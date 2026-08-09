<?php

namespace App\Http\Requests;

use App\Enums\Condition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * One row per physical piece, so there is deliberately no quantity field.
 */
class StoreHoldingRequest extends FormRequest
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
            'variant_id' => 'required|integer|exists:variants,id',
            'condition' => ['sometimes', 'nullable', Rule::enum(Condition::class)],
            'condition_notes' => 'sometimes|nullable|string|max:1000',
            'purchase_price' => 'sometimes|nullable|numeric|min:0|max:1000000',
            'purchase_date' => 'sometimes|nullable|date',
            'notes' => 'sometimes|nullable|string|max:1000',
        ];
    }
}
