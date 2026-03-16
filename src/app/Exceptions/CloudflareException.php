<?php

namespace App\Exceptions;

use Exception;

class CloudflareException extends Exception
{
    /**
     * The Cloudflare API error details.
     */
    protected array $errors;

    public function __construct(string $message = '', array $errors = [], int $code = 0, ?\Throwable $previous = null)
    {
        $this->errors = $errors;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the Cloudflare API error details.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
