<?php

namespace App\Http\Requests;

use App\Enums\Era;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexColorRequest extends FormRequest
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
            'line_id' => 'sometimes|integer|exists:lines,id',
            'product_id' => 'sometimes|integer|exists:products,id',
            'era' => ['sometimes', Rule::enum(Era::class)],
        ];
    }
}
