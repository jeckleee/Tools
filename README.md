# Jeckleee/Tools

一个 PHP 数据验证和工具库，提供符合直觉的链式调用验证器，让数据验证变得简单高效。

## 主要特性

- **数据验证** : 提供符合直觉的验证器，使用链式调用添加规则，方便记忆和使用
- **常用工具函数** : 封装了一些常用的数据处理方法
- **统一验证入口**：表单、单字段和独立变量都使用 `verify(...)->field(...)->get()`。
- **灵活的配置** : 支持自定义异常、错误码、错误返回模式
- **丰富的验证规则** : 覆盖大部分常用验证场景
- **框架兼容** : 可在 Laravel、Webman、ThinkPHP 等主流 PHP 框架中直接使用

## 安装

```bash
composer require jeckleee/tools
```

## 2.x 链式验证

用 `V::verify($input)` 传入数据，依次声明字段和规则，最后调用 `get()` 执行校验。

```php
use Jeckleee\Tools\Validator as V;

$data = V::verify($input)
    ->field('name')->required()->strTrim()->strLength(3, 32)->msg('用户名格式错误', 12001)
    ->field('email')->required()->email()->msg('邮箱格式错误', 12002)
    ->get();

$name = V::verify($input)->field('name')->required()->strTrim()->get();
```

`msg()` 为当前字段的所有规则设置错误提示和错误码。两个参数都可省略，默认沿用规则的提示和配置的错误码。只改错误码可以写 `->msg(null, 12001)`。

`get()` 按声明的 `field()` 数量返回结果。单字段返回处理后的值，单个可选字段缺失时返回 `null`。多字段返回关联数组，缺失且被 `ifExisted()` 跳过的字段不出现在数组中。校验失败时按配置抛出异常。

在 Swoole 或 Webman 的请求处理函数中，每次调用 `V::verify($input)` 都会创建一条新的验证链，字段规则从空数组开始。每次 `get()` 也会创建独立的校验上下文。将验证链保存在请求的局部变量中即可。如果把链存进单例或静态属性，它也会持续引用该次输入和规则。

从 1.x 迁移时，将数据传给 `V::verify()`，将每个字段的错误提示和错误码移到 `msg()`，最后调用一次 `get()`。验证独立变量时，给它一个字段名，例如 `V::verify(['email' => $email])->field('email')->email()->get()`。

## 配置

```php
// 配置文件: config/plugin/jeckleee/tools/app.php
return [
    'enable' => true,
    // 定义验证失败以后抛出的异常, webman 框架建议使用 BusinessException::class
    'exception' => Exception::class,
    // 定义验证失败的错误码
    'exception_code' => 500,
    // 验证失败错误如何返回(immediate,collective)
    // immediate: 立即返回, 只要验证出现错误, 立即抛出当前错误字段的异常信息, 不再验证剩余的字段
    // collective: 集中返回, 验证全部字段, 收集所有异常, 验证结束后在异常 $e->getMessage() 中返回错误字段的列表, json 字符串形式
    'error_return_mode' => 'immediate', // 只支持 immediate 和 collective，其他值会抛异常
];
```

## 验证规则总览

| 验证规则 | 说明                                                                                                                                                                                                                                                                                                        | 参数示例 |
|:---------|:----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|:---------|
| **基础验证** |
| `required` | 字段必填，可设置一个默认值                                                                                                                                                                                                                                                                                             | `required('默认值')` |
| `ifExisted` | 字段存在时才验证，否则跳过                                                                                                                                                                                                                                                                                             | `ifExisted()` |
| `requiredWith` | 当指定字段存在且不为空时，当前字段必填                                                                                                                                                                                                                                                                                       | `requiredWith('email')` |
| `requiredWithout` | 当指定字段不存在或为空时，当前字段必填                                                                                                                                                                                                                                                                                       | `requiredWithout('phone')` |
| `same` | 当前字段值必须与指定字段值相同                                                                                                                                                                                                                                                                                           | `same('password')` |
| `different` | 当前字段值必须与指定字段值不同                                                                                                                                                                                                                                                                                           | `different('old_password')` |
| **字符串验证** |
| `strTrim` | 去除字段两端的空格、制表符、换行符等                                                                                                                                                                                                                                                                                        | `strTrim()` |
| `strLength` | 字段的值必须在指定范围的长度                                                                                                                                                                                                                                                                                            | `strLength(3, 32)` |
| `strStartWith` | 字段的值必须以指定的字符串开始                                                                                                                                                                                                                                                                                           | `strStartWith('http')` |
| `strEndWith` | 字段的值必须以指定的字符串结尾                                                                                                                                                                                                                                                                                           | `strEndWith('.com')` |
| `strAlpha` | 字段的值只能由字母组成                                                                                                                                                                                                                                                                                               | `strAlpha()` |
| `strAlphaNum` | 字段的值只能由字母和数字组成，`true` 时必须同时包含字母和数字                                                                                                                                                                                                                                                                        | `strAlphaNum(true)` |
| `strLowercase` | 将字段的值转换为小写                                                                                                                                                                                                                                                                                                | `strLowercase()` |
| `strUppercase` | 将字段的值转换为大写                                                                                                                                                                                                                                                                                                | `strUppercase()` |
| **数字验证** |
| `betweenNumber` | 字段的值必须在某两个数字区间(含)                                                                                                                                                                                                                                                                                         | `betweenNumber(1, 100)` |
| `cmpNumber` | 对字段进行比较，允许的符号: >, <, >=, <=, !=, =                                                                                                                                                                                                                                                                        | `cmpNumber('>', 18)` |
| `isNumber` | 字段的值必须是数字(int 或 float，字符串数字也通过)                                                                                                                                                                                                                                                                           | `isNumber()` |
| `isInt` | 字段的值必须是整数（int 类型或整数字符串，如 "123" 也通过）                                                                                                                                                                                                                                                                       | `isInt()` |
| `isFloat` | 字段的值必须是小数，可限制小数位数                                                                                                                                                                                                                                                                                         | `isFloat(2)` |
| **数组验证** |
| `inArray` | 字段的值必须在数组中                                                                                                                                                                                                                                                                                                | `inArray([1,2,3])` |
| `notInArray` | 字段的值必须不在数组中                                                                                                                                                                                                                                                                                               | `notInArray(['admin'])` |
| `isArray` | 字段的值必须是数组                                                                                                                                                                                                                                                                                                 | `isArray()` |
| **常用格式验证** |
| `email` | 字段的值必须是邮箱                                                                                                                                                                                                                                                                                                 | `email()` |
| `mobile` | 字段的值必须是中国大陆手机号                                                                                                                                                                                                                                                                                            | `mobile()` |
| `dateFormat` | 字段的值必须是指定格式的时间字符串                                                                                                                                                                                                                                                                                         | `dateFormat('Y-m-d')` |
| `idCard` | 字段的值必须是中国大陆身份证号                                                                                                                                                                                                                                                                                           | `idCard()` |
| `isUrl` | 字段的值必须是网址                                                                                                                                                                                                                                                                                                 | `isUrl()` |
| `isIp` | 字段的值必须是 IP 地址(ipv4 或 ipv6)                                                                                                                                                                                                                                                                                | `isIp('ipv4')` |
| `isBool` | 字段的值必须是布尔值                                                                                                                                                                                                                                                                                                | `isBool()` |
| `isJson` | 字段的值必须是一个 json 字符串，`true` 时转为数组                                                                                                                                                                                                                                                                           | `isJson(true)` |
| `isBase64` | 字段的值必须是有效的Base64编码字符串                                                                                                                                                                                                                                                                                     | `isBase64()` |
| **文件验证** |
| `file` | 文件校验，支持多种格式：<br/>1. 原始 `$_FILES` 数组<br/>2. Laravel 的 `Illuminate\Http\UploadedFile` 对象<br/>3. Webman 的 `support\UploadFile` 对象<br/>4. ThinkPHP 的 `think\file\UploadedFile` 对象<br/>常见用法：<br/>- `file($_FILES, ['jpg','png'], 1024)`<br/>- `file($request->file(), ['pdf'], 2048)`<br/>校验通过后返回原始文件名，失败时抛出异常 | `file($_FILES, ['jpg','png'], 1024)` |
| **其他验证** |
| `withRegex` | 使用正则表达式验证字段                                                                                                                                                                                                                                                                                               | `withRegex('/^[a-z]+$/')` |
| `func` | 使用自定义验证函数                                                                                                                                                                                                                                                                                                 | `func(function($val){ return $val > 0; })` |

---

## 规则行为详解与注意事项

- **required($def = null)**
  - 字段必填。若传递 `$def`，当字段不存在时会自动赋值为 `$def` 并通过校验。
- **ifExisted()**
  - 字段存在时才验证，否则跳过。适合可选字段。
- **isInt()**
  - 接受 int 类型或整数字符串（如 "123"、"-456" 也通过）。
- **isNumber()**
  - 接受 int、float 及字符串数字（如 "123"、"12.3"）。
- **isFloat($decimalPlaces = null)**
  - 校验为浮点数，若指定 `$decimalPlaces`，则小数位数不能超过该值。
- **file($file, $ext = [], $maxSize_Kb = 500)**
  - 支持多种文件对象，校验通过后返回原始文件名，失败时抛出异常。
  - `$file` 可为 `$_FILES`、Laravel/Webman/ThinkPHP 上传对象。
  - `$ext` 限制扩展名，空数组不限制。
  - `$maxSize_Kb` 最大文件大小，单位 KB，默认 500KB。
- **isJson(true)**
  - 校验为 json 字符串，`true` 时自动转为数组。
- **strAlphaNum(true)**
  - 必须同时包含字母和数字。
- **requiredWith/requiredWithout**
  - 依赖字段存在/不存在时，当前字段必填。
- **func(callable)**
  - 传入自定义函数，返回 true 通过，否则抛出异常。

---

## 用法示例

### 1. 批量验证表单数据

```php
use Jeckleee\Tools\Validator as V;

$post = [
    'name' => 'jeckleee',
    'password' => '123456',
    'password_confirm' => '123456',
    'email' => 'jeckleee@qq.com',
    'age' => 18,
    'phone' => '13800138000',
    'email_code' => '123456',
    'avatar' => $_FILES['avatar'] ?? null
];

// 验证一组数据
$data = V::verify($post)
    // 基础验证
    ->field('name')->required()->strTrim()->strLength(3, 32)->msg('请填写正确的用户名')
    // 密码验证
    ->field('password')->required()->strLength(6, 20)->msg('密码长度6-20位')
    ->field('password_confirm')->same('password')->msg('两次密码不一致')
    // 邮箱验证
    ->field('email')->required()->email()->msg('请填写正确的邮箱')
    // 年龄验证（接受整数或整数字符串）
    ->field('age')->required()->isInt()->betweenNumber(1, 120)->msg('请填写正确的年龄')
    // 文件验证（第一个参数为 $_FILES，第二个为扩展名数组，第三个为最大 KB 数）
    ->field('avatar')->ifExisted()->file($_FILES, ['jpg', 'png', 'gif'], 2*1024)->msg('头像格式或大小不正确')
    // 条件验证
    ->field('phone')->requiredWithout('email')->mobile()->msg('手机号或邮箱至少填写一个')
    ->field('email_code')->requiredWith('email')->strLength(4, 6)->msg('邮箱验证码必填')
    // 可选字段验证
    ->field('score')->ifExisted()->isInt()->betweenNumber(0, 100)->msg('请填写正确的分数')
    ->get();
// $data 包含所有验证通过的字段
```

### 2. 验证单个字段

```php
$age = V::verify($post)
    ->field('age')->required()->isInt()->betweenNumber(1, 120)->msg('请填写正确的年龄')
    ->get();
```

### 3. 验证变量

把变量放入命名字段。需要布尔判断时，在调用方捕获配置的校验异常。

```php
try {
    V::verify(['phone' => $phone])->field('phone')->mobile()->get();
    $valid = true;
} catch (\Exception $e) {
    $valid = false;
}
```

### 4. 自定义验证规则

```php
$age = V::verify($post)
    ->field('age')->func(fn($value) => $value >= 18)->msg('年龄不能小于18岁')
    ->get();
```

### 5. 条件必填验证

```php
$data = V::verify($input)
    // 当邮箱存在时，验证码必填
    ->field('email')->required()->email()->msg('邮箱格式错误')
    ->field('email_code')->requiredWith('email')->strLength(4, 6)->msg('邮箱验证码必填')
    // 当邮箱不存在时，手机号必填
    ->field('phone')->requiredWithout('email')->mobile()->msg('手机号或邮箱至少填写一个')
    ->get();
```

### 6. 字段值比较验证

```php
$data = V::verify($input)
    // 密码确认
    ->field('password')->required()->strLength(6, 20)->msg('密码长度6-20位')
    ->field('password_confirm')->same('password')->msg('两次密码不一致')
    // 新旧密码不能相同
    ->field('new_password')->required()->different('old_password')->msg('新密码不能与原密码相同')
    ->get();
```

### 7. 文件上传验证

原生 PHP 可以传入 `$_FILES`。在 Laravel 或 Webman 中，可以传入 `$request->file()` 返回的文件集合。

```php
$files = $request->file();
$data = V::verify($request->all())
    ->field('avatar')->file($files, ['jpg', 'png'], 2 * 1024)->msg('头像格式或大小不正确')
    ->field('document')->file($files, ['pdf'], 5 * 1024)->msg('文档格式或大小不正确')
    ->get();
```

### 8. JSON 字符串校验

```php
// 校验并转为数组
$data = V::verify($post)
    ->field('data')->isJson(true)->msg('数据格式错误')
    ->get();
```

### 9. Base64 编码校验

```php
// 校验Base64编码字符串
$data = V::verify($post)
    ->field('image_data')->isBase64()->msg('图片数据格式错误')
    ->get();
```

### 10. 浮点数校验

```php
// 校验并限制 2 位小数
$data = V::verify($post)
    ->field('price')->isFloat(2)->msg('价格格式错误')
    ->get();
```

### 11. 数字比较

```php
$data = V::verify($post)
    ->field('age')->cmpNumber('>', 18)->msg('年龄必须大于18岁')
    ->get();
```

### 12. isInt/isNumber/isFloat 区别示例

```php
$data = V::verify($post)
    ->field('a')->isInt() // 接受 int 或整数字符串（如 "123"）
    ->field('b')->isNumber() // 接受 int/float/字符串数字
    ->field('c')->isFloat(2) // 浮点数且最多2位小数
    ->get();
```

### 13. file 返回值说明

```php
// 单字段校验通过后取得原始文件名，文件存储由业务代码处理
$originalName = V::verify($post)
    ->field('avatar')->file($_FILES, ['jpg'], 1024)
    ->get();
// ...自己处理文件上传
$avatar=$request->file('avatar')->store('uploads/avatar/avatar');

```

### 14. 自定义函数校验

```php
$data = V::verify($post)
    ->field('score')->func(function($val){ return $val > 60; })->msg('分数必须大于60')
    ->get();
```

### 15. 字符串大小写转换

```php
$data = V::verify($post)
    // 转小写
    ->field('username')->strLowercase()->msg('用户名转小写失败')
    // 转大写
    ->field('code')->strUppercase()->msg('验证码转大写失败')
    ->get();
```

---

## 异常处理

### 自定义异常和错误码

`get()` 默认使用项目配置。需要对单次校验设置异常类、默认错误码或返回模式时，可以使用命名参数。`msg()` 设置的字段错误码优先。

```php
$data = V::verify($post)
    ->field('name')->required()->msg('请填写账号', 12001)
    ->field('age')->required()->isInt()->msg('请填写正确的年龄')
    ->get(exceptionClass: MyException::class, errorCode: 422, errorMode: 'immediate');
```

### 错误返回模式

`immediate` 会在首个错误处抛出异常。`collective` 会汇总错误，异常消息是包含字段名、错误码和提示的 JSON 数组。需要对单次校验收集错误时，使用 `->get(errorMode: 'collective')`。

---

## 完整使用示例

```php
use Jeckleee\Tools\Validator as V;
use support\Request;

class UserController extends BaseController
{
    public function register(Request $request): \support\Response
    {
        try {
            $input = V::verify($request->all())
                // 基础信息验证
                ->field('username')->required()->strTrim()->strLength(3, 20)->strAlphaNum()->msg('用户名格式错误')
                ->field('email')->required()->email()->msg('邮箱格式错误')
                ->field('phone')->requiredWithout('email')->mobile()->msg('手机号或邮箱至少填写一个')
                // 密码验证
                ->field('password')->required()->strLength(6, 20)->msg('密码长度6-20位')
                ->field('password_confirm')->same('password')->msg('两次密码不一致')
                // 个人信息验证
                ->field('age')->ifExisted()->isInt()->betweenNumber(1, 120)->msg('年龄格式错误')
                ->field('avatar')->ifExisted()->file($request->file(), ['jpg', 'png'], 1024)->msg('头像格式或大小错误')
                // 自定义验证
                ->field('invite_code')->func(function($val) {
                    return strlen($val) === 6 && ctype_alnum($val);
                })->msg('邀请码格式错误')
                ->get();
            //需要自己处理处理头像的上传...todo
            $avatar = $request->file('avatar')->store('uploads/avatar/avatar');
            
            //将头像地址增加到$input数据中
            $input['avatar']=$avatar;
            // 创建用户
            $user = User::create($input);
            return json(['code' => 200, 'msg' => '注册成功', 'data' => $user]);
        } catch (BusinessException $exception) {
            return json([
                'code' => $exception->getCode() ?: 300,
                'msg' => $exception->getMessage(),
                'status' => 'error'
            ]);
        }
    }
}
```

---

## 工具函数

除了验证器，本工具还提供了一些常用的工具函数：

```php
use Jeckleee\Tools\Tool;

// 二维数组根据字段绑定到唯一键
$users = Tool::arrayBindKey($userList, 'id');
// $users = [1=>['id'=>1,'name'=>'A'], 2=>['id'=>2,'name'=>'B']]

// 二维数组根据字段排序
$sortedUsers = Tool::arraySequence($userList, 'age', 'SORT_DESC');
// $sortedUsers = [['id'=>2,'age'=>30], ['id'=>1,'age'=>20]]

// 生成树形结构
$tree = Tool::generateTree($list, 'id', 'parent_id', 'children');

// 生成随机字符串
$randomStr = Tool::getRandomString(16);

// 计算日期差
$days = Tool::diffDateDays('2024-01-01', '2024-01-10');

// 字符串脱敏
$masked = Tool::maskSecret('13812345678', 3, 4);

// 生成 UUID
$uuid = Tool::generateUUID();

// 安全 UUID v4 / 时间有序 UUID v7
$uuid4 = Tool::uuidV4();
$uuid7 = Tool::uuidV7();

// 安全随机数
$ri = Tool::randomInt(1, 100);
$rf = Tool::randomFloat(0.1, 9.9);

// 人性化时间差与字节显示
$diff = Tool::humanizeDiff('2025-01-01 12:00:00'); // 如："3天前"
$size = Tool::humanBytes(1234567); // 1.18 MB

// 稳定排序构建查询串（RFC3986）
$query = Tool::buildQuery(['b'=>2, 'a'=>['y'=>2, 'x'=>1]]); // a%5Bx%5D=1&a%5By%5D=2&b=2

// 分组、去重、分块、二分
$groups = Tool::arrayGroupBy([
  ['id'=>1,'cat'=>'A'],
  ['id'=>2,'cat'=>'B'],
  ['id'=>3,'cat'=>'A'],
], 'cat');
// [ 'A' => [...], 'B' => [...] ]

$uniq = Tool::arrayUniqueBy([
  ['id'=>1,'name'=>'x'],
  ['id'=>1,'name'=>'x2'],
  ['id'=>2,'name'=>'y'],
], 'id'); // 稳定去重，保留首次出现

$chunks = Tool::arrayChunkFixed([1,2,3,4,5], 2); // [[1,2],[3,4],[5]]

[$evens, $odds] = Tool::arrayPartition([1,2,3,4], fn($n)=> $n%2===0);

// 树相关：扁平化、查找、路径
$flat = Tool::flattenTree($tree, 'children');
$found = Tool::findInTree($tree, fn($node)=> ($node['id']??null) === 5);
$pathInTree = Tool::pathInTree($tree, 5, 'id', 'children'); // 从根到目标的路径数组

// 从扁平数组追溯路径
$flatNodes = [
  ['id'=>1,'parent_id'=>null],
  ['id'=>2,'parent_id'=>1],
  ['id'=>5,'parent_id'=>2],
];
$path = Tool::pathInFlat($flatNodes, 5, 'id', 'parent_id');

// 重试工具
$result = Tool::retry(function(int $attempt){
  if ($attempt < 3) throw new Exception('try again');
  return 'ok';
}, times: 5, sleepMs: 100);
```

### 工具函数说明

- **Tool::arrayBindKey($arr, $key)**
  - 作用：二维数组按某字段转为以该字段为 key 的关联数组。
  - 示例：
    ```php
    $arr = [['id'=>1,'name'=>'A'], ['id'=>2,'name'=>'B']];
    $res = Tool::arrayBindKey($arr, 'id');
    // [1=>['id'=>1,'name'=>'A'], 2=>['id'=>2,'name'=>'B']]
    ```
- **Tool::arraySequence($arr, $field, $sort = 'SORT_DESC')**
  - 作用：按指定字段排序，支持 SORT_ASC/SORT_DESC。
  - 示例：
    ```php
    $arr = [['id'=>1,'age'=>20], ['id'=>2,'age'=>18]];
    $res = Tool::arraySequence($arr, 'age', 'SORT_ASC');
    // [['id'=>2,'age'=>18], ['id'=>1,'age'=>20]]
    ```
- **Tool::arrayGroupBy($arr, string|callable $key)**
  - 作用：按键名或回调分组，返回键为字符串的分组映射。
- **Tool::arrayUniqueBy($arr, string|callable $key)**
  - 作用：按键稳定去重，保留首次出现的元素。
- **Tool::arrayChunkFixed($arr, int $size)**
  - 作用：按固定长度分块，size<=0 时返回原数组。
- **Tool::arrayPartition($arr, callable $predicate)**
  - 作用：按谓词拆分为 [匹配数组, 不匹配数组]。
- **Tool::generateTree($list, $idField='id', $parentField='p_id', $children='children')**
  - 作用：生成树形结构（基于父子引用）。
- **Tool::flattenTree($tree, $children='children')**
  - 作用：扁平化树，移除子节点字段。
- **Tool::findInTree($tree, callable $predicate, $children='children')**
  - 作用：在树中查找首个满足条件的节点。
- **Tool::pathInTree($tree, mixed $id, $idField='id', $children='children')**
  - 作用：返回从根到目标节点的路径数组，未找到返回空数组。
- **Tool::pathInFlat($flat, mixed $id, $idField='id', $parentField='parent_id')**
  - 作用：在扁平数组中自底向上追溯父级路径。
- **Tool::getRandomString(int $length)**
  - 作用：生成长度为 length 的随机字符串（URL 安全字符集）。
- **Tool::maskSecret(string $str, int $startKeep, int $endKeep, string $mask='*', ?int $maxLen=null)**
  - 作用：字符串脱敏，支持多字节字符与最大长度限制。
- **Tool::humanizeDiff(DateTime|string $datetime)**
  - 作用：人性化时间差：刚刚/秒前/分钟前/小时前/天前/日期时间。
- **Tool::humanBytes(int|float $bytes, int $precision=2)**
  - 作用：人类可读的字节单位展示（B/KB/MB/GB/TB/PB）。
- **Tool::buildQuery(array $params)**
  - 作用：递归键排序后，按 RFC3986 规则构建查询字符串，稳定可复现。
- **Tool::generateUUID()**
  - 作用：基于 mt_rand 的不安全 UUID（非加密安全）。
- **Tool::uuidV4()**
  - 作用：安全 UUID v4（random_bytes），RFC 4122 兼容。
- **Tool::uuidV7()**
  - 作用：时间有序的 UUID v7，便于索引与排序。
- **Tool::randomInt(int $min, int $max)**
  - 作用：生成加密安全的随机整数（含边界）。
- **Tool::randomFloat(float $min, float $max)**
  - 作用：生成 [min, max] 区间随机浮点数。
- **Tool::diffDateDays(string $date1, string $date2)**
  - 作用：计算两个日期间相差天数。
- **Tool::retry(callable $fn, int $times=3, int $sleepMs=100, ?callable $shouldRetry=null)**
  - 作用：失败自动重试；可自定义重试判定与重试间隔。

---

## 注意事项

1. **验证规则顺序**：建议将 `required` 规则放在最前面，避免对空值进行不必要的验证。
2. **错误消息**：可以为每个规则自定义错误消息，提高用户体验。
3. **性能考虑**：使用 `ifExisted` 规则可以避免对不存在字段的验证。
4. **文件验证**：文件验证支持多种格式：
   - 原始 `$_FILES` 数组格式
   - Laravel 的 `Illuminate\Http\UploadedFile` 对象
   - Webman 的 `support\UploadFile` 对象
   - ThinkPHP 的 `think\file\UploadedFile` 对象
5. **条件验证**：合理使用 `requiredWith` 和 `requiredWithout` 可以处理复杂的表单逻辑。
6. **isInt/isNumber 区别**：`isInt` 接受 int 类型 和 字符串整数 ；`isNumber` 可接受字符串数字 和 浮点数字符串。
7. **isFloat**：可限制小数位数。
8. **isJson**：`true` 时自动转为数组。
9. **框架兼容性**：可在 Laravel、Webman、ThinkPHP 等框架中使用。
10. **配置函数依赖**：如需自定义配置，需保证 `config()` 函数可用。

---

## 贡献

欢迎提交 Issue 和 Pull Request 来完善这个工具！

## 许可证

MIT License
