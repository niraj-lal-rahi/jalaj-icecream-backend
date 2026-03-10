<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CreateItemRequest
 *
 * Validates data for creating a new item.
 *
 * Rules:
 * - name: Required, string, max 250 characters
 * - price: Required, integer, min 0
 * - order_by: Required, integer, min 0
 */
class CreateItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // All authenticated users can create items
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
            'name' => 'required|string|max:250',
            'price' => 'required|integer|min:0',
            'order_by' => 'required|integer|min:0',
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
            'name.required' => 'Item name is required',
            'name.string' => 'Item name must be a string',
            'name.max' => 'Item name cannot exceed 250 characters',
            'price.required' => 'Item price is required',
            'price.integer' => 'Item price must be a whole number',
            'price.min' => 'Item price cannot be negative',
            'order_by.required' => 'Display order is required',
            'order_by.integer' => 'Display order must be a whole number',
            'order_by.min' => 'Display order cannot be negative',
        ];
    }
}
