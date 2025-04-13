<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email,' . $this->customer->id,
            'phone' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'label_id' => 'nullable|exists:labels,id',
            'short_code' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'is_qr' => 'boolean',
            'is_bale_no' => 'boolean',
            'is_printed_by' => 'boolean',
        ];
    }
}
