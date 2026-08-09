<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'line_id' => 'required|integer|exists:lines,id',
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('products', 'name')->where('line_id', $this->input('line_id')),
            ],
            'notes' => 'sometimes|nullable|string|max:1000',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'That line already has a product with this name. Merge into it instead.',
        ];
    }
}
