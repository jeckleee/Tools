<?php

namespace Jeckleee\Tools;

use DateTime;
use Exception;

class Validator
{
    private static array $input = [];
    private static array $output = [];
    private static string|null $customException = null;
    private static int $errorCode = 300;
    private array $rules = [];
    private string $fieldName = '';
    public static array $showAllRules = [
        'required' => '参数必填,可设置一个默认值',
    ];


    public static function array(array $input, $rules, $customException = null, $errorCode = 500): array
    {
        self::$customException = $customException ?: Exception::class;
        self::$errorCode = $errorCode;
        self::$input = $input;
        self::$output = [];
        self::applyRules($rules);
        return self::$output;
    }

    public static function one(array $input, $rules, $customException = null, $errorCode = 500)
    {
        self::$customException = $customException ?: Exception::class;
        self::$errorCode = $errorCode;
        self::$input = $input;
        self::$output = [];
        self::applyRules($rules);
        return reset(self::$output);
    }

    public static function fieldName(string $fieldName): Validator
    {
        $validator = new static();
        $validator->fieldName = $fieldName;
        return $validator;
    }

    public function msg(string $err_msg = ''): array
    {
        $this->rules['err_msg'] = $err_msg ?: '数据验证失败:' . $this->fieldName;
        $this->rules['fieldName'] = $this->fieldName;
        return $this->rules;
    }

    private static function applyRules($rules): void
    {
        foreach ($rules as $rule) {
            foreach ($rule['list'] as $item) {
                $function = $item['function'];
                $fieldName = $rule['fieldName'];
                $fieldValue = self::$input[$rule['fieldName']] ?? null;
                $item['msg'] = $rule['err_msg'] ?? '';
                $function($fieldName, $fieldValue, $item); // 调用闭包
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
            $msg = $item['msg'] ?: '参数必填:' . $fieldName;
            if ($fieldValue ?? null) {
                self::$output[$fieldName] = $fieldValue;
            } elseif ($defaultValue !== null) {
                self::$input[$fieldName] = $defaultValue;
                self::$output[$fieldName] = $defaultValue;
            } else {
                throw new self::$customException($msg, self::$errorCode);
            }
        });
    }

    public function stringTrim(): Validator
    {
        return $this->addRule(function ($fieldName, $fieldValue, $item) {
            $msg = $item['msg'] ?: '参数:' . $fieldName . '不合法';
            if ($fieldValue) {
                self::$input[$fieldName] = trim($fieldValue);
                self::$output[$fieldName] = self::$input[$fieldName];
            } else {
                throw new self::$customException($msg, self::$errorCode);
            }
        });
    }

    public function betweenNumber(int $min, int $max): Validator
    {
        return $this->addRule(function ($fieldName, $fieldValue, $item) use ($min, $max) {
            $msg = $item['msg'] ?: '参数:' . $fieldName . '必须在' . $min . '-' . $max . '之间';
            if ($fieldValue >= $min && $fieldValue <= $max) {
                self::$output[$fieldName] = $fieldValue;
            } else {
                throw new self::$customException($msg, self::$errorCode);
            }
        }, ['min' => $min, 'max' => $max]);
    }

    public function inArray($array): Validator
    {
        return $this->addRule(function ($fieldName, $fieldValue, $item) use ($array) {
            $msg = $item['msg'] ?: '参数:' . $fieldName . '仅允许在(' . implode(',', $array) . ')中';
            if (in_array($fieldValue, $array)) {
                self::$output[$fieldName] = $fieldValue;
            } else {
                throw new self::$customException($msg, self::$errorCode);
            }
        }, ['array' => $array]);
    }

    public function isArray(): Validator
    {
        return $this->addRule(function ($fieldName, $fieldValue, $item) {
            $msg = $item['msg'] ?: '参数:' . $fieldName . '必须是一个数组';
            if (is_array($fieldValue)) {
                self::$output[$fieldName] = $fieldValue;
            } else {
                throw new self::$customException($msg, self::$errorCode);
            }
        });
    }

    public function notValidate(): Validator
    {
        return $this->addRule(function ($fieldName, $fieldValue) {
            self::$output[$fieldName] = $fieldValue ?? null;
        });
    }

    public function isNumber(): Validator
    {
        return $this->addRule(function ($fieldName, $fieldValue, $item) {
            $msg = $item['msg'] ?: '参数:' . $fieldName . '必须是数字';
            if (is_numeric($fieldValue)) {
                self::$output[$fieldName] = intval($fieldValue);
            } else {
                throw new self::$customException($msg, self::$errorCode);
            }
        });
    }

    public function stringLength($min = 1, $max = 20): Validator
    {
        return $this->addRule(function ($fieldName, $fieldValue, $item) use ($min, $max) {
            $msg = $item['msg'] ?: '参数:' . $fieldName . '的长度必须在' . $min . '~' . $max . '之间';
            $length = mb_strlen($fieldValue, 'utf-8');
            if ($length >= $min && $length <= $max) {
                self::$output[$fieldName] = $fieldValue;
            } else {
                throw new self::$customException($msg, self::$errorCode);
            }
        }, ['min' => $min, 'max' => $max]);
    }

    public function isEmail(): Validator
    {
        return $this->addRule(function ($fieldName, $fieldValue, $item) {
            $msg = $item['msg'] ?: '参数:' . $fieldName . '不是一个合法的邮箱地址';
            if (filter_var($fieldValue, FILTER_VALIDATE_EMAIL)) {
                self::$output[$fieldName] = $fieldValue;
            } else {
                throw new self::$customException($msg, self::$errorCode);
            }
        });
    }

    public function isMobile(): Validator
    {
        return $this->addRule(function ($fieldName, $fieldValue, $item) {
            $msg = $item['msg'] ?: '参数:' . $fieldName . '不是一个合法的手机号';
            $phoneRegex = '/^1[3-9]\d{9}$/';
            if (preg_match($phoneRegex, $fieldValue)) {
                self::$output[$fieldName] = $fieldValue;
            } else {
                throw new self::$customException($msg, self::$errorCode);
            }
        });
    }

    public function isDateTimeInFormat($format): Validator
    {
        return $this->addRule(function ($fieldName, $fieldValue, $item) use ($format) {
            $msg = $item['msg'] ?: '参数:' . $fieldName . '不是一个合法的时间字符串.(' . $format . ')';
            $d = DateTime::createFromFormat($format, $fieldValue);
            if ($d && $d->format($format) === $fieldValue) {
                self::$output[$fieldName] = $fieldValue;
            } else {
                throw new self::$customException($msg, self::$errorCode);
            }
        }, ['format' => $format]);
    }

    public function isIdCard(): Validator
    {
        return $this->addRule(function ($fieldName, $fieldValue, $item) {
            $msg = $item['msg'] ?: '参数:' . $fieldName . '不是一个合法的身份证号';
            $idCardRegex = '/(^[1-9]\d{5}(18|19|([23]\d))\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$)|(^[1-9]\d{5}\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}$)/';
            if (preg_match($idCardRegex, $fieldValue)) {
                self::$output[$fieldName] = $fieldValue;
            } else {
                throw new self::$customException($msg, self::$errorCode);
            }
        });
    }

    public function isUrl(): Validator
    {
        return $this->addRule(function ($fieldName, $fieldValue, $item) {
            $msg = $item['msg'] ?: '参数:' . $fieldName . '不是一个合法的url';
            if (filter_var($fieldValue, FILTER_VALIDATE_URL)) {
                self::$output[$fieldName] = $fieldValue;
            } else {
                throw new self::$customException($msg, self::$errorCode);
            }
        });
    }

    public function isIp($type = 'ipv4'): Validator
    {
        return $this->addRule(function ($fieldName, $fieldValue, $item) use ($type) {
            $msg = $item['msg'] ?: '参数:' . $fieldName . '不是一个合法的ip地址';
            $filter = $type === 'ipv6' ? FILTER_FLAG_IPV6 : FILTER_FLAG_IPV4;
            if (filter_var($fieldValue, FILTER_VALIDATE_IP, $filter)) {
                self::$output[$fieldName] = $fieldValue;
            } else {
                throw new self::$customException($msg, self::$errorCode);
            }
        }, ['type' => $type]);
    }

    public function isIntval(): Validator
    {
        return $this->addRule(function ($fieldName, $fieldValue, $item) {
            $msg = $item['msg'] ?: '参数:' . $fieldName . '不是整数';
            if (is_integer($fieldValue)) {
                self::$output[$fieldName] = intval($fieldValue);
            } else {
                throw new self::$customException($msg, self::$errorCode);
            }
        });
    }

}