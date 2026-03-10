<?php

namespace App\Exceptions;

use Exception;

/**
 * ValidationException
 *
 * Thrown when input validation fails.
 *
 * Usage:
 *   try {
 *       $errors = validate($data);
 *       if ($errors) {
 *           throw new ValidationException($errors);
 *       }
 *   } catch (ValidationException $e) {
 *       return $this->error('Validation failed', 422, $e->errors);
 *   }
 */
class ValidationException extends Exception
{
    /**
     * @var array Validation error messages
     */
    public array $errors = [];

    /**
     * Create a new exception instance
     *
     * @param array $errors Array of validation error messages
     */
    public function __construct(array $errors)
    {
        $this->errors = $errors;
        parent::__construct("Validation failed");
    }

    /**
     * Get all validation errors
     *
     * @return array
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
