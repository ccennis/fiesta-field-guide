<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A swatch must be a literal #rrggbb rather than a color name. Production
 * years are the owner's correction of the reference data; a blank last year
 * means the color is still made.
 */
class UpdateColorRequest extends FormRequest
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
            'hex' => ['sometimes', 'nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'produced_from' => ['sometimes', 'nullable', 'integer', 'between:1900,2100'],
            'produced_to' => ['sometimes', 'nullable', 'integer', 'between:1900,2100', 'gte:produced_from'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'hex.regex' => 'A swatch must be a #rrggbb value.',
            'produced_to.gte' => 'The last year cannot be before the first year.',
        ];
    }
}
