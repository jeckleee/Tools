<?php

namespace Jeckleee\Tools;

use Exception;

/** @internal 每次执行校验时创建，规则仅访问本次调用的输入、输出和异常配置。 */
final class ValidationContext
{
	public array $output = [];
	public string $exceptionClass;
	private int $errorCode;
	private string $errorMode;

	public function __construct(public array $input, ?string $exceptionClass = null, ?int $errorCode = null, ?string $errorMode = null)
	{
		$config = [
			'exception' => Exception::class,
			'exception_code' => 500,
			'error_return_mode' => 'immediate',
		];
		if (function_exists('config')) {
			$config = \config('plugin.jeckleee.tools.app', $config);
		}
		$this->exceptionClass = $exceptionClass ?? $config['exception'];
		$this->errorCode = $errorCode ?? $config['exception_code'];
		$this->errorMode = $errorMode ?? $config['error_return_mode'];
		if (!in_array($this->errorMode, ['immediate', 'collective'], true)) {
			throw new $this->exceptionClass('error_return_mode参数错误', $this->errorCode);
		}
	}

	public function validate(array $fields): array
	{
		$errors = [];
		foreach ($fields as $field) {
			$name = $field['fieldName'];
			if (empty($field['list'])) {
				$this->output[$name] = $this->input[$name] ?? null;
				continue;
			}
			foreach ($field['list'] as $item) {
				$ruleName = $item['_function_name'] ?? null;
				$value = $this->input[$name] ?? null;
				if ($ruleName === 'ifExisted' && $value === null) {
					break;
				}
				$item['err_msg'] = $field['err_msg'] ?? '';
				$item['err_code'] = $field['err_code'] ?? $this->errorCode;
				try {
					$item['function']($name, $value, $item, $this);
					// 字段缺失时沿用 required() 的默认值，跳过余下规则。
					if ($ruleName === 'required' && $value === null && $item['def'] !== null) {
						break;
					}
				} catch (Exception $e) {
					if ($this->errorMode !== 'collective' || !($e instanceof $this->exceptionClass)) {
						throw $e;
					}
					$errors[] = [
						'fieldName' => $name,
						'error_code' => $e->getCode(),
						'message' => $e->getMessage(),
					];
				}
			}
		}
		if ($errors) {
			throw new $this->exceptionClass(json_encode($errors), $this->errorCode);
		}
		return $this->output;
	}
}
