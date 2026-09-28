<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetColorMadeRequest extends FormRequest
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
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'made' => ['required', 'boolean'],
        ];
    }
}
