# mValidate

Example:
```
class BaseCtl extends \MValid\Model{}

//这里的info ，用load的第二个参数控制 ， 默认是 BaseCtl(类名)
$_POST['info']['email'] = '105@qq.com';
require( __DIR__ . '/EmailEntity.php');
$a = new BaseCtl();
$validate = $a->loadEntity( Email::class )->load( \MValid\Base\Bear::post() , 'info')->validate();
var_dump( $validate );
if(!$validate){
    var_dump($a->getErrors());
}
var_dump($a->getAttributes());

```
Entity:
```
class Email {

    //  AUTO_INCREMENT 默认主键（ primary key ）
    public $id = [
        'Type'=>'int',
        'Length'=>11,
        'AUTO_INCREMENT' => true
    ];

    public $email = [
        'Type'=>'varchar',
        'Length'=>255,
        'Default'=>'',
        'Comment'=> 'email',
        'ValidateMode' => ['email']
    ];
}

```