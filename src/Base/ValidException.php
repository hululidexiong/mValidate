<?php
/**
 * Created by PhpStorm.
 * User: Bear <hululidexiong@163.com>
 * Date: 2018/5/8
 * Time: 10:40
 */

namespace MValid\Base;

class ValidException extends \Exception{


    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'MValidate ';
    }
}