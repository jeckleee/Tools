# Validator

一些常用工具:数据验证

## 安装

composer require jeckleee/tools

## 使用

```php
use Jeckleee\Tools\Validator

$post=['name'=>'jeckleee','password'=>'123456','email'=>'jeckleee@qq.com','age'=>18];

//验证一个数组
$data=Validator::array($post,[
     //只有写在此数组中的字段才会验证并存储到$data中
     Validator::fieldName('name')->required()->stringTrim()->stringLength(3,32)->msg('请填写正确的用户名'),
     Validator::fieldName('password')->required()->stringTrim()->stringLength(6,32)->msg('请填写正确的密码'),
     Validator::fieldName('email')->required()->isEmail()->msg('请填写正确的邮箱'),
]);
//$data=['name'=>'jeckleee','password'=>'123456','email'=>'jeckleee@qq.com'];age字段不会出现在$data中

//验证一个字段
$data=Validator::one($post,[
     Validator::fieldName('age')->required()->isIntval()->betweenNumber(1,120)->msg('请填写正确的年龄'),
]);
//$data=18

//自定义异常和错误码
$data=Validator::array($post,[
     //......省略
],MyException::class,11002);
//如果不定义异常类，则使用默认的Exception::class


//查看全部可用的验证规则
echo json_decode(Validator::$showAllRules);

```
