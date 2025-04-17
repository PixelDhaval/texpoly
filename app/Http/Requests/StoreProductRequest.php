<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'short_code' => 'nullable|string|max:50|unique:products,short_code',
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'label_name' => 'nullable|string|max:255',
            'grade' => 'nullable|string',
            'unit' => 'nullable|string|in:KGS,LBS,PCS',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'weight' => 'required|integer|min:0',
        ];
    }
}
