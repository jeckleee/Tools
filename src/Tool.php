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
			$config = config('config.plugin.jeckleee.tools.app', $config);
		}
		return $config;
	}
	
	
	/**
	 * @param $phone
	 * @return bool
	 */
	public static function checkPhone($phone): bool
	{
		$phoneRegex = '/^1[3-9]\d{9}$/';
		if (preg_match($phoneRegex, $phone)) {
			return true;
		}
		return false;
	}
	
	public static function checkEmail($email): bool
	{
		if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return true;
		}
		return false;
	}
	
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
	 * @param array $items
	 * @param string $union_field 唯一标识字段名
	 * @param string $parent_field 父级字段名
	 * @param string $children_field 自定义子级字段名,比如children/son等
	 * @return array
	 */
	public static function generateTree(array $items, string $union_field = 'id', string $parent_field = 'p_id', string $children_field = 'children'): array
	{
		$data = array();
		foreach ($items as $item) {
			if (isset($items[$item[$parent_field]])) {
				$items[$item[$parent_field]][$children_field][] =& $items[$item[$union_field]];
			} else {
				$data[] =& $items[$item[$union_field]];
			}
		}
		return $data;
	}
	
	
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
	
	
	//判断两个日期相差几天
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
	
}