<?php

declare(strict_types=1);

namespace TobiasMaxham\PhpLims\Exceptions;

/**
 * The request could not be sent (DNS, connection refused, timeout, ...).
 */
class ConnectionException extends LimsException
{
}
