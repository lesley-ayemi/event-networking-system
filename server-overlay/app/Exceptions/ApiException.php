<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown for any expected, named business-rule failure (already registered,
 * duplicate request, messaging not permitted, ...) so every API error —
 * whether thrown here or from a framework exception — renders through the
 * same {success, message, errorCode} envelope registered in bootstrap/app.php.
 */
class ApiException extends Exception
{
    public function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly int $status = 422,
    ) {
        parent::__construct($message);
    }
}
