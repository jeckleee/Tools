<?php

namespace Jeckleee\Tools;

use DateTime;
use Exception;
use Throwable;


class Tool
{

	/**
	 * 计时器存储
	 * @var array<string, float>
	 */
	private static array $timeit = [];

	/**
	 * 显示所有公开访问的函数,私有方法不出现在这里
	 * @return array
	 */
	public static function showAllFunction(): array
	{
		return [
			// 数组操作相关
			'arrayBindKey' => '二维数组根据字段绑定到唯一键',
			'arraySequence' => '二维数组根据字段进行排序',
			'arrayGroupBy' => '按键分组',
			'arrayUniqueBy' => '按键去重(稳定去重，保留首次出现)',
			'arrayChunkFixed' => '定长分块',
			'arrayPartition' => '二分分组：按谓词拆分为 [匹配, 不匹配]',

			// 树形结构操作
			'generateTree' => '生成树形结构',
			'flattenTree' => '扁平化树',
			'findInTree' => '在树中查找符合谓词的节点',
			'pathInTree' => '查找树中节点路径',
			'pathInFlat' => '从扁平数组中获取节点路径',

			// 字符串处理
			'getRandomString' => '生成随机字符串',
			'maskSecret' => '字符串脱敏（支持中文字符）',
			'humanizeDiff' => '人性化时间差，如：刚刚、3分钟前、2小时前、5天前',
			'humanBytes' => '人类可读的字节展示，如 1.23 MB',
			'buildQuery' => '构建稳定排序的查询字符串（RFC3986）',

			// UUID生成
			'generateUUID' => '生成不安全的uuid',
			'uuidV4' => '安全 UUID v4',
			'uuidV7' => 'UUID v7 (时间有序)',

			// 随机数生成
			'randomInt' => '生成安全随机整数',
			'randomFloat' => '生成随机浮点数（含最小值，含最大值）',

			// 日期时间
			'diffDateDays' => '计算两个日期之间的天数差',

			// 工具函数
			'retry' => '重试执行',
		];
	}


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
		if (\function_exists('config')) {
			$config = \call_user_func('config', 'plugin.jeckleee.tools.app', $config);
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
				$list[$item[$parent_field]][$children_field][] = &$list[$item[$union_field]];
			} else {
				$data[] = &$list[$item[$union_field]];
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
	 * 字符串脱敏（支持中文字符）
	 * @param $inputString string 需要脱敏的字符串
	 * @param $startLength int 字符串开头保留的长度（字符数，非字节数）
	 * @param $endLength  int 字符串末尾保留的长度（字符数，非字节数）
	 * @param $maskChar string 脱敏字符， 默认为 *
	 * @param $maxLength int|null 最大长度（字符数），如果超过最大长度，则减少$maskChar的数量，默认为 null，表示不设置最大长度
	 * @return string
	 */
	public static function maskSecret(string $inputString, int $startLength, int $endLength, string $maskChar = '*', int|null $maxLength = null): string
	{
		// 获取字符串的长度（字符数，支持多字节字符）
		$length = mb_strlen($inputString, 'UTF-8');

		// 如果字符串长度小于等于开头和结尾保留的长度之和，则直接返回原字符串
		if ($length <= ($startLength + $endLength)) {
			return $inputString;
		}

		// 截取开头保留的部分（使用多字节安全函数）
		$start = mb_substr($inputString, 0, $startLength, 'UTF-8');

		// 截取结尾保留的部分（使用多字节安全函数）
		$end = mb_substr($inputString, -$endLength, null, 'UTF-8');

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
	 * 生成不安全的uuid
	 * @return string
	 */
	public static function generateUUID(): string
	{
		return sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// 32 bits for "time_low"
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),

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
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff)
		);
	}



	/**
	 * 按键分组
	 * @param array $array
	 * @param string|callable $key 键名或回调 (fn($item): string|int)
	 * @return array
	 */
	public static function arrayGroupBy(array $array, string|callable $key): array
	{
		$groups = [];
		foreach ($array as $item) {
			$groupKey = is_callable($key) ? $key($item) : (is_array($item) && array_key_exists($key, $item) ? $item[$key] : null);
			$groupKey = (string)$groupKey;
			$groups[$groupKey][] = $item;
		}
		return $groups;
	}

	/**
	 * 按键去重(稳定去重，保留首次出现)
	 * @param array $array
	 * @param string|callable $key 键名或回调 (fn($item): string|int)
	 * @return array
	 */
	public static function arrayUniqueBy(array $array, string|callable $key): array
	{
		$seen = [];
		$result = [];
		foreach ($array as $item) {
			$k = is_callable($key) ? $key($item) : (is_array($item) && array_key_exists($key, $item) ? $item[$key] : null);
			$k = (string)$k;
			if (!array_key_exists($k, $seen)) {
				$seen[$k] = true;
				$result[] = $item;
			}
		}
		return $result;
	}

	/**
	 * 定长分块
	 * @param array $array
	 * @param int $size
	 * @return array
	 */
	public static function arrayChunkFixed(array $array, int $size): array
	{
		if ($size <= 0) return $array;
		return array_chunk($array, $size);
	}

	/**
	 * 二分分组：按谓词拆分为 [匹配, 不匹配]
	 * @param array $array
	 * @param callable $predicate fn($item, $index): bool
	 * @return array{0: array, 1: array}
	 */
	public static function arrayPartition(array $array, callable $predicate): array
	{
		$truthy = [];
		$falsy = [];
		foreach ($array as $i => $item) {
			if ($predicate($item, $i)) {
				$truthy[] = $item;
			} else {
				$falsy[] = $item;
			}
		}
		return [$truthy, $falsy];
	}

	/**
	 * 人性化时间差，如：刚刚、3分钟前、2小时前、5天前
	 * @param DateTime|string $datetime
	 * @return string
	 */
	public static function humanizeDiff(DateTime|string $datetime): string
	{
		$now = new DateTime('now');
		$dt = $datetime instanceof DateTime ? $datetime : new DateTime($datetime);
		$diffSeconds = (int)($now->format('U') - $dt->format('U'));
		if ($diffSeconds <= 5) return '刚刚';
		if ($diffSeconds < 60) return $diffSeconds . '秒前';
		$minutes = intdiv($diffSeconds, 60);
		if ($minutes < 60) return $minutes . '分钟前';
		$hours = intdiv($minutes, 60);
		if ($hours < 24) return $hours . '小时前';
		$days = intdiv($hours, 24);
		if ($days < 30) return $days . '天前';
		return $dt->format('Y-m-d H:i');
	}

	/**
	 * 安全 UUID v4
	 * @return string
	 * @throws \Exception
	 */
	public static function uuidV4(): string
	{
		$data = random_bytes(16);
		$data[6] = chr(ord($data[6]) & 0x0f | 0x40); // version 4
		$data[8] = chr(ord($data[8]) & 0x3f | 0x80); // variant RFC 4122
		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}

	/**
	 * UUID v7 (时间有序)
	 * @return string
	 * @throws \Exception
	 */
	public static function uuidV7(): string
	{
		$ms = (int) floor(microtime(true) * 1000);
		$timeHex = str_pad(dechex($ms), 12, '0', STR_PAD_LEFT); // 48 bits
		$timeBin = hex2bin($timeHex);
		$rand = random_bytes(10);
		$bytes = $timeBin . $rand; // 16 bytes
		$bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x70); // version 7
		$bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80); // variant RFC 4122
		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
	}

	/**
	 * 生成安全随机整数
	 * @param int $min
	 * @param int $max
	 * @return int
	 */
	public static function randomInt(int $min, int $max): int
	{
		$config = self::getConfig();
		if ($min > $max) {
			throw new $config['exception']('randomInt: 最小值不能大于最大值', $config['exception_code']);
		}
		return random_int($min, $max);
	}

	/**
	 * 生成随机浮点数（含最小值，含最大值）
	 * @param float $min
	 * @param float $max
	 * @return float
	 */
	public static function randomFloat(float $min, float $max): float
	{
		$config = self::getConfig();
		if ($min > $max) {
			throw new $config['exception']('randomFloat: 最小值不能大于最大值', $config['exception_code']);
		}
		if ($min === $max) return $min;
		$scale = random_int(0, PHP_INT_MAX) / PHP_INT_MAX; // [0,1]
		return $min + ($max - $min) * $scale;
	}

	/**
	 * 构建稳定排序的查询字符串（RFC3986）
	 * @param array $params
	 * @return string
	 */
	public static function buildQuery(array $params): string
	{
		$normalized = $params;
		self::ksortRecursive($normalized);
		return http_build_query($normalized, '', '&', PHP_QUERY_RFC3986);
	}

	/**
	 * 人类可读的字节展示，如 1.23 MB
	 * @param int|float $bytes
	 * @param int $precision
	 * @return string
	 */
	public static function humanBytes(int|float $bytes, int $precision = 2): string
	{
		$units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
		$bytes = max(0, (float)$bytes);
		$pow = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
		$pow = (int)min($pow, count($units) - 1);
		$value = $bytes / (1024 ** $pow);
		return number_format($value, $precision) . ' ' . $units[$pow];
	}

	/**
	 * 重试执行
	 * @param callable $fn 执行体 fn(int $attempt): mixed
	 * @param int $times 重试次数（含首次），默认3
	 * @param int $sleepMs 每次重试间隔毫秒
	 * @param callable|null $shouldRetry fn(Throwable $e, int $attempt): bool
	 * @return mixed
	 * @throws Throwable
	 */
	public static function retry(callable $fn, int $times = 3, int $sleepMs = 100, ?callable $shouldRetry = null): mixed
	{
		$config = self::getConfig();
		if ($times <= 0) {
			throw new $config['exception']('retry: 次数必须大于0', $config['exception_code']);
		}
		$attempt = 0;
		while (true) {
			$attempt++;
			try {
				return $fn($attempt);
			} catch (Throwable $e) {
				if ($attempt >= $times) {
					throw $e;
				}
				if ($shouldRetry !== null && !$shouldRetry($e, $attempt)) {
					throw $e;
				}
				if ($sleepMs > 0) {
					usleep($sleepMs * 1000);
				}
			}
		}
	}



	/**
	 * 扁平化树
	 * @param array $tree
	 * @param string $children
	 * @return array
	 */
	public static function flattenTree(array $tree, string $children = 'children'): array
	{
		$flat = [];
		$stack = $tree;
		while ($stack) {
			$node = array_shift($stack);
			$nodeCopy = $node;
			if (is_array($nodeCopy) && array_key_exists($children, $nodeCopy)) {
				$child = $nodeCopy[$children];
				unset($nodeCopy[$children]);
				if (is_array($child)) {
					$stack = array_merge($child, $stack);
				}
			}
			$flat[] = $nodeCopy;
		}
		return $flat;
	}

	/**
	 * 在树中查找符合谓词的节点
	 * @param array $tree
	 * @param callable $predicate fn(array $node): bool
	 * @param string $children
	 * @return array|null
	 */
	public static function findInTree(array $tree, callable $predicate, string $children = 'children'): ?array
	{
		foreach ($tree as $node) {
			if ($predicate($node)) {
				return $node;
			}
			if (isset($node[$children]) && is_array($node[$children])) {
				$found = self::findInTree($node[$children], $predicate, $children);
				if ($found !== null) return $found;
			}
		}
		return null;
	}

	/**
	 * 查找节点路径
	 * @param array $tree
	 * @param mixed $id
	 * @param string $idField
	 * @param string $children
	 * @return array 路径数组(从根到目标)，未找到返回空数组
	 */
	public static function pathInTree(array $tree, mixed $id, string $idField = 'id', string $children = 'children'): array
	{
		$path = [];
		$found = self::pathInTreeDfs($tree, $id, $idField, $children, $path);
		return $found ? $path : [];
	}

	/**
	 * DFS 查找节点路径的辅助方法
	 * @param array $tree
	 * @param mixed $targetId
	 * @param string $idField
	 * @param string $children
	 * @param array $path
	 * @return bool
	 */
	private static function pathInTreeDfs(array $tree, mixed $targetId, string $idField, string $children, array &$path): bool
	{
		foreach ($tree as $node) {
			// 将当前节点添加到路径中
			$path[] = $node;

			// 检查当前节点是否是目标节点
			if (isset($node[$idField]) && $node[$idField] === $targetId) {
				return true; // 找到目标节点，返回 true
			}

			// 如果有子节点，递归搜索
			if (isset($node[$children]) && is_array($node[$children])) {
				if (self::pathInTreeDfs($node[$children], $targetId, $idField, $children, $path)) {
					return true; // 在子节点中找到了目标
				}
			}

			// 当前路径没有找到目标，移除当前节点
			array_pop($path);
		}

		return false; // 没有找到目标节点
	}

	/**
	 * 从扁平数组中获取节点路径
	 * @param array $flatArray 扁平数组
	 * @param mixed $id 目标节点ID
	 * @param string $idField ID字段名
	 * @param string $parentField 父级字段名
	 * @return array 路径数组(从根到目标)，未找到返回空数组
	 */
	public static function pathInFlat(array $flatArray, mixed $id, string $idField = 'id', string $parentField = 'parent_id'): array
	{
		// 将扁平数组转换为以ID为键的映射
		$nodeMap = [];
		foreach ($flatArray as $node) {
			if (isset($node[$idField])) {
				$nodeMap[$node[$idField]] = $node;
			}
		}

		// 检查目标节点是否存在
		if (!isset($nodeMap[$id])) {
			return [];
		}

		$path = [];
		$currentId = $id;

		// 从目标节点向上追溯到根节点
		while ($currentId !== null && isset($nodeMap[$currentId])) {
			$currentNode = $nodeMap[$currentId];
			array_unshift($path, $currentNode); // 插入到数组开头

			// 获取父级ID
			$parentId = $currentNode[$parentField] ?? null;

			// 避免无限循环（防止数据错误导致的循环引用）
			if ($parentId === $currentId) {
				break;
			}

			$currentId = $parentId;
		}

		return $path;
	}



	/**
	 * 递归按键排序（仅关联数组）
	 * @param array $array
	 * @return void
	 */
	private static function ksortRecursive(array &$array): void
	{
		$keys = array_keys($array);
		$sequential = $keys === array_keys($keys);
		if (!$sequential) {
			ksort($array);
		}
		foreach ($array as &$value) {
			if (is_array($value)) {
				self::ksortRecursive($value);
			}
		}
	}
}
