<?php

declare(strict_types=1);

namespace LimsPhp\Lims\Exceptions;

use Throwable;

/**
 * The LiMS API answered with an error status code.
 */
class ApiException extends LimsException
{
    public function __construct(
        string $message,
        public readonly ?int $statusCode = null,
        public readonly ?string $responseBody = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode ?? 0, $previous);
    }
}
