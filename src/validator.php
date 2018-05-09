<?php
/**
 * Created by PhpStorm.
 * User: Bear <hululidexiong@163.com>
 * Date: 2018/5/8
 * Time: 9:59
 */

namespace MValid;


use MValid\Base\Bear;
use MValid\Base\Object;
use MValid\Base\ValidException;

class Validator extends Object{
    /**
     * @var array list of built-in validators (name => class or configuration)
     */
    public static $builtInValidators = [
        'boolean' => 'yii\validators\BooleanValidator',
        'default' => 'yii\validators\DefaultValueValidator',
        'double' => 'yii\validators\NumberValidator',
        //'each' => 'yii\validators\EachValidator',
        'email' => 'yii\validators\EmailValidator',
        'exist' => 'yii\validators\ExistValidator',
        'number' => 'yii\validators\NumberValidator',
        'required' => 'yii\validators\RequiredValidator',
        'safe' => 'yii\validators\SafeValidator',
        'string' => 'yii\validators\StringValidator',
    ];


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

    protected function validateValue( $value = null )
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
}