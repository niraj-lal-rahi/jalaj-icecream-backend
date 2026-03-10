<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CreateSaleRequest
 *
 * Validates data for creating a new sale.
 *
 * Rules:
 * - seller_id: Required, must exist in sellers table
 * - item_id: Required, must exist in items table
 * - pick: Required, non-negative integer
 * - returned: Optional, non-negative integer
 * - custom_price: Optional, non-negative integer
 * - red_flag: Optional, boolean
 * - remarks: Optional, string
 * - date: Required, valid date format
 */
class CreateSaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // All authenticated users can create sales
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
            'seller_id' => 'required|exists:sellers,id',
            'item_id' => 'required|exists:items,id',
            'pick' => 'required|integer|min:0',
            'returned' => 'nullable|integer|min:0',
            'custom_price' => 'nullable|integer|min:0',
            'red_flag' => 'nullable|boolean',
            'remarks' => 'nullable|string',
            'date' => 'required|date',
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
            'seller_id.required' => 'Seller is required',
            'seller_id.exists' => 'The selected seller does not exist',
            'item_id.required' => 'Item is required',
            'item_id.exists' => 'The selected item does not exist',
            'pick.required' => 'Quantity picked is required',
            'pick.integer' => 'Quantity picked must be a whole number',
            'pick.min' => 'Quantity picked cannot be negative',
            'returned.integer' => 'Quantity returned must be a whole number',
            'returned.min' => 'Quantity returned cannot be negative',
            'custom_price.integer' => 'Custom price must be a whole number',
            'custom_price.min' => 'Custom price cannot be negative',
            'red_flag.boolean' => 'Red flag must be true or false',
            'date.required' => 'Sale date is required',
            'date.date' => 'Sale date must be a valid date',
        ];
    }
}
