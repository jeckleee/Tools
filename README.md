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
     Validator::fieldName('password')->required()->withRegex('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/')->msg('要求密码必须包含大写字母、小写字母、数字和特殊字符'),
     Validator::fieldName('email')->required()->isEmail()->msg('请填写正确的邮箱'),
]);
//$data=['name'=>'jeckleee','password'=>'123456','email'=>'jeckleee@qq.com'];age字段不会出现在$data中

//验证一个字段
$data=Validator::one($post,[
     Validator::fieldName('age')->required()->isIntval()->betweenNumber(1,120)->msg('请填写正确的年龄'),
]);
//$data=18

//验证失败会抛出异常
//自定义异常
$data=Validator::array($post,[
     //......省略
],MyException::class);
//如果不定义异常类，则使用默认的Exception::class


//自定义错误码:有两个位置可以自定义错误码
$data=Validator::array($post,[
     //第一种,选填,优先级最高,
      Validator::fieldName('name')->required()->msg('请填写账号',12001),
      Validator::fieldName('age')->required()->isIntval()->betweenNumber(1,120)->msg('请填写正确的年龄',12002),
],MyException::class,12000);//这是第二种

//两种错误码定义的区别
//工具默认错误码500,如果在使用array或者one方法时,没有定义错误码,异常中的code就是500,
//在使用array或者one方法时定义了错误码,异常中的code就是定义的错误码,
//在规则中的->msg()方法中定义的错误码优先级最高,会覆盖之前所有的定义


//查看全部可用的验证规则
echo json_decode(Validator::$showAllRules);

```
