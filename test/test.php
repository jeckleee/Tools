<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Jeckleee\Tools\Validator as V;


//echo json_encode(Validator::fieldName('aa')->isNumber()->betweenNumber(1,10)->msg('数据类验证失败')->rules);
//die;
try {
	$data = V::array([
		'aa' => '10.1',
		'bb' => '12',
		'cc' => '622426199102230071',
		'dd' => 'a',
		'ff' => '{"a":1}',
	], [
		V::field('cc')->ifExisted()->isIdCard()->verify('请填写正确的身份证号', 300),
		V::field('ff')->isJson()->verify('必须是json字符串', 301),
		//V::field('cc')->isIdCard()->verify('请填写正确的身份证号'),
		//V::field('ee')->required('eeeee')->verify()
	]);

	echo json_encode($data);
} catch (\Exception $e) {
	echo json_encode([
		'code' => $e->getCode(),
		'msg' => $e->getMessage(),
	]);
}
