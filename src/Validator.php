<?php

namespace Jeckleee\Tools;

use DateTime;
use Exception;


class Validator
{
	private static array $input = [];
	private static array $output = [];
	private static string|null $customException = null;
	private static int $err_code = 500;
	private array $rules = [];
	private string $fieldName = '';
	private array $config = [];
	public static array $showAllRules = [
		'required' => '字段必填,可设置一个默认值',
		'ifExisted' => '对字段进行判断,如果字段存在,则进行验证',

		'strTrim' => '去除字段两端的空格、制表符、换行符等',
		'strLength' => '字段的值知必须指定范围的长度',
		'betweenNumber' => '字段的值必须在某个区间',
		'inArray' => '字段的值必须在数组中',
		'isArray' => '字段的值必须是数组',
		'isNumber' => '字段的值必须是数字(int or float)',
		'isEmail' => '字段的值必须是邮箱',
		'isMobile' => '字段的值必须是手机号',
		'isDateTimeInFormat' => '字段的值必须是指定格式的时间字符串(Ymd-His等)',
		'isIdCard' => '字段的值必须是身份证号',
		'isUrl' => '字段的值必须是网址',
		'isIp' => '字段的值必须是IP地址(ipv4 or ipv6)',
		'isInt' => '字段的值必须是整数',
		'isFloat' => '字段的值必须是小数,传入参数控制小数位数',
		'isBool' => '字段的值必须是布尔值,为 "1", "true", "on" and "yes" 返回 TRUE,为 "0", "false", "off" and "no" 返回 FALSE',
		'isJson' => '字段的值必须是一个json字符串,允许传入参数将其转为Array',
		'withRegex' => '使用正则表达式验证字段',
		'cmpNumber' => '对字段进行比较,是betweenNumber方法的补充,允许的符号:>,<,>=,<=,!=,=',

	];

	private static function getConfig()
	{
		// 定义默认配置
		$config = [
			// 验证失败以后抛出的异常
			'exception' => Exception::class,
			// 验证失败以后抛出的异常错误码
			'exception_code' => 500,
			//验证失败错误如何返回 (immediate:立即返回,集中返回:collective)
			'error_return_mode' => 'immediate'
		];
		if (function_exists('config')) {
			$config = config('config.plugin.jeckleee.tools.app', $config);
		}
		return $config;
	}

	private static function initialize(array $input, $rules, $customException = null, $err_code = null, $error_return_mode = null): void
	{
		$config = self::getConfig();
		self::$customException = $customException ?: $config['exception'];
		self::$err_code = $err_code ?: $config['exception_code'];

		if ($error_return_mode && !in_array($error_return_mode, ['immediate', 'collective'])) {
			throw new self::$customException('error_return_mode参数错误', self::$err_code);
		}
		self::$input = $input;
		self::$output = [];
	}

	public static function array(array $input, $rules, $customException = null, $err_code = null, $error_return_mode = null): array
	{
		self::initialize($input, $rules, $customException, $err_code, $error_return_mode);
		self::applyRules($rules);
		return self::$output;
	}

	public static function one(array $input, $rules, $customException = null, $err_code = null, $error_return_mode = null)
	{
		self::initialize($input, $rules, $customException, $err_code, $error_return_mode);
		self::applyRules($rules);
		return reset(self::$output);
	}


	public static function check(array $input, $rules, $customException = null, $err_code = null, $error_return_mode = null)
	{
		self::initialize($input, $rules, $customException, $err_code, $error_return_mode);
		self::applyRules($rules);
		if (count($rules) == 1) {
			return reset(self::$output);
		}
		return self::$output;
	}

	public static function field(string $fieldName): Validator
	{
		$validator = new static();
		$validator->fieldName = $fieldName;
		return $validator;
	}

	public function verify(string $err_msg = '', $err_code = null): array
	{
		$this->rules['err_msg'] = $err_msg ?: null;
		$this->rules['err_code'] = $err_code;
		$this->rules['fieldName'] = $this->fieldName;
		return $this->rules;
	}

	private static function applyRules($rules): void
	{
		$config = self::getConfig();
		$collective_error = [];
		foreach ($rules as $rule) {
			if (!is_array($rule)) throw new self::$customException('请在规则的链式结束后调用->verify()方法', self::$err_code);
			if ($rule['list'] ?? false) {
				foreach ($rule['list'] as $item) {
					if (isset($item['_function_name']) && $item['_function_name'] === 'ifExisted' && !isset(self::$input[$rule['fieldName']])) {
						break;
					}
					$function = $item['function'];
					$fieldName = $rule['fieldName'];
					$fieldValue = self::$input[$rule['fieldName']] ?? null;
					$item['err_msg'] = $rule['err_msg'] ?? '';
					$item['err_code'] = $rule['err_code'] ?: self::$err_code;
					try {
						$function($fieldName, $fieldValue, $item); // 调用闭包
					} catch (Exception $e) {
						if ($config['error_return_mode'] === 'collective' && $e instanceof $config['exception']) {
							$collective_error[] = [
								'fieldName' => $rule['fieldName'],
								'error_code' => $e->getCode(),
								'message' => $e->getMessage(),
							];
							continue;
						} else {
							// 如果不是预期的异常类型,重新抛出
							throw $e;
						}
					}
				}
			} else {
				//没有任何验证规则时
				self::$output[$rule['fieldName']] = self::$input[$rule['fieldName']] ?? null;
			}
		}
		if ($collective_error) {
			throw new self::$customException(json_encode($collective_error), self::$err_code);
		}
	}

	private function addRule(callable $function, $additionalParams = []): Validator
	{
		$this->rules['list'][] = array_merge([
			'function' => $function,
		], $additionalParams);
		return $this;
	}

	public function required($defaultValue = null): Validator
	{
		return $this->addRule(function ($fieldName, $fieldValue, $item) use ($defaultValue) {
			$msg = $item['err_msg'] ?: '参数必填:' . $fieldName;
			if (isset(self::$input[$fieldName])) {
				self::$output[$fieldName] = $fieldValue;
			} elseif ($defaultValue !== null) {
				self::$input[$fieldName] = $defaultValue;
				self::$output[$fieldName] = $defaultValue;
			} else {
				throw new self::$customException($msg, $item['err_code']);
			}
		});
	}

	public function strTrim(): Validator
	{
		return $this->addRule(function ($fieldName, $fieldValue, $item) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '不合法';
			if ($fieldValue) {
				self::$input[$fieldName] = trim($fieldValue);
				self::$output[$fieldName] = self::$input[$fieldName];
			} else {
				throw new self::$customException($msg, $item['err_code']);
			}
		});
	}

	public function betweenNumber(int $min, int $max): Validator
	{
		return $this->addRule(function ($fieldName, $fieldValue, $item) use ($min, $max) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '必须在' . $min . '-' . $max . '之间';
			if ($fieldValue >= $min && $fieldValue <= $max) {
				self::$output[$fieldName] = $fieldValue;
			} else {
				throw new self::$customException($msg, $item['err_code']);
			}
		}, ['min' => $min, 'max' => $max]);
	}

	public function inArray($array): Validator
	{
		return $this->addRule(function ($fieldName, $fieldValue, $item) use ($array) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '仅允许在(' . implode(',', $array) . ')中';
			if (in_array($fieldValue, $array)) {
				self::$output[$fieldName] = $fieldValue;
			} else {
				throw new self::$customException($msg, $item['err_code']);
			}
		}, ['array' => $array]);
	}

	public function isArray(): Validator
	{
		return $this->addRule(function ($fieldName, $fieldValue, $item) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '必须是一个数组';
			if (is_array($fieldValue)) {
				self::$output[$fieldName] = $fieldValue;
			} else {
				throw new self::$customException($msg, $item['err_code']);
			}
		});
	}

	public function isNumber(): Validator
	{
		return $this->addRule(function ($fieldName, $fieldValue, $item) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '必须是数字';
			if (is_numeric($fieldValue)) {
				if (is_float((float)$fieldValue)) {
					self::$output[$fieldName] = floatval($fieldValue);
				} else {
					self::$output[$fieldName] = intval($fieldValue);
				}
			} else {
				throw new self::$customException($msg, $item['err_code']);
			}
		});
	}

	public function strLength($min = 1, $max = 20): Validator
	{
		return $this->addRule(function ($fieldName, $fieldValue, $item) use ($min, $max) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '的长度必须在' . $min . '~' . $max . '之间';
			$length = mb_strlen($fieldValue, 'utf-8');
			if ($length >= $min && $length <= $max) {
				self::$output[$fieldName] = $fieldValue;
			} else {
				throw new self::$customException($msg, $item['err_code']);
			}
		}, ['min' => $min, 'max' => $max]);
	}

	public function isEmail(): Validator
	{
		return $this->addRule(function ($fieldName, $fieldValue, $item) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '不是一个合法的邮箱地址';
			if (filter_var($fieldValue, FILTER_VALIDATE_EMAIL)) {
				self::$output[$fieldName] = $fieldValue;
			} else {
				throw new self::$customException($msg, $item['err_code']);
			}
		});
	}

	public function isMobile(): Validator
	{
		return $this->addRule(function ($fieldName, $fieldValue, $item) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '不是一个合法的手机号';
			$phoneRegex = '/^1[3-9]\d{9}$/';
			if (preg_match($phoneRegex, $fieldValue)) {
				self::$output[$fieldName] = $fieldValue;
			} else {
				throw new self::$customException($msg, $item['err_code']);
			}
		});
	}

	public function isDateTimeInFormat($format): Validator
	{
		return $this->addRule(function ($fieldName, $fieldValue, $item) use ($format) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '不是一个合法的时间字符串.(' . $format . ')';
			$d = DateTime::createFromFormat($format, $fieldValue);
			if ($d && $d->format($format) === $fieldValue) {
				self::$output[$fieldName] = $fieldValue;
			} else {
				throw new self::$customException($msg, $item['err_code']);
			}
		}, ['format' => $format]);
	}

	public function isIdCard(): Validator
	{
		return $this->addRule(function ($fieldName, $fieldValue, $item) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '不是一个合法的身份证号';
			// 身份证号长度为 15 位或 18 位
			$pattern = '/^(?:\d{15}|\d{17}[\dxX])$/';
			if (!preg_match($pattern, $fieldValue)) {
				throw new self::$customException($msg, $item['err_code']);
			}

			// 15 位身份证号转换为 18 位
			if (strlen($fieldValue) === 15) {
				$fieldValue = substr($fieldValue, 0, 6) . '19' . substr($fieldValue, 6, 9);
			}

			// 计算校验位
			$weights = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
			$checkCodes = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];
			$sum = 0;
			for ($i = 0; $i < 17; $i++) {
				$sum += $weights[$i] * (int)$fieldValue[$i];
			}
			$checkCodeIndex = $sum % 11;

			// 验证校验位
			$lastChar = strtoupper($fieldValue[17]);
			if ($lastChar === $checkCodes[$checkCodeIndex]) {
				self::$output[$fieldName] = $fieldValue;
			} else {
				throw new self::$customException($msg, $item['err_code']);
			}
		});
	}

	public function isUrl(): Validator
	{
		return $this->addRule(function ($fieldName, $fieldValue, $item) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '不是一个合法的url';
			if (filter_var($fieldValue, FILTER_VALIDATE_URL)) {
				self::$output[$fieldName] = $fieldValue;
			} else {
				throw new self::$customException($msg, $item['err_code']);
			}
		});
	}

	public function isIp($type = 'ipv4'): Validator
	{
		return $this->addRule(function ($fieldName, $fieldValue, $item) use ($type) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '不是一个合法的ip地址';
			$filter = $type === 'ipv6' ? FILTER_FLAG_IPV6 : FILTER_FLAG_IPV4;
			if (filter_var($fieldValue, FILTER_VALIDATE_IP, $filter)) {
				self::$output[$fieldName] = $fieldValue;
			} else {
				throw new self::$customException($msg, $item['err_code']);
			}
		}, ['type' => $type]);
	}

	public function isInt(): Validator
	{
		return $this->addRule(function ($fieldName, $fieldValue, $item) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '不是整数';
			if (is_integer($fieldValue)) {
				self::$output[$fieldName] = intval($fieldValue);
			} else {
				throw new self::$customException($msg, $item['err_code']);
			}
		});
	}

	public function isFloat(int $decimalPlaces = null): Validator
	{
		return $this->addRule(function ($fieldName, $fieldValue, $item) use ($decimalPlaces) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '不是浮点数';

			// 尝试将字符串转换为浮点数
			$floatValue = filter_var($fieldValue, FILTER_VALIDATE_FLOAT);

			if ($floatValue === false) {
				throw new self::$customException($msg, $item['err_code']);
			}

			if ($decimalPlaces !== null) {
				// 将浮点数转换为字符串并分割小数部分
				$parts = explode('.', (string)$floatValue);
				if (isset($parts[1]) && strlen($parts[1]) > $decimalPlaces) {
					throw new self::$customException($msg, $item['err_code']);
				}
			}

			self::$output[$fieldName] = $floatValue;
		});
	}


	public function withRegex(string $pattern): Validator
	{
		return $this->addRule(function ($fieldName, $fieldValue, $item) use ($pattern) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '不符合指定的格式';
			if (preg_match($pattern, $fieldValue)) {
				self::$output[$fieldName] = $fieldValue;
			} else {
				throw new self::$customException($msg, $item['err_code']);
			}
		}, ['pattern' => $pattern]);
	}

	public function isBool(): Validator
	{
		return $this->addRule(function ($fieldName, $fieldValue, $item) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '不是布尔值';
			$result = filter_var($fieldValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
			if ($result === true || $result === false) {
				self::$output[$fieldName] = $result;
			} else {
				throw new self::$customException($msg, $item['err_code']);
			}
		});
	}

	public function cmpNumber($symbol, int|float $number): Validator
	{
		return $this->addRule(function ($fieldName, $fieldValue, $item) use ($symbol, $number) {
			$msg = $item['err_msg'] ?: '参数' . $fieldName . '的值:' . $fieldValue . $symbol . $number . ' 不成立';

			if (!is_numeric($fieldValue)) throw new self::$customException($msg, $item['err_code']);

			$res = match ($symbol) {
				'>' => $fieldValue > $number,
				'>=' => $fieldValue >= $number,
				'<' => $fieldValue < $number,
				'<=' => $fieldValue <= $number,
				'=' => $fieldValue == $number,
				'!=' => $fieldValue != $number,
				default => throw new self::$customException(
					'不支持的运算符: ' . $symbol,
					$item['err_code']
				),
			};
			if ($res) {
				self::$output[$fieldName] = floor($fieldValue) == $fieldValue ? intval($fieldValue) : floatval($fieldValue);
			} else {
				throw new self::$customException($msg, $item['err_code']);
			}
		}, ['symbol' => $symbol, 'number' => $number]);
	}


	public function ifExisted(): Validator
	{
		return $this->addRule(function ($fieldName, $fieldValue, $item) {
			if (self::$input[$fieldName] ?? false) {
				self::$output[$fieldName] = $fieldValue;
			}
		}, ['_function_name' => 'ifExisted']);
	}

	public function isJson($to_array = false): Validator
	{
		return $this->addRule(function ($fieldName, $fieldValue, $item) use ($to_array) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '不是JSON字符串';
			//判断是否是json字符串
			if (is_string($fieldValue) && is_array(json_decode($fieldValue, true))) {
				self::$output[$fieldName] = $to_array ? json_decode($fieldValue, true) : $fieldValue;
			} else {
				throw new self::$customException($msg, $item['err_code']);
			}
		}, ['to_array' => $to_array]);
	}


}