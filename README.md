# Jeckleee/Tools

一些常用工具:

- 数据验证 : 在很多情况下总是记不住验证器的规则,每次都得查询文档,所以本工具提供了一个符合直觉的验证器,使用链式调用添加规则,方便记忆和使用
- 常用Function :封装了一些常用的方法

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
    //collective:集中返回,验证全部字段,收集所有异常,验证结束后在异常$e->getMessage()中返回错误字段的列表,json字符串形式
    'error_return_mode' => 'immediate',
	
];
```

## 查看所有支持的验证规则

```php
use Jeckleee\Tools\Validator
echo json_encode(Validator::$showAllRules);
//此工具已经收集了大多数的常用规则,欢迎大家提交pr补充新的规则
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

```

### 验证一个字段

```php
//验证一个字段
$data=Validator::one($post,[
     Validator::field('age')->required()->isInt()->betweenNumber(1,120)->verify('请填写正确的年龄'),
]);
echo $data; //$data=18
```

### 自定义验证规则

```php

//自定义验证方法,只有回调方法返回(bool)true时,才验证通过,否则验证失败
$data=Validator::one($post,[
     Validator::field('age')->fun(function ($value){
         if ($value<18){
             return false;
         }
         return true;
     })->verify('年龄不能小于18岁'),
]);
```

### 自定义验证失败的异常和错误码

```php
//自定义验证失败的异常
$data=Validator::array($post,[
     //......省略
],MyException::class);
//如果不定义异常类，则使用配置文件中定义的异常




//三种异常定义的区别:
//1.使用配置文件中定义异常和错误码
$data=Validator::array($post,[
      Validator::field('name')->required()->verify('请填写账号'),
      //......省略
]);

//2.在使用array()或者one()方法时定义异常和错误码,会覆盖配置文件中定义的异常
$data=Validator::array($post,[
	Validator::field('name')->required()->verify('请填写账号'),
	//......省略
],MyException::class);


//3.在规则中的->verify()方法中定义的错误码优级最高,会覆盖之前所有的定义
$data=Validator::array($post,[
      Validator::field('name')->required()->verify('请填写账号',12001),
      Validator::field('age')->required()->isInt()->betweenNumber(1,120)->verify('请填写正确的年龄',12002),
      //......省略
]);

```

## 一个使用示例

```php
use Jeckleee\Tools\Validator as V;
use support\Request;

class PostController extends BaseController

{
	/**
	 * @Notes: 获取列表
	 * @Name getList
	 * @return \support\Response
	 * @author: -
	 * @Time: 2024/2/5 15:06
	 */
	public function getList(Request $request): \support\Response
	{

		try {
			$input = V::array($request->all(), [
				V::field('title')->verify(),
				V::field('currentPage')->required(1)->isInt()->cmpNumber('>=',1)->verify('请填写正确的页码'),
				V::field('pageSize')->required(30)->isInt()->betweenNumber(1,50)->verify('请填写正确的每页数据量(最大50)'),
				V::field('status')->required(-1)->inArray([-1, 1, 2, 9])->verify('请选择正确的状态'),
			]);
			$list = Post::getList($input);
			$this->pagedata['list'] = $list['list'];
			$this->pagedata['total'] = $list['total'];
		} catch (BusinessException $exception) {
			$this->code = $exception->getCode() ?: 300;
			$this->msg = $exception->getMessage();
			$this->status = 'error';
		}

		return json($this->getFormatApiData());//getFormatApiData是一个我自己的自定义方法,返回格式化后的数据或者错误
	}
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
