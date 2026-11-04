<?php

declare(strict_types=1);

namespace TobiasMaxham\PhpLims\Exceptions;

/**
 * 403 - credentials are valid, but access to the resource or the API was denied.
 */
class AccessDeniedException extends ApiException
{
}
