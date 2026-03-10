<?php

namespace App\Exceptions;

use Exception;

/**
 * NotFoundException
 *
 * Generic exception for when a resource cannot be found.
 * Use specific exceptions (SellerNotFoundException, ItemNotFoundException, etc.)
 * for domain-specific not found cases.
 *
 * Status Code: 404 Not Found
 *
 * Usage:
 *   throw new NotFoundException('The requested resource was not found');
 */
class NotFoundException extends Exception
{
    /**
     * Create a new exception instance
     *
     * @param string $message Error message describing what was not found
     */
    public function __construct(string $message = 'Resource not found')
    {
        parent::__construct($message);
    }
}
