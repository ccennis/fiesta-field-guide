<?php

namespace App\Http\Requests;

use App\Enums\AliasDecision;
use App\Enums\ListingNameKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Names travel in the body rather than the URL, because store names contain
 * slashes ("Fruit/Salsa Bowl").
 */
class RuleListingNameRequest extends FormRequest
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
            'decision' => ['required', Rule::enum(AliasDecision::class)],
            'target_id' => 'required_if:decision,mapped|nullable|integer',
        ];
    }
}
