<?php

namespace App\Exceptions;

use Exception;

/**
 * UnauthorizedException
 *
 * Thrown when a user lacks permission to perform an action.
 * Status Code: 403 Forbidden
 *
 * Usage:
 *   if (!auth()->user()->hasPermission('edit_sales')) {
 *       throw new UnauthorizedException('You do not have permission to edit sales');
 *   }
 */
class UnauthorizedException extends Exception
{
    /**
     * Create a new exception instance
     *
     * @param string $message Error message explaining the authorization failure
     */
    public function __construct(string $message = 'Unauthorized')
    {
        parent::__construct($message);
    }
}
