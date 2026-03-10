<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CreateSellerRequest
 *
 * Validates data for creating a new seller.
 *
 * Rules:
 * - name: Required, string, max 255 characters
 * - number: Required, string, max 20 characters (seller badge number or ID)
 * - address: Required, string (full address)
 */
class CreateSellerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // All authenticated users can create sellers
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
            'name' => 'required|string|max:255',
            'number' => 'required|string|max:20',
            'address' => 'required|string',
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
            'name.required' => 'Seller name is required',
            'name.string' => 'Seller name must be a string',
            'name.max' => 'Seller name cannot exceed 255 characters',
            'number.required' => 'Seller number is required',
            'number.string' => 'Seller number must be a string',
            'number.max' => 'Seller number cannot exceed 20 characters',
            'address.required' => 'Seller address is required',
            'address.string' => 'Seller address must be a string',
        ];
    }
}
