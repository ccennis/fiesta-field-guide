<?php

namespace App\Http\Requests;

use App\Enums\Era;
use App\Enums\VariantExistence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filters for the variant list, which backs both the collection view and the
 * missing view. Casting belongs to the service; this decides only what counts
 * as acceptable input.
 */
class IndexVariantRequest extends FormRequest
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
            'color_id' => 'sometimes|integer|exists:colors,id',
            'decoration_id' => 'sometimes|integer|exists:decorations,id',
            'era' => ['sometimes', Rule::enum(Era::class)],
            'year' => 'sometimes|integer|min:1930|max:2100',
            'existence' => ['sometimes', Rule::enum(VariantExistence::class)],
            'owned' => 'sometimes|boolean',
            'decorated' => 'sometimes|boolean',
            'per_page' => 'sometimes|integer|min:1|max:200',
        ];
    }
}
