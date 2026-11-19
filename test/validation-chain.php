<?php

use Jeckleee\Tools\Validator as V;

require __DIR__ . '/../vendor/autoload.php';

// 模拟框架的 config()，每个用例可以覆盖验证器配置。
function config(string $key, array $defaults): array
{
	return array_replace($defaults, $GLOBALS['validationConfig'] ?? []);
}

function expectSame($expected, $actual): void
{
	if ($expected !== $actual) {
		throw new RuntimeException('预期 ' . var_export($expected, true) . '，实际 ' . var_export($actual, true));
	}
}

function expectFailure(callable $run, string $message, int $code): void
{
	try {
		$run();
	} catch (Exception $e) {
		expectSame($message, $e->getMessage());
		expectSame($code, $e->getCode());
		return;
	}
	throw new RuntimeException('预期校验抛出异常');
}

$tests = [
	'多字段按顺序处理，只返回声明的字段' => function () {
		$input = ['name' => '  小明同学  ', 'email' => 'alice@example.org', 'extra' => 1];
		$result = V::verify($input)
			->field('name')->required()->strTrim()->strLength(3, 4)->msg('用户名格式错误', 12001)
			->field('email')->required()->email()->msg('邮箱格式错误')
			->get();
		expectSame(['name' => '小明同学', 'email' => 'alice@example.org'], $result);
	},
	'单字段直接返回值，保留转换后的类型' => function () {
		expectSame(0, V::verify(['n' => '0'])->field('n')->isInt()->get());
		expectSame(false, V::verify(['flag' => false])->field('flag')->ifExisted()->get());
		expectSame(['x' => 1], V::verify(['data' => '{"x":1}'])->field('data')->isJson(true)->get());
	},
	'可选字段缺失时按声明的字段数决定结果形式' => function () {
		expectSame([], V::verify(['unused' => 1])->get());
		expectSame(null, V::verify([])->field('email')->ifExisted()->email()->get());
		expectSame(['name' => 'Alice'], V::verify(['name' => 'Alice'])
			->field('name')->required()->field('email')->ifExisted()->email()->get());
	},
	'规则在 get() 才执行，错误提示和错误码只属于当前字段' => function () {
		$chain = V::verify(['name' => 'Alice', 'email' => 'invalid'])
			->field('name')->required()->msg('用户名格式错误', 12001)
			->field('email')->required()->email()->msg('邮箱格式错误', 12002);
		expectFailure(fn() => $chain->get(), '邮箱格式错误', 12002);

		$chain = V::verify(['name' => 'Alice'])
			->field('name')->required()->msg('用户名格式错误', 12001)
			->field('email')->required();
		expectFailure(fn() => $chain->get(), '参数必填:email', 500);
	},
	'msg 可省略，可只指定提示或错误码' => function () {
		expectFailure(fn() => V::verify([])->field('name')->required()->msg()->get(), '参数必填:name', 500);
		expectFailure(fn() => V::verify([])->field('name')->required()->msg('请填写名字')->get(), '请填写名字', 500);
		expectFailure(fn() => V::verify([])->field('name')->required()->msg(null, 0)->get(), '参数必填:name', 0);
	},
	'集中返回错误时沿用配置并保留各字段的错误码' => function () {
		$GLOBALS['validationConfig'] = ['error_return_mode' => 'collective', 'exception' => DomainException::class, 'exception_code' => 422];
		try {
			V::verify([])->field('name')->required()->msg('缺用户名', 12001)
				->field('email')->required()->msg('缺邮箱')->get();
		} catch (DomainException $e) {
			expectSame(422, $e->getCode());
			expectSame([
				['fieldName' => 'name', 'error_code' => 12001, 'message' => '缺用户名'],
				['fieldName' => 'email', 'error_code' => 422, 'message' => '缺邮箱'],
			], json_decode($e->getMessage(), true));
			return;
		} finally {
			unset($GLOBALS['validationConfig']);
		}
		throw new RuntimeException('预期返回配置的异常');
	},
	'嵌套调用、交错创建和重复执行均从各自的输入开始' => function () {
		$inner = V::verify(['other' => '42'])->field('other')->isInt();
		$outer = V::verify(['name' => ' Alice '])->field('name')
			->func(fn($value) => $value === ' Alice ' && $inner->get() === 42)->strTrim()
			->field('role')->required('guest');
		expectSame(['name' => 'Alice', 'role' => 'guest'], $outer->get());
		expectSame(['name' => 'Alice', 'role' => 'guest'], $outer->get());
	},
	'同一进程连续校验后释放每次调用的输入、规则和验证链' => function () {
		$liveObjects = new WeakMap();
		$handleRequest = static function (int $id) use ($liveObjects) {
			$payload = (object) ['body' => str_repeat('x', 64 * 1024)];
			$valid = $id % 2 === 0;
			$rule = static fn($value) => $valid && $value === $id;
			$chain = V::verify(['payload' => $payload, 'id' => $id])
				->field('payload')->required()
				->field('id')->func($rule)->msg('编号错误', 12003);

			$liveObjects[$payload] = true;
			$liveObjects[$rule] = true;
			$liveObjects[$chain] = true;

			if ($valid) {
				expectSame(['payload' => $payload, 'id' => $id], $chain->get());
			} else {
				expectFailure(fn() => $chain->get(), '编号错误', 12003);
			}
		};

		for ($id = 0; $id < 4096; ++$id) {
			$handleRequest($id);
			expectSame(0, count($liveObjects));
		}
	},
	'独立变量通过单字段验证，失败时抛出指定的异常' => function () {
		$email = 'alice@example.org';
		expectSame($email, V::verify(['email' => $email])->field('email')->email()->get());

		try {
			V::verify(['email' => 'invalid'])->field('email')->email()->msg('邮箱格式错误')
				->get(exceptionClass: DomainException::class, errorCode: 422);
		} catch (DomainException $e) {
			expectSame('邮箱格式错误', $e->getMessage());
			expectSame(422, $e->getCode());
			return;
		}
		throw new RuntimeException('预期单字段校验失败');
	},
];

foreach ($tests as $description => $test) {
	$test();
	echo "PASS  $description\n";
}
echo count($tests) . " tests passed\n";
