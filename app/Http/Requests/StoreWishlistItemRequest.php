<?php

namespace App\Http\Requests;

use App\Enums\WishlistPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A null variant means the product in any plain color.
 */
class StoreWishlistItemRequest extends FormRequest
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
            'product_id' => 'required|integer|exists:products,id',
            'variant_id' => 'sometimes|nullable|integer|exists:variants,id',
            'priority' => ['sometimes', Rule::enum(WishlistPriority::class)],
            'max_price' => 'sometimes|nullable|numeric|min:0|max:1000000',
            'notes' => 'sometimes|nullable|string|max:1000',
        ];
    }
}
