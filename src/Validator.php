<?php

namespace Jeckleee\Tools;

class Validator
{
	public static array $showAllRules = [
		//基础
		'required' => '字段必填,可设置一个默认值',
		'ifExisted' => '对字段进行判断,如果字段存在,则进行验证',

		//字符串相关
		'strTrim' => '去除字段两端的空格、制表符、换行符等',
		'strLength' => '字段的值知必须指定范围的长度',
		'strStartWith' => '字段的值必须以指定的字符串开始',
		'strEndWith' => '字段的值必须以指定的字符串结尾',
		'strAlpha' => '字段的值只能由字母组成',
		'strAlphaNum' => '字段的值只能由字母和数字组成,$type=true时要求必须同时包含字母和数字',
		'strLowercase' => '将字段的值转换为小写',
		'strUppercase' => '将字段的值转换为大写',

		//数字相关
		'betweenNumber' => '字段的值必须在某个区间',
		'cmpNumber' => '对字段进行比较,是betweenNumber方法的补充,允许的符号:>,<,>=,<=,!=,=',
		'isNumber' => '字段的值必须是数字(int or float)',
		'isInt' => '字段的值必须是整数',
		'isFloat' => '字段的值必须是小数,传入参数控制小数位数',

		//数组相关
		'inArray' => '字段的值必须在数组中',
		'notInArray' => '字段的值必须不在数组中',
		'isArray' => '字段的值必须是数组',

		//常用
		'email' => '字段的值必须是邮箱',
		'mobile' => '字段的值必须是手机号',
		'dateFormat' => '字段的值必须是指定格式的时间字符串(Ymd-His等)',
		'idCard' => '字段的值必须是身份证号',
		'isUrl' => '字段的值必须是网址',
		'isIp' => '字段的值必须是IP地址(ipv4 or ipv6)',
		'file' => '字段的值必须是文件',


		//其他
		'isBool' => '字段的值必须是布尔值,为 "1", "true", "on" and "yes" 返回 TRUE,为 "0", "false", "off" and "no" 返回 FALSE',
		'isJson' => '字段的值必须是一个json字符串,允许传入参数将其转为Array',
		'isBase64' => '字段的值必须是有效的Base64编码字符串',
		'func' => '自定义校验，回调返回 true 时通过',
		'withRegex' => '使用正则表达式验证字段',
		'requiredWith' => '字段依赖于另一个字段,当另一个字段存在且不为空时,当前字段必填',
		'requiredWithout' => '字段依赖于另一个字段,当另一个字段不存在或为空时,当前字段必填',
		'same' => '字段值必须与指定字段值相同',
		'different' => '字段值必须与指定字段值不同',

	];

	public static function verify(array $input): ValidationChain
	{
		return new ValidationChain($input);
	}
}
