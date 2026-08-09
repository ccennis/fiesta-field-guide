<?php

namespace App\Http\Requests;

use App\Enums\Rarity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
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
            'name' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('products', 'name')
                    ->where('line_id', $this->route('product')->line_id)
                    ->ignore($this->route('product')),
            ],
            'rarity' => ['sometimes', 'nullable', Rule::enum(Rarity::class)],
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
