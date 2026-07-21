<?php

namespace App\Exceptions;

use Exception;

class BoatNotAvailableException extends Exception
{
    public function __construct(string $message = 'This boat has already been assigned.')
    {
        parent::__construct($message, 409);
    }

    public function render()
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
        ], 409);
    }
}
