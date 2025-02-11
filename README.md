# Jeckleee/Tools

一些常用工具:数据验证 | 常用Function

## 安装

```angular2html
composer require jeckleee/tools
```

## 配置

```php
//配置文件: config/plugin/jeckleee/tools/app.php

return [
    'enable' => true,

    // 定义验证失败以后抛出的异常,webman框架建议使用 BusinessException::class
    'exception' => Exception::class,

    // 定义验证失败的错误码
    'exception_code' => 500,
    
    //验证失败错误如何返回(immediate,collective)
    //immediate:立即返回,只要验证出现错误,立即抛出当前错误字段的异常信息,不再验证剩余的字段
    //collective:集中返回,验证全部字段,收集所有异常,验证结束后在异常$e->getMessage()中返回错误字段的json字符串
    'error_return_mode' => 'immediate',
	
];
```

## 使用场景1:验证表单提交的数据

```php
use Jeckleee\Tools\Validator

$post=['name'=>'jeckleee','password'=>'123456','email'=>'jeckleee@qq.com','age'=>18];

//验证一组数据
$data=Validator::array($post,[
     //只有写在此数组中的字段才会验证并存储到$data中
     Validator::field('name')->required()->strTrim()->strLength(3,32)->verify('请填写正确的用户名'),
     
     //使用自定义正则表达式验证
     Validator::field('password')->required()->withRegex('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/')->verify('要求密码必须包含大写字母、小写字母、数字和特殊字符'),
     Validator::field('email')->required()->isEmail()->verify('请填写正确的邮箱'),
     
     //不验证score字段,如果字段不存在则返回["score"=>null]
     Validator::field('score')->verify(),
     
     //存在则验证,如果字段不存在则不验证,也不会出现在最终的数据中
     Validator::field('score')->ifExisted()->isInt()->betweenNumber(0,100)->verify('请填写正确的分数'),
     
]);
//$data=['name'=>'jeckleee','password'=>'123456','email'=>'jeckleee@qq.com','score'=>null]; //age字段不会出现在$data中

// 按需使用 extract 函数将关联数组转换为变量
extract($data);
// 现在可以使用这些变量了
echo $name; // 输出: jeckleee
echo $password;  // 输出: 123456
echo email; // 输出: jeckleee@qq.com


//验证一个字段
$data=Validator::one($post,[
     Validator::field('age')->required()->isInt()->betweenNumber(1,120)->verify('请填写正确的年龄'),
]);
//$data=18


//自定义验证方法,只有回调方法返回(bool)true时,才验证通过,否则验证失败
$data=Validator::one($post,[
     Validator::field('age')->fun(function ($value){
         if ($value<18){
             return false;
         }
         return true;
     })->verify('年龄不能小于18岁'),
]);

//自定义验证失败的异常
$data=Validator::array($post,[
     //......省略
],MyException::class);
//如果不定义异常类，则使用配置文件中定义的异常


//自定义错误码
$data=Validator::array($post,[
     //第一种,在->verify()方法中定义,选填,适合对每一个字段定义不同的错误码的场景
      Validator::field('name')->required()->verify('请填写账号',12001),
      Validator::field('age')->required()->isInt()->betweenNumber(1,120)->verify('请填写正确的年龄',12002),
],MyException::class,12000);//这是第二种,所有的验证失败都用同一个错误码的场景

//三种异常定义的区别:
//1.在配置文件中定义异常和错误码,优先级最低
//2.在使用array()或者one()方法时定义异常和错误码,会覆盖配置文件中定义的异常
//3.在规则中的->verify()方法中定义的异常和错误码优级最高,会覆盖之前所有的定义


```

## 使用场景2: 验证变量是否正确,返回(bool) TURE or FALSE

```php
use Jeckleee\Tools\Validator

$phone='123456789';

if (Validator::var($phone)->isMobile()->check()){
    echo '手机号码正确';
}else{
    echo '手机号码不正确'
}
```

## 注意事项

```php
//查看全部可用的验证规则
echo json_encode(Validator::$showAllRules);
```
