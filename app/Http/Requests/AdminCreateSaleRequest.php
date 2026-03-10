<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminCreateSaleRequest extends FormRequest
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
            'seller_id' => 'required|exists:sellers,id',
            'date' => 'required|date',
            'taken.*' => 'integer|min:0',
            'returned.*' => 'nullable|integer|min:0',
            'price.*' => 'nullable|integer|min:0',
            'remarks.*' => 'nullable|string',
            'red_flag' => 'nullable|boolean',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'seller_id.required' => 'Seller is required.',
            'seller_id.exists' => 'Selected seller does not exist.',
            'date.required' => 'Date is required.',
            'date.date' => 'Date must be a valid date.',
            'taken.*.integer' => 'Taken quantity must be an integer.',
            'taken.*.min' => 'Taken quantity must be at least 0.',
            'returned.*.integer' => 'Returned quantity must be an integer.',
            'returned.*.min' => 'Returned quantity must be at least 0.',
            'price.*.integer' => 'Custom price must be an integer.',
            'price.*.min' => 'Custom price must be at least 0.',
            'remarks.*.string' => 'Remarks must be a string.',
        ];
    }
}
