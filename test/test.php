<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Jeckleee\Tools\Validator as V;


//echo json_encode(Validator::fieldName('aa')->isNumber()->betweenNumber(1,10)->msg('数据类验证失败')->rules);
//die;
$input = [
	'aa' => '10.1',
	'bb' => '12',
	'cc' => '622426199102230071',
	'dd' => 'a',
	'ee' => 3,
	'ff' => '{"a":1}',
	'gg' => 3,
	'phone' => '17666666666',
	'xxx' => 'as1',

];

try {
	$data = V::array($input, [
		V::field('cc')->ifExisted()->isIdCard()->verify('请填写正确的身份证号', 300),
		V::field('ff')->isJson()->verify('必须是json字符串', 301),
		V::field('cc')->isIdCard()->verify('请填写正确的身份证号'),
		V::field('ee')->required('eeeee')->verify(),
		V::field('gg')->fun(function ($val) use ($input) {
			if ($input['phone'] == '17666666665') {
				return true;
			}
			if ($input['bb'] == '12') {
				return true;
			}
			
			return false;
		})->verify(),
		V::field('xxx')->strAlphaNum()->strTrim()->strLength(2, 100)->isDateTimeInFormat('Y-m')->verify(),
	]);
//echo json_encode($data);
	
	$res = V::var($input['phone'])->required()->cmpNumber('>', 1)->check();
	
	
	echo json_encode($data);
} catch (\Exception $e) {
	echo json_encode([
		'code' => $e->getCode(),
		'msg' => $e->getMessage(),
	]);
}
