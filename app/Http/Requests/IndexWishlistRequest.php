<?php

namespace App\Http\Requests;

use App\Enums\WishlistPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexWishlistRequest extends FormRequest
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
            'fulfilled' => 'sometimes|boolean',
            'priority' => ['sometimes', Rule::enum(WishlistPriority::class)],
            'line_id' => 'sometimes|integer|exists:lines,id',
            'product_id' => 'sometimes|integer|exists:products,id',
        ];
    }
}
