<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * UpdateSaleRequest
 *
 * Validates data for updating an existing sale.
 * All fields are optional (sometimes) to allow partial updates.
 *
 * Rules:
 * - seller_id: Optional, must exist in sellers table
 * - date: Optional, valid date in Y-m-d format
 * - pick: Optional, non-negative integer
 * - returned: Optional, non-negative integer
 * - custom_price: Optional, non-negative integer
 * - red_flag: Optional, boolean
 * - remarks: Optional, string
 */
class UpdateSaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // All authenticated users can update sales
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
            'seller_id' => 'sometimes|exists:sellers,id',
            'date' => 'sometimes|date_format:Y-m-d',
            'pick' => 'sometimes|integer|min:0',
            'returned' => 'nullable|integer|min:0',
            'custom_price' => 'nullable|integer|min:0',
            'red_flag' => 'nullable|boolean',
            'remarks' => 'nullable|string',
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
            'seller_id.exists' => 'The selected seller does not exist',
            'date.date_format' => 'Sale date must be in format YYYY-MM-DD',
            'pick.integer' => 'Quantity picked must be a whole number',
            'pick.min' => 'Quantity picked cannot be negative',
            'returned.integer' => 'Quantity returned must be a whole number',
            'returned.min' => 'Quantity returned cannot be negative',
            'custom_price.integer' => 'Custom price must be a whole number',
            'custom_price.min' => 'Custom price cannot be negative',
            'red_flag.boolean' => 'Red flag must be true or false',
        ];
    }
}
