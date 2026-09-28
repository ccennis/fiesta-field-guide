<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BrowseRequest extends FormRequest
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
            'q' => ['required_without_all:color_id,product_id', 'nullable', 'string', 'min:2', 'max:60'],
            'color_id' => ['sometimes', 'integer', 'exists:colors,id'],
            'product_id' => ['sometimes', 'integer', 'exists:products,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'q.required_without_all' => 'Type a color or a product to search for.',
            'q.min' => 'Type at least two letters.',
        ];
    }
}
