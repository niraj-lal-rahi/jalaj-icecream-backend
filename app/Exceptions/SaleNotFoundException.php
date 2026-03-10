<?php

namespace App\Exceptions;

use Exception;

/**
 * SaleNotFoundException
 *
 * Thrown when a sale resource cannot be found in the database.
 *
 * Usage:
 *   if (!$sale) {
 *       throw new SaleNotFoundException($saleId);
 *   }
 */
class SaleNotFoundException extends Exception
{
    /**
     * Create a new exception instance
     *
     * @param int $saleId The ID of the sale that was not found
     */
    public function __construct(int $saleId)
    {
        parent::__construct("Sale with ID {$saleId} not found");
    }
}
