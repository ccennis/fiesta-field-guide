<?php

namespace App\Http\Requests;

use App\Enums\ListingNameKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexListingNamesRequest extends FormRequest
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
            'ruled' => 'sometimes|boolean',
        ];
    }
}
