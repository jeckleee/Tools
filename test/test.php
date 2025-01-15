<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Jeckleee\Tools\Validator as V;


//echo json_encode(Validator::fieldName('aa')->isNumber()->betweenNumber(1,10)->msg('数据类验证失败')->rules);
//die;

$data = V::array([
	'aa' => '10.1',
	'bb' => '12',
	'cc' => '622426199102230071',
	'dd' => 'a',
	'fff' => '{"a":1}',
], [
	V::field('cc')->ifExisted()->isIdCard()->verify('必须是身份证号'),
	//V::field('bb')->withRegex('/^[a-zA-Z]+$/')->verify('数据类验证失败'),
	//V::field('cc')->isIdCard()->verify('请填写正确的身份证号'),
	//V::field('ee')->required('eeeee')->verify()
], Exception::class, 400);

echo json_encode($data);