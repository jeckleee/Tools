# Jeckleee/Tools

一个 PHP 数据验证和工具库，提供符合直觉的链式调用验证器，让数据验证变得简单高效。

## 主要特性

- **数据验证** : 提供符合直觉的验证器，使用链式调用添加规则，方便记忆和使用
- **常用工具函数** : 封装了一些常用的数据处理方法
- **支持多种验证方式** : 批量验证、单字段验证、变量验证
- **灵活的配置** : 支持自定义异常、错误码、错误返回模式
- **丰富的验证规则** : 覆盖大部分常用验证场景
- **框架兼容** : 可在 Laravel、Webman、ThinkPHP 等主流 PHP 框架中直接使用

## 安装

```bash
composer require jeckleee/tools
```

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

| 验证规则 | 说明 | 参数示例 |
|:---------|:-----|:---------|
| **基础验证** |
| `required` | 字段必填，可设置一个默认值 | `required('默认值')` |
| `ifExisted` | 字段存在时才验证，否则跳过 | `ifExisted()` |
| `requiredWith` | 当指定字段存在且不为空时，当前字段必填 | `requiredWith('email')` |
| `requiredWithout` | 当指定字段不存在或为空时，当前字段必填 | `requiredWithout('phone')` |
| `same` | 当前字段值必须与指定字段值相同 | `same('password')` |
| `different` | 当前字段值必须与指定字段值不同 | `different('old_password')` |
| **字符串验证** |
| `strTrim` | 去除字段两端的空格、制表符、换行符等 | `strTrim()` |
| `strLength` | 字段的值必须在指定范围的长度 | `strLength(3, 32)` |
| `strStartWith` | 字段的值必须以指定的字符串开始 | `strStartWith('http')` |
| `strEndWith` | 字段的值必须以指定的字符串结尾 | `strEndWith('.com')` |
| `strAlpha` | 字段的值只能由字母组成 | `strAlpha()` |
| `strAlphaNum` | 字段的值只能由字母和数字组成，`true` 时必须同时包含字母和数字 | `strAlphaNum(true)` |
| `strLowercase` | 将字段的值转换为小写 | `strLowercase()` |
| `strUppercase` | 将字段的值转换为大写 | `strUppercase()` |
| **数字验证** |
| `betweenNumber` | 字段的值必须在某两个数字区间(含) | `betweenNumber(1, 100)` |
| `cmpNumber` | 对字段进行比较，允许的符号: >, <, >=, <=, !=, = | `cmpNumber('>', 18)` |
| `isNumber` | 字段的值必须是数字(int 或 float，字符串数字也通过) | `isNumber()` |
| `isInt` | 字段的值必须是整数（int 类型或整数字符串，如 "123" 也通过） | `isInt()` |
| `isFloat` | 字段的值必须是小数，可限制小数位数 | `isFloat(2)` |
| **数组验证** |
| `inArray` | 字段的值必须在数组中 | `inArray([1,2,3])` |
| `notInArray` | 字段的值必须不在数组中 | `notInArray(['admin'])` |
| `isArray` | 字段的值必须是数组 | `isArray()` |
| **常用格式验证** |
| `isEmail` | 字段的值必须是邮箱 | `isEmail()` |
| `isMobile` | 字段的值必须是中国大陆手机号 | `isMobile()` |
| `isDateFormat` | 字段的值必须是指定格式的时间字符串 | `isDateFormat('Y-m-d')` |
| `isIdCard` | 字段的值必须是中国大陆身份证号 | `isIdCard()` |
| `isUrl` | 字段的值必须是网址 | `isUrl()` |
| `isIp` | 字段的值必须是 IP 地址(ipv4 或 ipv6) | `isIp('ipv4')` |
| `isBool` | 字段的值必须是布尔值 | `isBool()` |
| `isJson` | 字段的值必须是一个 json 字符串，`true` 时转为数组 | `isJson(true)` |
| `isBase64` | 字段的值必须是有效的Base64编码字符串 | `isBase64()` |
| **文件验证** |
| `isFile` | 文件校验，支持多种格式：<br/>1. 原始 `$_FILES` 数组<br/>2. Laravel 的 `Illuminate\Http\UploadedFile` 对象<br/>3. Webman 的 `support\UploadFile` 对象<br/>4. ThinkPHP 的 `think\file\UploadedFile` 对象<br/>常见用法：<br/>- `isFile($_FILES, ['jpg','png'], 1024)`<br/>- `isFile($request->file(), ['pdf'], 2048)`<br/>校验通过返回 ''，否则抛出异常 | `isFile($_FILES, ['jpg','png'], 1024)` |
| **其他验证** |
| `withRegex` | 使用正则表达式验证字段 | `withRegex('/^[a-z]+$/')` |
| `fun` | 使用自定义验证函数 | `fun(function($val){ return $val > 0; })` |

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
- **isFile($file, $ext = [], $maxSize_Kb = 500)**
  - 支持多种文件对象，校验通过返回 ''，否则抛出异常。
  - `$file` 可为 `$_FILES`、Laravel/Webman/ThinkPHP 上传对象。
  - `$ext` 限制扩展名，空数组不限制。
  - `$maxSize_Kb` 最大文件大小，单位 KB，默认 500KB。
- **isJson(true)**
  - 校验为 json 字符串，`true` 时自动转为数组。
- **strAlphaNum(true)**
  - 必须同时包含字母和数字。
- **requiredWith/requiredWithout**
  - 依赖字段存在/不存在时，当前字段必填。
- **fun(callable)**
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
    'avatar' => $_FILES['avatar'] ?? null
];

// 验证一组数据
$data = V::array($post, [
    // 基础验证
    V::field('name')->required()->strTrim()->strLength(3, 32)->verify('请填写正确的用户名'),
    // 密码验证
    V::field('password')->required()->strLength(6, 20)->verify('密码长度6-20位'),
    V::field('password_confirm')->same('password')->verify('两次密码不一致'),
    // 邮箱验证
    V::field('email')->required()->isEmail()->verify('请填写正确的邮箱'),
    // 年龄验证（只接受 int 类型）
    V::field('age')->required()->isInt()->betweenNumber(1, 120)->verify('请填写正确的年龄'),
    // 文件验证（第一个参数为 $_FILES，第二个为扩展名数组，第三个为最大 KB 数）
    V::field('avatar')->isFile($_FILES, ['jpg', 'png', 'gif'], 2*1024)->verify('头像格式或大小不正确'),
    // 条件验证
    V::field('phone')->requiredWithout('email')->isMobile()->verify('手机号或邮箱至少填写一个'),
    V::field('email_code')->requiredWith('email')->strLength(4, 6)->verify('邮箱验证码必填'),
    // 可选字段验证
    V::field('score')->ifExisted()->isInt()->betweenNumber(0, 100)->verify('请填写正确的分数'),
]);
// $data 包含所有验证通过的字段
```

### 2. 验证单个字段

```php
// 验证一个字段
$age = V::one($post, [
    V::field('age')->required()->isInt()->betweenNumber(1, 120)->verify('请填写正确的年龄'),
]);
echo $age; // 输出: 18
```

### 3. 验证变量

```php
// 验证变量是否正确, 返回 (bool) TRUE or FALSE
$phone = '123456789';
if (V::var($phone)->isMobile()->check()) {
    echo '手机号码正确';
} else {
    echo '手机号码不正确';
}
```

### 4. 自定义验证规则

```php
// 自定义验证方法, 只有回调方法返回 (bool) true 时, 才验证通过
$data = V::one($post, [
    V::field('age')->fun(function ($value) {
        return $value >= 18;
    })->verify('年龄不能小于18岁'),
]);
```

### 5. 条件必填验证

```php
$data = V::array($input, [
    // 当邮箱存在时，验证码必填
    V::field('email')->required()->isEmail()->verify('邮箱格式错误'),
    V::field('email_code')->requiredWith('email')->strLength(4, 6)->verify('邮箱验证码必填'),
    // 当邮箱不存在时，手机号必填
    V::field('phone')->requiredWithout('email')->isMobile()->verify('手机号或邮箱至少填写一个'),
]);
```

### 6. 字段值比较验证

```php
$data = V::array($input, [
    // 密码确认
    V::field('password')->required()->strLength(6, 20)->verify('密码长度6-20位'),
    V::field('password_confirm')->same('password')->verify('两次密码不一致'),
    // 新旧密码不能相同
    V::field('new_password')->required()->different('old_password')->verify('新密码不能与原密码相同'),
]);
```

### 7. 文件上传验证

```php
// 兼容多种文件上传格式
$data = V::array($request->all(), [
    // 原始 $_FILES 格式
    V::field('avatar')->isFile($_FILES, ['jpg', 'png', 'gif'], 2*1024)->verify('头像格式或大小不正确'),
    // Laravel 框架
    V::field('document')->isFile($request->file(), ['pdf', 'doc', 'docx'], 10*1024)->verify('文档格式或大小不正确'),
    // Webman 框架
    V::field('image')->isFile($request->file(), ['jpg', 'png'], 1024)->verify('图片格式或大小不正确'),
]);

// Laravel 使用示例
public function upload(Request $request)
{
    $data = V::array($request->all(), [
        V::field('avatar')->isFile($request->file(), ['jpg', 'png'], 2*1024)->verify('头像格式或大小不正确'),
        V::field('document')->isFile($request->file(), ['pdf'], 5*1024)->verify('文档格式或大小不正确'),
    ]);
    // 处理文件上传
    if (isset($data['avatar'])) {
        $path = $data['avatar']->store('avatars');
    }
}

// Webman 使用示例
public function upload(Request $request)
{
    $data = V::array($request->all(), [
        V::field('avatar')->isFile($request->file(), ['jpg', 'png'], 2*1024)->verify('头像格式或大小不正确'),
        V::field('document')->isFile($request->file(), ['pdf'], 5*1024)->verify('文档格式或大小不正确'),
    ]);
    // 处理文件上传
    if (isset($data['avatar'])) {
        $path = $data['avatar']->move('uploads/avatars');
    }
}
```

### 8. JSON 字符串校验

```php
// 校验并转为数组
V::field('data')->isJson(true)->verify('数据格式错误');
```

### 9. Base64 编码校验

```php
// 校验Base64编码字符串
V::field('image_data')->isBase64()->verify('图片数据格式错误');

```

### 10. 浮点数校验

```php
// 校验并限制 2 位小数
V::field('price')->isFloat(2)->verify('价格格式错误');
```

### 11. 数字比较

```php
// 年龄大于 18
V::field('age')->cmpNumber('>', 18)->verify('年龄必须大于18岁');
```

### 12. isInt/isNumber/isFloat 区别示例

```php
V::field('a')->isInt()->verify(); // 接受 int 或整数字符串（如 "123"）
V::field('b')->isNumber()->verify(); // 接受 int/float/字符串数字
V::field('c')->isFloat(2)->verify(); // 浮点数且最多2位小数
```

### 13. isFile 返回值说明

```php
// 校验通过返回 ''，否则抛出异常
$result = V::field('avatar')->isFile($_FILES, ['jpg'], 1024)->verify();
// $result === ''

```

### 14. 自定义函数校验

```php
V::field('score')->fun(function($val){ return $val > 60; })->verify('分数必须大于60');
```

### 15. 字符串大小写转换

```php
// 转小写
V::field('username')->strLowercase()->verify('用户名转小写失败');
// 转大写
V::field('code')->strUppercase()->verify('验证码转大写失败');
```

---

## 异常处理

### 自定义异常和错误码

```php
// 1. 使用配置文件中定义异常和错误码
$data = V::array($post, [
    V::field('name')->required()->verify('请填写账号'),
]);

// 2. 在使用 array() 或者 one() 方法时定义异常和错误码
$data = V::array($post, [
    V::field('name')->required()->verify('请填写账号'),
], MyException::class, 500);

// 3. 在规则中的 ->verify() 方法中定义的错误码优先级最高
$data = V::array($post, [
    V::field('name')->required()->verify('请填写账号', 12001),
    V::field('age')->required()->isInt()->betweenNumber(1, 120)->verify('请填写正确的年龄', 12002),
]);
```

### 错误返回模式

```php
// immediate 模式：立即返回第一个错误
$data = V::array($post, $rules, null, null, 'immediate');

// collective 模式：收集所有错误后返回
$data = V::array($post, $rules, null, null, 'collective');
```

> `error_return_mode` 只支持 `immediate` 和 `collective`，否则会抛出异常。

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
            $input = V::array($request->all(), [
                // 基础信息验证
                V::field('username')->required()->strTrim()->strLength(3, 20)->strAlphaNum()->verify('用户名格式错误'),
                V::field('email')->required()->isEmail()->verify('邮箱格式错误'),
                V::field('phone')->requiredWithout('email')->isMobile()->verify('手机号或邮箱至少填写一个'),
                // 密码验证
                V::field('password')->required()->strLength(6, 20)->verify('密码长度6-20位'),
                V::field('password_confirm')->same('password')->verify('两次密码不一致'),
                // 个人信息验证
                V::field('age')->ifExisted()->isInt()->betweenNumber(1, 120)->verify('年龄格式错误'),
                V::field('avatar')->ifExisted()->isFile($request->file(), ['jpg', 'png'], 1024)->verify('头像格式或大小错误'),
                // 自定义验证
                V::field('invite_code')->fun(function($val) {
                    return strlen($val) === 6 && ctype_alnum($val);
                })->verify('邀请码格式错误'),
            ]);
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
$masked = Tool::desensitizeString('13812345678', 3, 4);

// 生成 UUID
$uuid = Tool::generateUUID();
```

### 工具函数说明

- **Tool::arrayBindKey($arr, $key)**
  - 作用：将二维数组按某字段转为以该字段为 key 的关联数组。
  - 示例：
    ```php
    $arr = [['id'=>1,'name'=>'A'], ['id'=>2,'name'=>'B']];
    $res = Tool::arrayBindKey($arr, 'id');
    // $res = [1=>['id'=>1,'name'=>'A'], 2=>['id'=>2,'name'=>'B']]
    ```
- **Tool::arraySequence($arr, $field, $sort = 'SORT_DESC')**
  - 作用：按指定字段排序。
  - 示例：
    ```php
    $arr = [['id'=>1,'age'=>20], ['id'=>2,'age'=>18]];
    $res = Tool::arraySequence($arr, 'age', 'SORT_ASC');
    // $res = [['id'=>2,'age'=>18], ['id'=>1,'age'=>20]]
    ```
- **Tool::generateTree($list, $id, $pid, $children)**
  - 作用：生成树形结构。
- **Tool::getRandomString($len)**
  - 作用：生成指定长度的随机字符串。
- **Tool::diffDateDays($date1, $date2)**
  - 作用：计算两个日期相差天数。
- **Tool::desensitizeString($str, $start, $end)**
  - 作用：字符串脱敏。
- **Tool::generateUUID()**
  - 作用：生成 UUID。

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
6. **isInt/isNumber 区别**：`isInt` 接受 int 类型 和 字符串整数 ；`isNumber` 可接受字符串数字。
7. **isFloat**：可限制小数位数。
8. **isJson**：`true` 时自动转为数组。
9. **框架兼容性**：可在 Laravel、Webman、ThinkPHP 等框架中使用。
10. **配置函数依赖**：如需自定义配置，需保证 `config()` 函数可用。

---

## 贡献

欢迎提交 Issue 和 Pull Request 来完善这个工具！

## 许可证

MIT License
