<?php

declare (strict_types=1);

namespace app\platform\ai;

/**
 * AI 响应值对象
 */
class AiResponse
{
    /** @var bool */
    public $success;

    /** @var string|null */
    public $content;

    /** @var string */
    public $error;

    public function __construct(bool $success, ?string $content, string $error = '')
    {
        $this->success = $success;
        $this->content = $content;
        $this->error   = $error;
    }

    public static function ok(string $content): self
    {
        return new self(true, $content);
    }

    public static function fail(string $error): self
    {
        return new self(false, null, $error);
    }
}
