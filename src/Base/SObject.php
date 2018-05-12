<?php
/**
 * Created by PhpStorm.
 * User: Bear <hululidexiong@163.com>
 * Date: 2018/5/8
 * Time: 10:47
 */

namespace MValid\Base;

class SObject{

    public $EntityAttributes;

    public function __construct($config = [])
    {
        if (!empty($config)) {
            Bear::configure($this, $config);
        }
        $this->init();
    }

    /**
     * Initializes the object.
     * This method is invoked at the end of the constructor after the object is initialized with the
     * given configuration.
     */
    public function init()
    {
    }

    public static function className()
    {
        return get_called_class();
    }

    public function hasMethod($name)
    {
        return method_exists($this, $name);
    }

    public function __get($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            // read property, e.g. getName()
            return $this->$getter();
        }

        if (method_exists($this, 'set' . $name)) {
            throw new ValidException('Getting write-only property: ' . get_class($this) . '::' . $name);
        }

        throw new ValidException('Getting unknown property: ' . get_class($this) . '::' . $name);
    }

    public function __set($name, $value)
    {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            // set property
            $this->$setter($value);
            return;
        }

        if(is_array($this->EntityAttributes) && in_array($name , $this->EntityAttributes)){
            $this->$name = $value;
            return;
        }
        if (method_exists($this, 'get' . $name)) {
            throw new ValidException('Setting read-only property: ' . get_class($this) . '::' . $name);
        }

        throw new ValidException('Setting unknown property: ' . get_class($this) . '::' . $name);
    }

}