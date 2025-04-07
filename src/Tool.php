<?php

namespace Jeckleee\Tools;

use DateTime;
use Exception;


class Tool
{


	private static function getConfig(): array
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
			$config = config('plugin.jeckleee.tools.app', $config);
		}
		return $config;
	}


	/**
	 * 二维数组根据字段进行绑定到唯一键
	 * @params array $array 需要绑定的数组
	 * @params string $key 绑定的键
	 * @param array $array
	 * @param $key
	 * @return array
	 */
	public static function arrayBindKey(array $array, $key): array
	{
		$newArray = [];
		if (count($array) > 0) {
			foreach ($array as $item) {
				if (is_array($item) && array_key_exists($key, $item)) {
					$newArray[$item[$key]] = $item;
				}
			}
		}
		return $newArray;
	}

	/**
	 * 二维数组根据字段进行排序
	 * @params array $array 需要排序的数组
	 * @params string $field 排序的字段
	 * @params string $sort 排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
	 */
	public static function arraySequence(array $array, string $field, string $sort = 'SORT_DESC'): array
	{
		$config = self::getConfig();
		if ($array == []) return [];
		// 验证排序方式是否有效
		if (!defined($sort)) {
			throw new $config['exception']("排序方式 '$sort' 无效。", $config['exception_code']);
		}
		// 验证数组中是否存在指定的字段
		if (!array_key_exists($field, $array[0])) {
			throw new  $config['exception']("字段 '$field' 不存在于数组中。", $config['exception_code']);
		}
		// 初始化用于排序的数组
		$arrSort = array();
		foreach ($array as $uniqid => $row) {
			// 如果数组元素不是数组，则跳过该元素
			if (!is_array($row)) {
				continue;
			}
			foreach ($row as $key => $value) {
				$arrSort[$key][$uniqid] = $value;
			}
		}

		// 如果指定的字段不存在于排序数组中，抛出异常
		if (!array_key_exists($field, $arrSort)) {
			throw new  $config['exception']("字段 '$field' 在数组中不存在，无法进行排序。");
		}
		// 使用array_multisort进行排序
		array_multisort($arrSort[$field], constant($sort), $array);

		return $array;
	}

	/**
	 * 生成树形结构
	 * @param array $list
	 * @param string $union_field 唯一标识字段名
	 * @param string $parent_field 父级字段名
	 * @param string $children_field 自定义子级字段名,比如children/son等
	 * @return array
	 */
	public static function generateTree(array $list, string $union_field = 'id', string $parent_field = 'p_id', string $children_field = 'children'): array
	{
		$data = array();
		foreach ($list as $item) {
			if (isset($list[$item[$parent_field]])) {
				$list[$item[$parent_field]][$children_field][] =& $list[$item[$union_field]];
			} else {
				$data[] =& $list[$item[$union_field]];
			}
		}
		return $data;
	}

	/**
	 * 生成随机字符串
	 * @param $length
	 * @return string
	 * @throws \Random\RandomException
	 */
	public static function getRandomString($length): string
	{
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_';
		$randomString = '';
		$max = strlen($characters) - 1;
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[random_int(0, $max)];
		}
		return $randomString;
	}


	/**
	 * 计算两个日期之间的天数差
	 * @param string $date1
	 * @param string $date2
	 * @return int
	 * @throws \DateMalformedStringException
	 */
	public static function diffDateDays(string $date1, string $date2): int
	{
		// 将日期字符串转换为 DateTime 对象
		$dateTime1 = new DateTime($date1);
		$dateTime2 = new DateTime($date2);

		// 计算两个日期之间的差值
		$interval = $dateTime1->diff($dateTime2);

		// 返回相差的天数
		return $interval->days;
	}

	/**
	 * 字符串脱敏
	 * @param $inputString string 需要脱敏的字符串
	 * @param $startLength int 字符串开头保留的长度
	 * @param $endLength  int 字符串末尾保留的长度
	 * @param $maskChar string 脱敏字符， 默认为 *
	 * @param $maxLength int|null 最大长度，如果超过最大长度，则减少$maskChar的数量，默认为 null，表示不 设置最大长度
	 * @return string
	 */
	public static function desensitizeString(string $inputString, int $startLength, int $endLength, string $maskChar = '*', int $maxLength = null): string
	{
		// 获取字符串的长度
		$length = strlen($inputString);

		// 如果字符串长度小于等于开头和结尾保留的长度之和，则直接返回原字符串
		if ($length <= ($startLength + $endLength)) {
			return $inputString;
		}

		// 截取开头保留的部分
		$start = substr($inputString, 0, $startLength);

		// 截取结尾保留的部分
		$end = substr($inputString, -$endLength);

		// 计算需要替换的部分的长度
		$maskLength = $length - $startLength - $endLength;

		// 如果设置了最大长度，并且脱敏后的字符串长度超过最大长度
		if ($maxLength !== null && ($startLength + $endLength + $maskLength) > $maxLength) {
			// 计算允许的 * 号的最大数量
			$allowedMaskLength = $maxLength - $startLength - $endLength;
			// 如果允许的 * 号数量小于 0，则只保留开头和结尾的部分
			if ($allowedMaskLength < 0) {
				return $start . $end;
			}
			// 减少 * 号的数量
			$maskLength = $allowedMaskLength;
		}

		// 生成替换字符串
		$mask = str_repeat($maskChar, $maskLength);

		// 拼接并返回脱敏后的字符串
		return $start . $mask . $end;
	}

	/**
	 * 生成uuid
	 * @return string
	 */
	public static function generateUUID(): string
	{
		return sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// 32 bits for "time_low"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),

			// 16 bits for "time_mid"
			mt_rand(0, 0xffff),

			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand(0, 0x0fff) | 0x4000,

			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand(0, 0x3fff) | 0x8000,

			// 48 bits for "node"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
	}
}