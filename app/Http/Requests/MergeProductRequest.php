<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MergeProductRequest extends FormRequest
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
            'into' => 'required|integer|exists:products,id|different:product',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'into.different' => 'A product cannot be merged into itself.',
        ];
    }
}
