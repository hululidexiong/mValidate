<?php
/**
 * Created by PhpStorm.
 * User: Bear <hululidexiong@163.com>
 * Date: 2018/5/8
 * Time: 11:30
 */

namespace MValid\Base;

class Bear {

    static public $charset = 'UTF-8';

    static function createObject( $type, array $params = [] ){
        if( is_array($type) && isset($type['class']) ){
            $class = $type['class'];
            unset($type['class']);
            $params = $params ? $params : $type;
            return new $class( $params );
        }
    }

    public static function configure($object, $properties)
    {
        foreach ($properties as $name => $value) {
            $object->$name = $value;
        }
        return $object;
    }

    public static function camel2words($name, $ucwords = true)
    {
        $label = strtolower(trim(str_replace([
            '-',
            '_',
            '.',
        ], ' ', preg_replace('/(?<![A-Z])[A-Z]/', ' \0', $name))));

        return $ucwords ? ucwords($label) : $label;
    }

    public static function t($text){
        return $text;
    }

    public static function format($message, $params)
    {
        $params = (array) $params;
        if ($params === []) {
            return $message;
        }

        $p = [];
        foreach ($params as $name => $value) {
            $p['{' . $name . '}'] = $value;
        }

        return strtr($message, $p);
    }

    static public function post(){
        return array_merge($_GET,$_POST);
    }
}