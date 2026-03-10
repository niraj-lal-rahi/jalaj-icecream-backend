<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminCreateSellerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'number' => 'required|string|max:20',
            'address' => 'required|string',
            'documents.*' => 'file|mimes:pdf,jpg,png|max:5120',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Seller name is required.',
            'name.string' => 'Seller name must be a string.',
            'name.max' => 'Seller name may not be greater than 255 characters.',
            'number.required' => 'Seller phone number is required.',
            'number.string' => 'Seller phone number must be a string.',
            'number.max' => 'Seller phone number may not be greater than 20 characters.',
            'address.required' => 'Seller address is required.',
            'address.string' => 'Seller address must be a string.',
            'documents.*.file' => 'Each document must be a file.',
            'documents.*.mimes' => 'Each document must be a PDF, JPG, or PNG file.',
            'documents.*.max' => 'Each document may not be greater than 5MB.',
        ];
    }
}
