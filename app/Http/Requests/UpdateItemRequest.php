<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * UpdateItemRequest
 *
 * Validates data for updating an existing item.
 * All fields are optional (sometimes) to allow partial updates.
 *
 * Rules:
 * - name: Optional, string, max 250 characters
 * - price: Optional, integer, min 0
 * - order_by: Optional, integer, min 0
 */
class UpdateItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // All authenticated users can update items
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:250',
            'price' => 'sometimes|integer|min:0',
            'order_by' => 'sometimes|integer|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.string' => 'Item name must be a string',
            'name.max' => 'Item name cannot exceed 250 characters',
            'price.integer' => 'Item price must be a whole number',
            'price.min' => 'Item price cannot be negative',
            'order_by.integer' => 'Display order must be a whole number',
            'order_by.min' => 'Display order cannot be negative',
        ];
    }
}
