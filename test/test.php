<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Jeckleee\Tools\Validator as V;


//echo json_encode(Validator::fieldName('aa')->isNumber()->betweenNumber(1,10)->msg('数据类验证失败')->rules);
//die;

$data = V::array([
    'aa' => 1,
    'bb' => 10,
    'cc' => '622426199102230071',
    'dd' => 'a',
], [
//    V::fieldName('aa')->isNumber()->betweenNumber(1, 10)->msg('数据类验证失败:aa'),
//    V::fieldName('bb')->isNumber()->betweenNumber(1, 10)->msg('数据类验证失败:bb'),
//    V::fieldName('cc')->isIdCard()->msg('请填写正确的身份证号'),
    V::fieldName('ee')->required('eeeee')->msg()
], Exception::class, 400);

echo json_encode($data);