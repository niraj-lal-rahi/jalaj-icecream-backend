<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * UpdateSellerRequest
 *
 * Validates data for updating an existing seller.
 * All fields are optional (sometimes) to allow partial updates.
 *
 * Rules:
 * - name: Optional, string, max 255 characters
 * - number: Optional, string, max 20 characters
 * - address: Optional, string
 */
class UpdateSellerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // All authenticated users can update sellers
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
            'name' => 'sometimes|string|max:255',
            'number' => 'sometimes|string|max:20',
            'address' => 'sometimes|string',
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
            'name.string' => 'Seller name must be a string',
            'name.max' => 'Seller name cannot exceed 255 characters',
            'number.string' => 'Seller number must be a string',
            'number.max' => 'Seller number cannot exceed 20 characters',
            'address.string' => 'Seller address must be a string',
        ];
    }
}
