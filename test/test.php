<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Jeckleee\Tools\Validator as V;
use Jeckleee\Tools\Tool;


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
	'xxx' => '2025-02',

];

try {
	$uuid = Tool::maskSecret('176111888888', 3, 4, '*');
	echo json_encode([
		'code' => 200,
		'msg' => 'success',
		'data' => [
			'uuid' => $uuid,
		],
	]);
} catch (\Exception $e) {
	echo json_encode([
		'code' => $e->getCode(),
		'msg' => $e->getMessage(),
	]);
}
