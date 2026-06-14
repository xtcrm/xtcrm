<?php

declare (strict_types=1);

namespace app\platform\exception;

use cores\exception\BaseException;

/**
 * 平台异常基类
 *
 * 包装 cores\exception\BaseException，增加 statusCode 和数据承载。
 */
class PlatformException extends BaseException
{
    /** @var int HTTP 状态码 */
    protected $statusCode = 500;

    /**
     * 构造函数
     * @param array $params ['status' => int, 'message' => string, 'data' => array]
     */
    public function __construct(array $params = [])
    {
        parent::__construct($params);
        if (isset($params['statusCode'])) {
            $this->statusCode = (int)$params['statusCode'];
        }
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorData(): array
    {
        return $this->data;
    }
}
