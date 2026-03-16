<?php

declare(strict_types=1);

namespace Plugins\Subdomains\Exception;

class CloudflareException extends \RuntimeException
{
    private array $errors;

    public function __construct(string $message = '', array $errors = [], int $code = 0, ?\Throwable $previous = null)
    {
        $this->errors = $errors;
        parent::__construct($message, $code, $previous);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
