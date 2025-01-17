<?php
return [
	'enable' => true,

	// 定义验证失败以后抛出的异常
	'exception' => Exception::class,

	// 定义验证失败的错误码
	'exception_code' => 500,

	//验证失败错误如何返回(immediate:立即返回,集中返回:collective)
	'error_return_mode' => 'collective',
];