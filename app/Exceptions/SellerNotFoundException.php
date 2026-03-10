<?php

namespace App\Exceptions;

use Exception;

/**
 * SellerNotFoundException
 *
 * Thrown when a seller resource cannot be found in the database.
 *
 * Usage:
 *   if (!$seller) {
 *       throw new SellerNotFoundException($sellerId);
 *   }
 */
class SellerNotFoundException extends Exception
{
    /**
     * Create a new exception instance
     *
     * @param int $sellerId The ID of the seller that was not found
     */
    public function __construct(int $sellerId)
    {
        parent::__construct("Seller with ID {$sellerId} not found");
    }
}
