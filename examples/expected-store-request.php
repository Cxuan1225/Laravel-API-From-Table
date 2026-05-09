<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'credit_limit' => ['nullable', 'numeric'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
