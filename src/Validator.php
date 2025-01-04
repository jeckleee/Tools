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
	public static array $showAllRules = [
		'required' => '字段必填,可设置一个默认值',
		'stringTrim' => '去除字段两端的空格、制表符、换行符等',
		'betweenNumber' => '字段的值必须在某个区间',
		'inArray' => '字段的值必须在数组中',
		'isArray' => '字段的值必须是数组',
		'isNumber' => '字段的值必须是数字(int or float)',
		'stringLength' => '字段的值知必须指定范围的长度',
		'isEmail' => '字段的值必须是邮箱',
		'isMobile' => '字段的值必须是手机号',
		'isDateTimeInFormat' => '字段的值必须是指定格式的时间字符串(Ymd-His等)',
		'isIdCard' => '字段的值必须是身份证号',
		'isUrl' => '字段的值必须是网址',
		'isIp' => '字段的值必须是IP地址(ipv4 or ipv6)',
		'isInt' => '字段的值必须是整数',
		'withRegex' => '使用正则表达式验证字段',
		'isBool' => '字段的值必须是布尔值,为 "1", "true", "on" and "yes" 返回 TRUE,为 "0", "false", "off" and "no" 返回 FALSE',
		'cmpNumber' => '对字段进行比较,是betweenNumber方法的补充,允许的符号:>,<,>=,<=,!=,=',
	];


	public static function array(array $input, $rules, $customException = null, $err_code = null): array
	{
		self::$customException = $customException ?: Exception::class;
		self::$err_code = $err_code ?: self::$err_code;
		self::$input = $input;
		self::$output = [];
		self::applyRules($rules);
		return self::$output;
	}

	public static function one(array $input, $rules, $customException = null, $err_code = null)
	{
		self::$customException = $customException ?: Exception::class;
		self::$err_code = $err_code ?: self::$err_code;
		self::$input = $input;
		self::$output = [];
		self::applyRules($rules);
		return reset(self::$output);
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
		foreach ($rules as $rule) {
			if (!is_array($rule)) throw new self::$customException('请在规则的链式结束后调用->verify()方法', self::$err_code);
			if ($rule['list'] ?? false) {

				foreach ($rule['list'] as $item) {
                    if (isset($item['function_name'])&&$item['function_name']==='ifExisted'&&!isset(self::$input[$rule['fieldName']])){
                        break;
                    }
					$function = $item['function'];
					$fieldName = $rule['fieldName'];
                    $fieldValue = self::$input[$rule['fieldName']] ?? null;
					$item['err_msg'] = $rule['err_msg'] ?? '';
					$item['err_code'] = $rule['err_code'] ?: self::$err_code;
					$function($fieldName, $fieldValue, $item); // 调用闭包
				}
			} else {
				//没有什任何验证规则时
				self::$output[$rule['fieldName']] = self::$input[$rule['fieldName']] ?? null;
			}

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
			if ($fieldValue ?? null) {
				self::$output[$fieldName] = $fieldValue;
			} elseif ($defaultValue !== null) {
				self::$input[$fieldName] = $defaultValue;
				self::$output[$fieldName] = $defaultValue;
			} else {
				throw new self::$customException($msg, $item['err_code']);
			}
		});
	}

	public function stringTrim(): Validator
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

	public function stringLength($min = 1, $max = 20): Validator
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
			$idCardRegex = '/(^[1-9]\d{5}(18|19|([23]\d))\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$)|(^[1-9]\d{5}\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}$)/';
			if (preg_match($idCardRegex, $fieldValue)) {
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
            $result=filter_var($fieldValue, FILTER_VALIDATE_BOOLEAN,FILTER_NULL_ON_FAILURE);
			if ($result===true||$result===false) {
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
            if (self::$input[$fieldName]??false) {
                self::$output[$fieldName] = $fieldValue;
            }
        }, ['function_name'=>'ifExisted']);
    }
}