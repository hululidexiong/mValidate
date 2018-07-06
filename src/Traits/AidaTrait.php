<?php
/**
 * Created by PhpStorm.
 * User: Bear <hululidexiong@163.com>
 * Date: 2018/7/6
 * Time: 10:06
 */

namespace MValid\Traits;

trait AidaTrait{
    public function isEmpty($value)
    {
//        if ($this->isEmpty !== null) {
//            return call_user_func($this->isEmpty, $value);
//        } else {
        return $value === null || $value === [] || $value === '';
//        }
    }
}