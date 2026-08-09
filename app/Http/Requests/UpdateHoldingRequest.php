<?php

namespace App\Http\Requests;

use App\Enums\Condition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHoldingRequest extends FormRequest
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
            'condition' => ['sometimes', 'nullable', Rule::enum(Condition::class)],
            'condition_notes' => 'sometimes|nullable|string|max:1000',
        ];
    }
}
