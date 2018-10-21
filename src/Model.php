<?php
/**
 * Created by PhpStorm.
 * User: Bear <hululidexiong@163.com>
 * Date: 2018/5/8
 * Time: 10:20
 */

namespace MValid;




use MValid\Base\Bear;
use MValid\Base\SObject;
use MValid\Base\ValidException;
use MValid\Traits\AidaTrait;

class Model extends SObject{

    use AidaTrait;
    /**
     * The name of the default scenario.
     */
    const SCENARIO_DEFAULT = 'default';
    /**
     * @event ModelEvent an event raised at the beginning of [[validate()]]. You may set
     * [[ModelEvent::isValid]] to be false to stop the validation.
     */
    const EVENT_BEFORE_VALIDATE = 'beforeValidate';
    /**
     * @event Event an event raised at the end of [[validate()]]
     */
    const EVENT_AFTER_VALIDATE = 'afterValidate';

    /**
     * @var string current scenario
     */
    private $_scenario = self::SCENARIO_DEFAULT;

    /**
     * @var array validation errors (attribute name => array of errors)
     */
    private $_errors;

    /**
     * @var \ArrayObject list of validators
     */
    private $_validators;


    private $_rules=null;

    public $_ignore=[];

    /**
     * @var \MedMy\Entity
     */
    protected $_entity=null;
    /**
     * note :
     * Returns the validation rules for attributes.
     * Below are some examples:
     *
     * ```php
     * [
     *     // built-in "required" validator
     *     [['username', 'password'], 'required'],
     *     // built-in "string" validator customized with "min" and "max" properties
     *     ['username', 'string', 'min' => 3, 'max' => 12],
     *     // built-in "compare" validator that is used in "register" scenario only
     *     ['password', 'compare', 'compareAttribute' => 'password2', 'on' => 'register'],
     *     // an inline validator defined via the "authenticate()" method in the model class
     *     ['password', 'authenticate', 'on' => 'login'],
     *     // a validator of class "DateRangeValidator"
     *     ['dateRange', 'DateRangeValidator'],
     * ];
     * ```
     * @return array validation rules
     */
    public function rules()
    {
        return $this->_rules === null ? [] : $this->_rules;
    }

    public function getScenario()
    {
        return $this->_scenario;
    }

    public function setScenario($value)
    {
        $this->_scenario = $value;
    }

    public function scenarios()
    {
        $scenarios = [self::SCENARIO_DEFAULT => []];
        $names = array_keys($scenarios);
        foreach ($this->getValidators() as $validator) {
            foreach ($names as $name) {
                foreach ($validator->attributes as $attribute) {
                    $scenarios[$name][$attribute] = true;
                }
            }
        }
        foreach ($scenarios as $scenario => $attributes) {
            if (!empty($attributes)) {
                $scenarios[$scenario] = array_keys($attributes);
            }
        }
        return $scenarios;
    }

    public function getValidators()
    {
        if ($this->_validators === null) {
            $this->_validators = $this->createValidators();
        }
        return $this->_validators;
    }

    public function createValidators()
    {
        $validators = new \ArrayObject;
        foreach ($this->rules() as $rule) {
            if ($rule instanceof Validator) {
                $validators->append($rule);
            } elseif (is_array($rule) && isset($rule[0], $rule[1])) { // attributes, validator type
//                var_dump('+++++');
//                var_dump( $rule );
//                var_dump(  array_slice($rule, 2) );
                $validator = Validator::createValidator( ucfirst( $rule[1] ) , $this , (array) $rule[0], array_slice($rule, 2));
                $validators->append($validator);
            } else {
                throw new ValidException('Invalid validation rule: a rule must specify both attribute names and validator type.');
            }
        }
        return $validators;
    }


    /**
     * Returns the attribute names that are subject to validation in the current scenario.
     * @return string[] safe attribute names
     */
    public function activeAttributes()
    {
        $scenario = $this->getScenario();
        $scenarios = $this->scenarios();
        if (!isset($scenarios[$scenario])) {
            return [];
        }
        $attributes = $scenarios[$scenario];
//        foreach ($attributes as $i => $attribute) {
//            if ($attribute[0] === '!') {
//                $attributes[$i] = substr($attribute, 1);
//            }
//        }

        return $attributes;
    }

    /**
     * Returns the validators applicable to the current [[scenario]].
     * @param string $attribute the name of the attribute whose applicable validators should be returned.
     * If this is null, the validators for ALL attributes in the model will be returned.
     * @return \MValid\Validator[] the validators applicable to the current [[scenario]].
     */
    public function getActiveValidators($attribute = null)
    {
        $validators = [];
        //$scenario = $this->getScenario();
        foreach ($this->getValidators() as $validator) {
            //if ($validator->isActive($scenario) && ($attribute === null || in_array($attribute, $validator->getAttributeNames(), true))) {
            $validators[] = $validator;
            //}
        }
        return $validators;
    }


    public function ignore( $ignore ){
        $this->_ignore = (array)$ignore;
        return $this;
    }

    public function validate($attributeNames = null, $clearErrors = true)
    {
        if ($clearErrors) {
            $this->clearErrors();
        }

//        if (!$this->beforeValidate()) {
//            return false;
//        }

        $scenarios = $this->scenarios();
        $scenario = $this->getScenario();
        if (!isset($scenarios[$scenario])) {
            throw new ValidException("Unknown scenario: $scenario");
        }

        if ($attributeNames === null) {
            $attributeNames = $this->activeAttributes();
        }
        //######
        //var_dump( $this->getActiveValidators() );
        foreach ($this->getActiveValidators() as $validator) {
            $validator->validateAttributes($this, $attributeNames);
        }
        //$this->afterValidate();
        $this->_ignore = [];

        return !$this->hasErrors();
    }



    public function attributeLabels()
    {
        return [];
    }

    public function getAttributeLabel($attribute)
    {
        $labels = $this->attributeLabels();
        return isset($labels[$attribute]) ? $labels[$attribute] : $this->generateAttributeLabel($attribute);
    }

    public function generateAttributeLabel($name)
    {
        return Bear::camel2words($name, true);
    }

    public function clearErrors($attribute = null)
    {
        if ($attribute === null) {
            $this->_errors = [];
        } else {
            unset($this->_errors[$attribute]);
        }
    }
    /**
     * Returns a value indicating whether there is any validation error.
     * @param string|null $attribute attribute name. Use null to check all attributes.
     * @return bool whether there is any error.
     */
    public function hasErrors($attribute = null)
    {
        return $attribute === null ? !empty($this->_errors) : isset($this->_errors[$attribute]);
    }

    /**
     * Returns the errors for all attributes or a single attribute.
     * @param string $attribute attribute name. Use null to retrieve errors for all attributes.
     * @property array An array of errors for all attributes. Empty array is returned if no error.
     * The result is a two-dimensional array. See [[getErrors()]] for detailed description.
     * @return array errors for all attributes or the specified attribute. Empty array is returned if no error.
     * Note that when returning errors for all attributes, the result is a two-dimensional array, like the following:
     *
     * ```php
     * [
     *     'username' => [
     *         'Username is required.',
     *         'Username must contain only word characters.',
     *     ],
     *     'email' => [
     *         'Email address is invalid.',
     *     ]
     * ]
     * ```
     * @see getFirstErrors()
     * @see getFirstError()
     */
    public function getErrors($attribute = null)
    {
        if ($attribute === null) {
            return $this->_errors === null ? [] : $this->_errors;
        }
        return isset($this->_errors[$attribute]) ? $this->_errors[$attribute] : [];
    }

    /**
     * Returns the first error of every attribute in the model.
     * @return array the first errors. The array keys are the attribute names, and the array
     * values are the corresponding error messages. An empty array will be returned if there is no error.
     * @see getErrors()
     * @see getFirstError()
     */
    public function getFirstErrors()
    {
        if (empty($this->_errors)) {
            return [];
        }

        $errors = [];
        foreach ($this->_errors as $name => $es) {
            if (!empty($es)) {
                $errors[$name] = reset($es);
            }
        }
        return $errors;
    }

    /**
     * Returns the first error of the specified attribute.
     * @param string $attribute attribute name.
     * @return string the error message. Null is returned if no error.
     * @see getErrors()
     * @see getFirstErrors()
     */
    public function getFirstError($attribute)
    {
        return isset($this->_errors[$attribute]) ? reset($this->_errors[$attribute]) : null;
    }

    /**
     * Adds a new error to the specified attribute.
     * @param string $attribute attribute name
     * @param string $error new error message
     */
    public function addError($attribute, $error = '')
    {
        $this->_errors[$attribute][] = $error;
    }



    public function formName()
    {
        $reflector = new \ReflectionClass($this);
        return $reflector->getShortName();
    }

    /**
     * Returns the list of attribute names.
     * By default, this method returns all public non-static properties of the class.
     * You may override this method to change the default behavior.
     * @return array list of attribute names.
     */
    public function attributes()
    {
        $class = new \ReflectionClass($this);
        $names = [];
        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic()) {
                $names[] = $property->getName();
            }
        }

        return $names;
    }

    public function getAttrWithForm(){
        $data = [];
        if(method_exists($this->_entity , 'set_attr_strip')){
            $set_attr_strip = (array)$this->_entity->set_attr_strip();
            foreach( $this as $key=>$val){
                if(in_array($key , $set_attr_strip)){
                    $data[$key] = $val;
                }
            }

        }
        return $data;
    }

    public function getAttributes( $option = [] ){
        $option = array_merge( $this->_entity->_option, $option);
        $attr_strip = $option['get_attr_strip'];
        $get_field = $option['get_field'];
        if(!is_array( $attr_strip )){
            $attr_strip = (array)$attr_strip;
        }
        if(!is_array( $get_field )){
            $get_field = (array)$get_field;
        }
        $entityAttributes = $this->EntityAttributes;
        if( $get_field ){
            $entityAttributes = array_filter( $entityAttributes , function( $item ) use ($get_field){
                return in_array( $item , $get_field);
            });
        }else{
            foreach( $attr_strip as $item){
                $k = array_search( $item , $entityAttributes);
                if($k!==false){
                    array_splice( $entityAttributes , $k ,1);
                }
            }
        }

        $data = [];
        foreach( $this as $key=>$val){
            //由于目前是和表单验证一起的 ， 所有如果存在表单中的属性无条件返回
            $is_set = false;
            if(method_exists($this->_entity , 'set_attr_strip')){
                $set_attr_strip = (array)$this->_entity->set_attr_strip();
                if($set_attr_strip){
                    $is_set = in_array($key,$set_attr_strip);
                }
            }
            if(!$is_set){
                if( $option['is_filter_null'] === true && $this->isEmpty( $val )){
                    continue;
                }
            }

            if( in_array( $key , $entityAttributes ) ){
                $data[$key] = $val;
            }
        }
        return $data;
    }

    private function filter_set_attr( $name , $force = false ){
        //$this->_entity->
        $is_set = true;
        if(method_exists($this->_entity , 'set_attr_strip')){
            $set_attr_strip = (array)$this->_entity->set_attr_strip();
            if($set_attr_strip){
                $is_set = in_array($name,$set_attr_strip);
            }
        }
        $is = ( ( isset($this->$name)  && $is_set ) || $force);
        return $is;
    }

    public function setAttributes($values , $force = false )
    {
        if (is_array($values)) {
            foreach ($values as $name => $value) {

//                if( isset( $this->$name ) || $force ){
                if( $this->filter_set_attr( $name ,$force ) ){
                    $this->$name = $value;
                }
            }
//            $attributes = array_flip(  $this->attributes() );
//            foreach ($values as $name => $value) {
//                if (isset($attributes[$name])) {
//                    $this->$name = $value;
//                } else {
//                    $this->onUnsafeAttribute($name, $value);
//                }
//            }
        }
    }

    /**
     * Populates the model with input data.
     *
     * This method provides a convenient shortcut for:
     *
     * ```php
     * if (isset($_POST['FormName'])) {
     *     $model->attributes = $_POST['FormName'];
     *     if ($model->save()) {
     *         // handle success
     *     }
     * }
     * ```
     *
     * which, with `load()` can be written as:
     *
     * ```php
     * if ($model->load($_POST) && $model->save()) {
     *     // handle success
     * }
     * ```
     *
     * `load()` gets the `'FormName'` from the model's [[formName()]] method (which you may override), unless the
     * `$formName` parameter is given. If the form name is empty, `load()` populates the model with the whole of `$data`,
     * instead of `$data['FormName']`.
     *
     * Note, that the data being populated is subject to the safety check by [[setAttributes()]].
     *
     * @param array $data the data array to load, typically `$_POST` or `$_GET`.
     * @param string $formName the form name to use to load the data into the model.
     * If not set, [[formName()]] is used.
     *
     * bool whether `load()` found the expected form in `$data`
     * @return $this
     */
    public function load($data, $formName = null)
    {
        $scope = $formName === null ? $this->formName() : $formName;
        if ($scope === '' && !empty($data)) {
            $this->setAttributes($data);
            return $this;
            //return true;
        } elseif (isset($data[$scope])) {
            $this->setAttributes($data[$scope]);
            return $this;
            //return true;
        }
        throw new ValidException(' load data is invalid! ');
        return false;
    }

    /**
     * note : Entity obj
     * @param $Entity
     * @return $this
     */
    public function loadEntity( $Entity ){
        $class = new \ReflectionClass( $Entity );
        $data = [];
        $this->_entity = new $Entity;
        //TODO 临时在这里将保留属性过滤掉
        $filter = [ '_option' ];
        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic()) {
                $name = $property->getName();
                if( in_array( $name  , $filter)){
                    continue;
                }
                $value = $property->getValue( $this->_entity );
                $data[$name] = isset ($value['Default']) ? $value['Default'] : null ;

                if( isset( $value['ValidateMode'] ) && is_array( $value['ValidateMode'] ) ){
                    $param = array_slice( $value['ValidateMode'], 1);
                    $this->_rules[] = array_merge([$name , $value['ValidateMode'][0]] , $param);
//                    if( $param ){
//                        $this->_rules[] = [$name , $value['ValidateMode'][0] , $param ];
//                    }else{
//                        $this->_rules[] = [$name , $value['ValidateMode'][0]];
//                    }

                }

            }
        }

        $this->EntityAttributes = array_keys( $data );

//        var_dump( $this->EntityAttributes );
//        var_dump( 'rules------------' );
//        var_dump( $this->_rules );
        $this->setAttributes($data , true);
        return $this;
    }
}