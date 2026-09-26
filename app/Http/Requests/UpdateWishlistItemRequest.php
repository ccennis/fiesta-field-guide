<?php

namespace App\Http\Requests;

use App\Enums\WishlistPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWishlistItemRequest extends FormRequest
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
            'priority' => ['sometimes', Rule::enum(WishlistPriority::class)],
            'max_price' => 'sometimes|nullable|numeric|min:0|max:1000000',
            'notes' => 'sometimes|nullable|string|max:1000',
            'fulfilled' => 'sometimes|boolean',
        ];
    }
}
