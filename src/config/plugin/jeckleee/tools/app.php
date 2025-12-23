<?php
return [
	'enable' => true,

	// 定义验证失败以后抛出的异常
	'exception' => Exception::class,

	// 定义验证失败的错误码
	'exception_code' => 500,

	//验证失败错误如何返回(immediate,collective)
	//immediate:立即返回,只要验证出现错误,立即抛出当前错误的字段的异常信息,不再验证剩余的字段
	//collective:集中返回,验证全部字段,收集所有异常,验证结束后在异常$e->getMessage()中返回错误字段的json字符串
	'error_return_mode' => 'immediate',
];