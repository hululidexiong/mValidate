<?php
/**
 * Created by PhpStorm.
 * User: Bear <hululidexiong@163.com>
 * Date: 2018/5/8
 * Time: 9:59
 */

namespace MValid;


use MValid\Base\Bear;
use MValid\Base\SObject;
use MValid\Base\ValidException;

class Validator extends SObject{
    /**
     * @var array list of built-in validators (name => class or configuration)
     */
    public static $builtInValidators = [
        'boolean' => 'MValid\Validators\BooleanValidator',
        'number' => 'MValid\Validators\NumberValidator',
        'required' => 'MValid\Validators\RequiredValidator',
        'email' => 'MValid\Validators\EmailValidator',
        'string' => 'MValid\Validators\StringValidator',

        //'double' => 'yii\validators\NumberValidator',
        //'each' => 'yii\validators\EachValidator',
        //'exist' => 'yii\validators\ExistValidator',
        //'safe' => 'yii\validators\SafeValidator',
        //'default' => 'yii\validators\DefaultValueValidator',
    ];

    /**
     * @var array|string attributes to be validated by this validator. For multiple attributes,
     * please specify them as an array; for single attribute, you may use either a string or an array.
     */
    public $attributes = [];
    /**
     * @var string the user-defined error message. It may contain the following placeholders which
     * will be replaced accordingly by the validator:
     *
     * - `{attribute}`: the label of the attribute being validated
     * - `{value}`: the value of the attribute being validated
     *
     * Note that some validators may introduce other properties for error messages used when specific
     * validation conditions are not met. Please refer to individual class API documentation for details
     * about these properties. By convention, this property represents the primary error message
     * used when the most important validation condition is not met.
     */
    public $message;


    public function validateAttributes($model, $attributes = null)
    {
        if (is_array($attributes)) {
            $newAttributes = [];
            foreach ($attributes as $attribute) {
                if (in_array($attribute, $this->getAttributeNames(), true)) {
                    $newAttributes[] = $attribute;
                }
            }
            $attributes = $newAttributes;
        } else {
            $attributes = $this->getAttributeNames();
        }

        foreach ($attributes as $attribute) {
//            $skip = $this->skipOnError && $model->hasErrors($attribute)
//                || $this->skipOnEmpty && $this->isEmpty($model->$attribute);
            //if (!$skip) {
                //if ($this->when === null || call_user_func($this->when, $model, $attribute)) {
                    $this->validateAttribute($model, $attribute);
                //}
            //}
        }
    }

    public function validateAttribute($model, $attribute)
    {
        $result = $this->validateValue($model->$attribute);
        if (!empty($result)) {
            $this->addError($model, $attribute, $result[0], $result[1]);
        }
    }

    protected function validateValue( $value )
    {
        throw new ValidException( get_class($this) . ' does not support validateValue().');
    }

    /**
     * Creates a validator object.
     * @param string|\Closure $type the validator type. This can be either:
     *  * a built-in validator name listed in [[builtInValidators]];
     *  * a method name of the model class;
     *  * an anonymous function;
     *  * a validator class name.
     * @param \MValid\Model $model the data model to be validated.
     * @param array|string $attributes list of attributes to be validated. This can be either an array of
     * the attribute names or a string of comma-separated attribute names.
     * @param array $params initial values to be applied to the validator properties.
     * @return Validator the validator
     */
    public static function createValidator($type, $model, $attributes, $params = [])
    {
        if( $params ){

        }
        $params['attributes'] = $attributes;

        if ($type instanceof \Closure || $model->hasMethod($type)) {
            //待开发
            // method-based validator
//            $params['class'] = __NAMESPACE__ . '\InlineValidator';
//            $params['method'] = $type;
        } else {
            if (isset(static::$builtInValidators[$type])) {
                $type = static::$builtInValidators[$type];
            }
            if (is_array($type)) {
                $params = array_merge($type, $params);
            } else {
                $params['class'] = $type;
            }
        }
//var_dump( $params );
//        var_dump( $type );
        return Bear::createObject($params);
    }

    /**
     * Returns cleaned attribute names without the `!` character at the beginning
     * @return array attribute names.
     * @since 2.0.12
     */
    public function getAttributeNames()
    {
        return array_map(function($attribute) {
            return ltrim($attribute, '!');
        }, $this->attributes);
    }

    /**
     * Adds an error about the specified attribute to the model object.
     * This is a helper method that performs message selection and internationalization.
     * @param \MValid\Model $model the data model being validated
     * @param string $attribute the attribute being validated
     * @param string $message the error message
     * @param array $params values for the placeholders in the error message
     */

    public function addError($model, $attribute, $message, $params = [])
    {
        $params['attribute'] = $model->getAttributeLabel($attribute);
        if (!isset($params['value'])) {
            $value = $model->$attribute;
            if (is_array($value)) {
                $params['value'] = 'array()';
            } elseif (is_object($value) && !method_exists($value, '__toString')) {
                $params['value'] = '(object)';
            } else {
                $params['value'] = $value;
            }
        }
        $model->addError($attribute, $this->formatMessage($message, $params));
    }


    public function formatMessage( $message , $params ){
        $placeholders = [];
        foreach ((array) $params as $name => $value) {
            $placeholders['{' . $name . '}'] = $value;
        }
        return ($placeholders === []) ? $message : strtr($message, $placeholders);
    }

    public function isEmpty($value)
    {
//        if ($this->isEmpty !== null) {
//            return call_user_func($this->isEmpty, $value);
//        } else {
            return $value === null || $value === [] || $value === '';
//        }
    }
}