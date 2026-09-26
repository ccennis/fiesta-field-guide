<?php

namespace App\Http\Requests;

use App\Enums\ListingNameKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A new color or product made from a store name. Everything in it is what the
 * owner typed; nothing is filled in from the store.
 */
class CreateFromListingNameRequest extends FormRequest
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
            'kind' => ['required', Rule::enum(ListingNameKind::class)],
            'key' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'produced_from' => 'sometimes|nullable|integer|min:1900|max:2100',
            'produced_to' => 'sometimes|nullable|integer|min:1900|max:2100|gte:produced_from',
        ];
    }
}
