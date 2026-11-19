<?php

namespace Jeckleee\Tools;

use DateTime;
use LogicException;

class ValidationChain
{
	private array $fields = [];

	public function __construct(private array $input)
	{
	}

	public function field(string $fieldName): self
	{
		$this->fields[] = ['fieldName' => $fieldName];
		return $this;
	}

	private function currentFieldIndex(): int
	{
		$index = array_key_last($this->fields);
		if ($index === null) {
			throw new LogicException('请先使用 field() 声明字段');
		}
		return $index;
	}

	public function msg(?string $message = null, ?int $code = null): self
	{
		$index = $this->currentFieldIndex();
		$this->fields[$index]['err_msg'] = $message;
		$this->fields[$index]['err_code'] = $code;
		return $this;
	}

	public function get(?string $exceptionClass = null, ?int $errorCode = null, ?string $errorMode = null): mixed
	{
		$run = new ValidationContext($this->input, $exceptionClass, $errorCode, $errorMode);
		$output = $run->validate($this->fields);
		if (count($this->fields) === 1) {
			return $output[$this->fields[0]['fieldName']] ?? null;
		}
		return $output;
	}

	private function addRule(callable $function, array $additionalParams = []): self
	{
		$index = $this->currentFieldIndex();
		$this->fields[$index]['list'][] = ['function' => $function] + $additionalParams;
		return $this;
	}

	/**
	 * 校验: 要求字段必填
	 * @param $def
	 * @return $this
	 */
	public function required($def = null): self
	{
		return $this->addRule(static function ($fieldName, $fieldValue, $item, ValidationContext $run) use ($def) {
			$msg = $item['err_msg'] ?: '参数必填:' . $fieldName;
			if (isset($run->input[$fieldName]) && $run->input[$fieldName] !== '') {
				$run->output[$fieldName] = $fieldValue;
			} elseif ($def !== null) {
				$run->input[$fieldName] = $def;
				$run->output[$fieldName] = $def;
			} else {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}
		}, ['_function_name' => 'required', 'def' => $def]);
	}

	/**
	 * 校验: 去除字符串两端空白字符
	 * @return $this
	 */
	public function strTrim(): self
	{
		return $this->addRule(static function ($fieldName, $fieldValue, $item, ValidationContext $run) {
			$run->input[$fieldName] = trim($fieldValue);
			$run->output[$fieldName] = trim($fieldValue);
		});
	}

	/**
	 * 校验: 判断是否在指定范围内(含边界)
	 * @param int|float $min
	 * @param int|float $max
	 * @return $this
	 */
	public function betweenNumber(int|float $min, int|float $max): self
	{
		return $this->addRule(static function ($fieldName, $fieldValue, $item, ValidationContext $run) use ($min, $max) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '必须在' . $min . '~' . $max . '之间';
			if ($fieldValue >= $min && $fieldValue <= $max) {
				$run->output[$fieldName] = $fieldValue;
			} else {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}
		}, ['min' => $min, 'max' => $max]);
	}

	/**
	 * 校验: 判断是否在数组中
	 * @param $array
	 * @return $this
	 */
	public function inArray($array): self
	{
		return $this->addRule(static function ($fieldName, $fieldValue, $item, ValidationContext $run) use ($array) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '仅允许在(' . implode(',', $array) . ')中';
			if (in_array($fieldValue, $array)) {
				$run->output[$fieldName] = $fieldValue;
			} else {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}
		}, ['array' => $array]);
	}

	/**
	 * 校验: 判断是否在数组中之外
	 * @param $array
	 * @return $this
	 */
	public function notInArray($array): self
	{
		return $this->addRule(static function ($fieldName, $fieldValue, $item, ValidationContext $run) use ($array) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '不允许在(' . implode(',', $array) . ')中';
			if (!in_array($fieldValue, $array)) {
				$run->output[$fieldName] = $fieldValue;
			} else {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}
		}, ['array' => $array]);
	}

	/**
	 * 校验: 判断是否是数组
	 * @return $this
	 */
	public function isArray(): self
	{
		return $this->addRule(static function ($fieldName, $fieldValue, $item, ValidationContext $run) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '必须是一个数组';
			if (is_array($fieldValue)) {
				$run->output[$fieldName] = $fieldValue;
			} else {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}
		});
	}

	/**
	 * 校验: 判断是否是数字
	 * @return $this
	 */
	public function isNumber(): self
	{
		return $this->addRule(static function ($fieldName, $fieldValue, $item, ValidationContext $run) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '必须是数字';

			// 检查是否为数字（包括科学计数法）
			if (!is_numeric($fieldValue)) {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}

			// 处理科学计数法
			if (is_string($fieldValue) && (strpos($fieldValue, 'e') !== false || strpos($fieldValue, 'E') !== false)) {
				$floatValue = (float) $fieldValue;
				$run->output[$fieldName] = $floatValue;
				return;
			}

			// 区分整数和浮点数
			if (is_int($fieldValue)) {
				$run->output[$fieldName] = $fieldValue;
			} elseif (is_float($fieldValue)) {
				$run->output[$fieldName] = $fieldValue;
			} elseif (is_string($fieldValue)) {
				// 检查是否为整数字符串
				if (preg_match('/^-?\d+$/', $fieldValue)) {
					$run->output[$fieldName] = (int) $fieldValue;
				} else {
					// 检查是否为浮点数字符串
					if (preg_match('/^-?\d*\.\d+$/', $fieldValue)) {
						$run->output[$fieldName] = (float) $fieldValue;
					} else {
						// 其他数字格式（如科学计数法）
						$run->output[$fieldName] = (float) $fieldValue;
					}
				}
			} else {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}
		});
	}

	/**
	 * 校验: 判断字符串长度
	 * @param int $min
	 * @param int|null $max
	 * @return $this
	 */
	public function strLength(int $min = 1, int|null $max = null): self
	{
		return $this->addRule(static function ($fieldName, $fieldValue, $item, ValidationContext $run) use ($min, $max) {
			$max = $max === null ? $min : $max;
			$_msg = $min === $max ? '为' . $min . '个字符' : '在' . $min . '~' . $max . '个字符之间';
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '的长度必须' . $_msg;
			$length = mb_strlen($fieldValue, 'utf-8');
			if ($length >= $min && $length <= $max) {
				$run->output[$fieldName] = $fieldValue;
			} else {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}
		}, ['min' => $min, 'max' => $max]);
	}

	/**
	 * 校验: 判断是否是字母
	 * @return $this
	 */
	public function strAlpha(): self
	{
		return $this->addRule(static function ($fieldName, $fieldValue, $item, ValidationContext $run) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '只能由字母组成';
			if (preg_match('/^[a-zA-Z]+$/', $fieldValue)) {
				$run->output[$fieldName] = $fieldValue;
			} else {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}
		});
	}

	/**
	 * 校验: 判断是否是字母和数字的组合
	 * @param bool $type
	 * @return $this
	 */
	public function strAlphaNum(bool $type = false): self
	{
		return $this->addRule(static function ($fieldName, $fieldValue, $item, ValidationContext $run) use ($type) {
			$pattern = $type ? '/^(?=.*[a-zA-Z])(?=.*\d)[a-zA-Z\d]+$/' : '/^[a-zA-Z0-9]+$/';
			$defaultMsg = $type ? '参数:' . $fieldName . '必须同时包含字母和数字' : '参数:' . $fieldName . '由字母或数字组成';
			$msg = $item['err_msg'] ?: $defaultMsg;

			if (preg_match($pattern, $fieldValue)) {
				$run->output[$fieldName] = $fieldValue;
			} else {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}
		});
	}

	/**
	 * 校验: 判断是否是邮箱地址
	 * @return $this
	 */
	public function email(): self
	{
		return $this->addRule(static function ($fieldName, $fieldValue, $item, ValidationContext $run) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '不是一个合法的邮箱地址';
			if (filter_var($fieldValue, FILTER_VALIDATE_EMAIL)) {
				$run->output[$fieldName] = $fieldValue;
			} else {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}
		});
	}

	/**
	 * 校验: 判断是否是中国大陆手机号
	 * @return $this
	 */
	public function mobile(): self
	{
		return $this->addRule(static function ($fieldName, $fieldValue, $item, ValidationContext $run) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '不是一个合法的中国大陆手机号';
			$phoneRegex = '/^1[3-9]\d{9}$/';
			if (preg_match($phoneRegex, $fieldValue)) {
				$run->output[$fieldName] = $fieldValue;
			} else {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}
		});
	}

	/**
	 * 校验: 判断是否是时间字符串, 允许传入参数指定时间格式
	 * @param $format
	 * @return $this
	 */
	public function dateFormat($format): self
	{
		return $this->addRule(static function ($fieldName, $fieldValue, $item, ValidationContext $run) use ($format) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '不是一个合法的时间字符串.(例如:' . date($format) . ')';
			$d = DateTime::createFromFormat($format, $fieldValue);
			if ($d && $d->format($format) === $fieldValue) {
				$run->output[$fieldName] = $fieldValue;
			} else {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}
		}, ['format' => $format]);
	}

	/**
	 * 校验: 判断是否是身份证号
	 * @return $this
	 */
	public function idCard(): self
	{
		return $this->addRule(static function ($fieldName, $fieldValue, $item, ValidationContext $run) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '不是一个合法的身份证号';
			// 身份证号长度为 15 位或 18 位
			$pattern = '/^(?:\d{15}|\d{17}[\dxX])$/';
			if (!preg_match($pattern, $fieldValue)) {
				throw new $run->exceptionClass($msg, $item['err_code']);
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
				$sum += $weights[$i] * (int) $fieldValue[$i];
			}
			$checkCodeIndex = $sum % 11;

			// 验证校验位
			$lastChar = strtoupper($fieldValue[17]);
			if ($lastChar === $checkCodes[$checkCodeIndex]) {
				$run->output[$fieldName] = $fieldValue;
			} else {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}
		});
	}

	/**
	 * 校验: 判断是否是url
	 * @return $this
	 */
	public function isUrl(): self
	{
		return $this->addRule(static function ($fieldName, $fieldValue, $item, ValidationContext $run) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '不是一个合法的url';
			if (filter_var($fieldValue, FILTER_VALIDATE_URL)) {
				$run->output[$fieldName] = $fieldValue;
			} else {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}
		});
	}

	/**
	 * 校验: 判断是否是ip地址
	 * @param string $type ipv4|ipv6
	 * @return $this
	 */
	public function isIp(string $type = 'ipv4'): self
	{
		return $this->addRule(static function ($fieldName, $fieldValue, $item, ValidationContext $run) use ($type) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '不是一个合法的ip地址';
			$filter = $type === 'ipv6' ? FILTER_FLAG_IPV6 : FILTER_FLAG_IPV4;
			if (filter_var($fieldValue, FILTER_VALIDATE_IP, $filter)) {
				$run->output[$fieldName] = $fieldValue;
			} else {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}
		}, ['type' => $type]);
	}

	/**
	 * 校验: 判断是否是整数或整数字符串
	 * @return $this
	 */
	public function isInt(): self
	{
		return $this->addRule(static function ($fieldName, $fieldValue, $item, ValidationContext $run) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '不是整数';
			// 支持整数字符串和整数类型
			if ((is_int($fieldValue) || (is_string($fieldValue) && preg_match('/^-?\\d+$/', $fieldValue)))) {
				$run->output[$fieldName] = intval($fieldValue);
			} else {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}
		});
	}

	/**
	 * 校验: 判断是否是浮点数, 允许传入参数保留小数位
	 * @param int|null $decimalPlaces 保留小数位
	 * @return $this
	 */
	public function isFloat(int|null $decimalPlaces = null): self
	{
		return $this->addRule(static function ($fieldName, $fieldValue, $item, ValidationContext $run) use ($decimalPlaces) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '不是浮点数';

			// 尝试将字符串转换为浮点数
			$floatValue = filter_var($fieldValue, FILTER_VALIDATE_FLOAT);

			if ($floatValue === false) {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}

			// 检查小数位数限制
			if ($decimalPlaces !== null) {
				// 将浮点数转换为字符串，避免科学计数法
				$stringValue = number_format($floatValue, $decimalPlaces, '.', '');

				// 移除末尾的0
				$stringValue = rtrim(rtrim($stringValue, '0'), '.');

				// 计算实际小数位数
				$parts = explode('.', $stringValue);
				$actualDecimalPlaces = isset($parts[1]) ? strlen($parts[1]) : 0;

				if ($actualDecimalPlaces > $decimalPlaces) {
					$msg = $item['err_msg'] ?: '参数:' . $fieldName . '小数位数不能超过' . $decimalPlaces . '位';
					throw new $run->exceptionClass($msg, $item['err_code']);
				}
			}

			$run->output[$fieldName] = $floatValue;
		});
	}


	/**
	 * 正则表达式校验: 判断参数是否符合指定的正则表达式
	 * @param string $pattern 正则表达式
	 * @return $this
	 */
	public function withRegex(string $pattern): self
	{
		return $this->addRule(static function ($fieldName, $fieldValue, $item, ValidationContext $run) use ($pattern) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '不符合指定的格式';
			if (preg_match($pattern, $fieldValue)) {
				$run->output[$fieldName] = $fieldValue;
			} else {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}
		}, ['pattern' => $pattern]);
	}

	/**
	 * 校验: 判断是否是布尔值
	 * @return $this
	 */
	public function isBool(): self
	{
		return $this->addRule(static function ($fieldName, $fieldValue, $item, ValidationContext $run) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '不是布尔值';
			$result = filter_var($fieldValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
			if ($result === true || $result === false) {
				$run->output[$fieldName] = $result;
			} else {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}
		});
	}

	/**
	 * 比较数字: 比较数字与指定值的大小关系
	 * @param $symbol > >= < <= = !=
	 * @param int|float $number
	 * @return $this
	 */
	public function cmpNumber($symbol, int|float $number): self
	{
		return $this->addRule(static function ($fieldName, $fieldValue, $item, ValidationContext $run) use ($symbol, $number) {
			$msg = $item['err_msg'] ?: '参数' . $fieldName . '的值:' . $fieldValue . $symbol . $number . ' 不成立';

			if (!is_numeric($fieldValue))
				throw new $run->exceptionClass($msg, $item['err_code']);

			$res = match ($symbol) {
				'>' => $fieldValue > $number,
				'>=' => $fieldValue >= $number,
				'<' => $fieldValue < $number,
				'<=' => $fieldValue <= $number,
				'=' => $fieldValue == $number,
				'!=' => $fieldValue != $number,
				default => throw new $run->exceptionClass(
					'不支持的运算符: ' . $symbol,
					$item['err_code']
				),
			};
			if ($res) {
				$run->output[$fieldName] = floor($fieldValue) == $fieldValue ? intval($fieldValue) : floatval($fieldValue);
			} else {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}
		}, ['symbol' => $symbol, 'number' => $number]);
	}


	/**
	 * 有条件的校验: 判断字段是否存在, 如果存在则验证,否则忽略该字段
	 * @return $this
	 */
	public function ifExisted(): self
	{
		return $this->addRule(static function ($fieldName, $fieldValue, $item, ValidationContext $run) {
			if (isset($run->input[$fieldName])) {
				$run->output[$fieldName] = $fieldValue;
			}
		}, ['_function_name' => 'ifExisted']);
	}

	/**
	 * 校验: 判断是否是json字符串,允许传入参数将其转为Array
	 * @param bool $to_array
	 * @return $this
	 */
	public function isJson(bool $to_array = false): self
	{
		return $this->addRule(static function ($fieldName, $fieldValue, $item, ValidationContext $run) use ($to_array) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '不是JSON字符串';

			// 检查是否为字符串
			if (!is_string($fieldValue)) {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}

			// 尝试解析JSON
			$decoded = json_decode($fieldValue, true);

			// 检查JSON解析是否成功
			if (json_last_error() !== JSON_ERROR_NONE) {
				$errorMsg = $item['err_msg'] ?: '参数:' . $fieldName . 'JSON格式错误: ' . json_last_error_msg();
				throw new $run->exceptionClass($errorMsg, $item['err_code']);
			}

			// 检查解析结果是否为数组或对象
			if (!is_array($decoded) && !is_object($decoded)) {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}

			// 返回结果
			$run->output[$fieldName] = $to_array ? $decoded : $fieldValue;
		}, ['to_array' => $to_array]);
	}

	/**
	 * 字符串开头校验:要求字符串以指定的字符串开头
	 * @param string $with
	 * @return $this
	 */
	public function strStartWith(string $with): self
	{
		return $this->addRule(static function ($fieldName, $fieldValue, $item, ValidationContext $run) use ($with) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '不是以' . $with . '开头';
			if (str_starts_with($fieldValue, $with)) {
				$run->output[$fieldName] = $fieldValue;
			} else {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}
		}, ['with' => $with]);
	}

	/**
	 * 校验:要求字符串以指定的字符串结尾
	 * @param string $with
	 * @return $this
	 */
	public function strEndWith(string $with): self
	{
		return $this->addRule(static function ($fieldName, $fieldValue, $item, ValidationContext $run) use ($with) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '不是以' . $with . '结尾';
			if (str_ends_with($fieldValue, $with)) {
				$run->output[$fieldName] = $fieldValue;
			} else {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}
		}, ['with' => $with]);
	}

	/**
	 * 自定义校验: 传入一个函数, 函数返回true则验证通过, 否则验证失败
	 * @param callable $function
	 * @return $this
	 */
	public function func(callable $function): self
	{
		return $this->addRule(static function ($fieldName, $fieldValue, $item, ValidationContext $run) use ($function) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '不符合指定的格式';
			$result = $function($fieldValue);
			if ($result === true) {
				$run->output[$fieldName] = $fieldValue;
			} else {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}
		});
	}

	/**
	 * 文件校验：判断是否为文件、校验大小和扩展名,仅检查文件是否符合要求
	 * 兼容格式：
	 * 1. 原始$_FILES格式: 使用方式 ->field('avatar')->file($_FILES)->get()
	 * 2. Laravel格式: Illuminate\Http\UploadedFile对象   使用方式 ->field('avatar')->file(request()->file())->get()
	 * 3. Webman格式: support\UploadFile对象   使用方式 ->field('avatar')->file(request()->file())->get()
	 * 4. ThinkPHP8.0格式: think\file\UploadedFile对象   使用方式 ->field('avatar')->file(request()->file())->get()
	 * @param array $ext 允许的扩展名（如['jpg','png']），[]则不限制
	 * @param int|null $maxSize_Kb 最大字节数，默认500KB
	 * @return $this
	 */
	public function file(array|object|string $file, array $ext = [], int $maxSize_Kb = 500): self
	{
		return $this->addRule(static function ($fieldName, $fieldValue, $item, ValidationContext $run) use ($file, $maxSize_Kb, $ext) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '不是有效的文件';
			$sizeMsg = $item['err_msg'] ?: '参数:' . $fieldName . '文件大小超出限制';
			$typeMsg = $item['err_msg'] ?: '参数:' . $fieldName . '文件类型不被允许';
			$isValid = false;
			$size = null;
			$fileExt = null;
			$originalName = null;
			$fileObj = $file[$fieldName] ?? $file;
			if (!$fileObj) {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}
			// 1. 原生 $_FILES 格式
			if (is_array($fileObj) && isset($fileObj['tmp_name'])) {
				if (is_uploaded_file($fileObj['tmp_name'])) {
					$isValid = true;
					$size = $fileObj['size'] ?? null;
					$originalName = $fileObj['name'] ?? null;
					$fileExt = $originalName ? strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) : null;
				}
			}
			// 2. Laravel: Illuminate\Http\UploadedFile
			elseif (is_object($fileObj) && strpos(get_class($fileObj), 'Illuminate') !== false) {
				if (method_exists($fileObj, 'isValid') && $fileObj->isValid()) {
					$isValid = true;
					$size = method_exists($fileObj, 'getSize') ? $fileObj->getSize() : null;
					$originalName = method_exists($fileObj, 'getClientOriginalName') ? $fileObj->getClientOriginalName() : null;
					$fileExt = $originalName ? strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) : null;
				}
			}
			// 3. ThinkPHP8.0: think\file\UploadedFile
			elseif (is_object($fileObj) && strpos(get_class($fileObj), 'think\\file\\UploadedFile') !== false) {
				if (method_exists($fileObj, 'isValid') && $fileObj->isValid()) {
					$isValid = true;
					$size = method_exists($fileObj, 'getSize') ? $fileObj->getSize() : null;
					$originalName = method_exists($fileObj, 'getOriginalName') ? $fileObj->getOriginalName() : null;
					$fileExt = $originalName ? strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) : null;
				}
			}
			// 4. Webman: support\UploadFile
			elseif (is_object($fileObj) && (strpos(get_class($fileObj), 'support\\UploadFile') !== false || strpos(get_class($fileObj), 'Webman\\') !== false)) {
				if (method_exists($fileObj, 'isValid') && $fileObj->isValid()) {
					$isValid = true;
					$size = method_exists($fileObj, 'getSize') ? $fileObj->getSize() : null;
					$originalName = method_exists($fileObj, 'getUploadName') ? $fileObj->getUploadName() : null;
					$fileExt = $originalName ? strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) : null;
				}
			} else {
				throw new $run->exceptionClass('未知的文件处理方式', $item['err_code']);
			}

			if (!$isValid) {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}
			if ($maxSize_Kb !== null && ($size === null || $size > $maxSize_Kb * 1024)) {
				throw new $run->exceptionClass($sizeMsg, $item['err_code']);
			}
			if ($ext && ($fileExt === null || !in_array($fileExt, $ext))) {
				throw new $run->exceptionClass($typeMsg, $item['err_code']);
			}
			$run->output[$fieldName] = $originalName;
		}, ['maxSize_Kb' => $maxSize_Kb, 'ext' => $ext]);
	}

	/**
	 * 条件必填校验: 当指定字段存在且不为空时，当前字段必填
	 * @param string $field 依赖的字段名
	 * @return $this
	 */
	public function requiredWith(string $field): self
	{
		return $this->addRule(static function ($fieldName, $fieldValue, $item, ValidationContext $run) use ($field) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '在字段' . $field . '存在时必须填写';
			$dependentValue = $run->input[$field] ?? null;
			if ($dependentValue !== null && $dependentValue !== '') {
				if (isset($run->input[$fieldName]) && $run->input[$fieldName] !== '') {
					$run->output[$fieldName] = $fieldValue;
				} else {
					throw new $run->exceptionClass($msg, $item['err_code']);
				}
			} else {
				// 依赖字段不存在或为空，跳过当前字段验证
				if (isset($run->input[$fieldName])) {
					$run->output[$fieldName] = $fieldValue;
				}
			}
		}, ['dependent_field' => $field]);
	}

	/**
	 * 条件必填校验: 当指定字段不存在或为空时，当前字段必填
	 * @param string $field 依赖的字段名
	 * @return $this
	 */
	public function requiredWithout(string $field): self
	{
		return $this->addRule(static function ($fieldName, $fieldValue, $item, ValidationContext $run) use ($field) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '在字段' . $field . '不存在时必须填写';
			$dependentValue = $run->input[$field] ?? null;
			if ($dependentValue === null || $dependentValue === '') {
				if (isset($run->input[$fieldName]) && $run->input[$fieldName] !== '') {
					$run->output[$fieldName] = $fieldValue;
				} else {
					throw new $run->exceptionClass($msg, $item['err_code']);
				}
			} else {
				// 依赖字段存在且不为空，跳过当前字段验证
				if (isset($run->input[$fieldName])) {
					$run->output[$fieldName] = $fieldValue;
				}
			}
		}, ['dependent_field' => $field]);
	}

	/**
	 * 字段一致性校验: 当前字段值必须与指定字段值相同
	 * @param string $field 比较的字段名
	 * @return $this
	 */
	public function same(string $field): self
	{
		return $this->addRule(static function ($fieldName, $fieldValue, $item, ValidationContext $run) use ($field) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '与字段' . $field . '的值不一致';
			$compareValue = $run->input[$field] ?? null;
			if ($fieldValue === $compareValue) {
				$run->output[$fieldName] = $fieldValue;
			} else {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}
		}, ['compare_field' => $field]);
	}

	/**
	 * 字段差异性校验: 当前字段值必须与指定字段值不同
	 * @param string $field 比较的字段名
	 * @return $this
	 */
	public function different(string $field): self
	{
		return $this->addRule(static function ($fieldName, $fieldValue, $item, ValidationContext $run) use ($field) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '与字段' . $field . '的值不能相同';
			$compareValue = $run->input[$field] ?? null;
			if ($fieldValue !== $compareValue) {
				$run->output[$fieldName] = $fieldValue;
			} else {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}
		}, ['compare_field' => $field]);
	}

	/**
	 * 校验: 判断是否是Base64编码字符串
	 * @return $this
	 */
	public function isBase64(): self
	{
		return $this->addRule(static function ($fieldName, $fieldValue, $item, ValidationContext $run) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '不是有效的Base64编码字符串';

			// 检查是否为字符串
			if (!is_string($fieldValue)) {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}

			// 移除可能的换行符和空格
			$cleanedValue = preg_replace('/\s+/', '', $fieldValue);

			// 空字符串不是有效的Base64
			if ($cleanedValue === '') {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}

			// 检查Base64格式 - 更严格的正则表达式
			if (preg_match('/^[A-Za-z0-9+\/]+={0,2}$/', $cleanedValue)) {
				// 检查长度是否为4的倍数
				if (strlen($cleanedValue) % 4 === 0) {
					// 检查填充字符的位置是否正确
					$paddingCount = substr_count($cleanedValue, '=');
					if ($paddingCount === 0 || $paddingCount === 1 || $paddingCount === 2) {
						// 检查填充字符是否在正确位置
						if ($paddingCount === 0 || strpos($cleanedValue, '=') === strlen($cleanedValue) - $paddingCount) {
							// 尝试解码验证
							$decoded = base64_decode($cleanedValue, true);
							if ($decoded !== false) {
								// 重新编码验证是否一致
								$reEncoded = base64_encode($decoded);
								if ($reEncoded === $cleanedValue) {
									$run->output[$fieldName] = $fieldValue;
								} else {
									throw new $run->exceptionClass($msg, $item['err_code']);
								}
							} else {
								throw new $run->exceptionClass($msg, $item['err_code']);
							}
						} else {
							throw new $run->exceptionClass($msg, $item['err_code']);
						}
					} else {
						throw new $run->exceptionClass($msg, $item['err_code']);
					}
				} else {
					throw new $run->exceptionClass($msg, $item['err_code']);
				}
			} else {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}
		});
	}

	/**
	 * 字符串转为小写
	 * @return $this
	 */
	public function strLowercase(): self
	{
		return $this->addRule(static function ($fieldName, $fieldValue, $item, ValidationContext $run) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '不是字符串，无法转为小写';
			if (is_string($fieldValue)) {
				$run->input[$fieldName] = mb_strtolower($fieldValue, 'UTF-8');
				$run->output[$fieldName] = $run->input[$fieldName];
			} else {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}
		});
	}

	/**
	 * 字符串转为大写
	 * @return $this
	 */
	public function strUppercase(): self
	{
		return $this->addRule(static function ($fieldName, $fieldValue, $item, ValidationContext $run) {
			$msg = $item['err_msg'] ?: '参数:' . $fieldName . '不是字符串，无法转为大写';
			if (is_string($fieldValue)) {
				$run->input[$fieldName] = mb_strtoupper($fieldValue, 'UTF-8');
				$run->output[$fieldName] = $run->input[$fieldName];
			} else {
				throw new $run->exceptionClass($msg, $item['err_code']);
			}
		});
	}
}
