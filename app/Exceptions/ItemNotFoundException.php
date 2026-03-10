<?php

namespace App\Exceptions;

use Exception;

/**
 * ItemNotFoundException
 *
 * Thrown when an item resource cannot be found in the database.
 *
 * Usage:
 *   if (!$item) {
 *       throw new ItemNotFoundException($itemId);
 *   }
 */
class ItemNotFoundException extends Exception
{
    /**
     * Create a new exception instance
     *
     * @param int $itemId The ID of the item that was not found
     */
    public function __construct(int $itemId)
    {
        parent::__construct("Item with ID {$itemId} not found");
    }
}
