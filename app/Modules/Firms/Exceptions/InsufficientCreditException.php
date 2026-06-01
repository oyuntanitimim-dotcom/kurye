<?php

declare(strict_types=1);

namespace App\Modules\Firms\Exceptions;

use RuntimeException;

class InsufficientCreditException extends RuntimeException
{
    public function __construct(string $message = 'Kontör yetersiz.')
    {
        parent::__construct($message);
    }
}
