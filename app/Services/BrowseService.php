<?php

namespace App\Services;

use App\Enums\AliasDecision;
use App\Enums\VariantExistence;
use App\Models\Color;
use App\Models\ColorAlias;
use App\Models\Product;
use App\Models\ProductAlias;
use App\Models\User;
use App\Models\Variant;
use App\Models\WishlistItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * The browse screen's search. A term is matched against color names and
 * product names, including store names the owner mapped, and each match comes
 * back with every plain piece it pairs with. Unverified pairs are included and
 * marked, since most of the catalog has not been verified yet.
 */
class BrowseService extends BaseService
{
    private const MAX_MATCHES = 20;

    /**
     * @param  array<string, mixed>  $input
     * @return array{colors: Collection<int, Color>, products: Collection<int, Product>}
     */
    public function search(array $input, User $viewer): array
    {
        $term = isset($input['q']) ? trim($input['q']) : null;
        $colorId = isset($input['color_id']) ? (int) $input['color_id'] : null;
        $productId = isset($input['product_id']) ? (int) $input['product_id'] : null;

        $colors = $term || $colorId ? $this->matchColors($term, $colorId) : new Collection;
        $products = $term || $productId ? $this->matchProducts($term, $productId) : new Collection;

        $wishlist = $this->openWishlist($viewer);

        foreach ($colors as $color) {
            $color->setRelation('variants', $this->pieces($viewer, $wishlist, fn (Builder $query) => $query
                ->where('color_id', $color->id)
                ->orderByRaw('variants.existence = ? desc', [VariantExistence::Confirmed->value])
                ->orderBy('products.name')));
        }

        foreach ($products as $product) {
            $product->setRelation('variants', $this->pieces($viewer, $wishlist, fn (Builder $query) => $query
                ->where('product_id', $product->id)
                ->orderBy('colors.produced_from')
                ->orderBy('colors.name')));
        }

        return ['colors' => $colors, 'products' => $products];
    }

    /** @return Collection<int, Color> */
    private function matchColors(?string $term, ?int $colorId): Collection
    {
        return Color::with('line')
            ->when($colorId, fn (Builder $query, $id) => $query->whereKey($id))
            ->when($term, fn (Builder $query, $value) => $query->where(fn (Builder $inner) => $inner
                ->where('name', 'like', "%{$value}%")
                ->orWhereIn('id', ColorAlias::where('decision', AliasDecision::Mapped)
                    ->where('external_key', 'like', '%'.mb_strtolower($value).'%')
                    ->select('color_id'))))
            ->orderBy('name')
            ->orderBy('produced_from')
            ->limit(self::MAX_MATCHES)
            ->get();
    }

    /** @return Collection<int, Product> */
    private function matchProducts(?string $term, ?int $productId): Collection
    {
        return Product::with('line')
            ->when($productId, fn (Builder $query, $id) => $query->whereKey($id))
            ->when($term, fn (Builder $query, $value) => $query->where(fn (Builder $inner) => $inner
                ->where('name', 'like', "%{$value}%")
                ->orWhereIn('id', ProductAlias::where('decision', AliasDecision::Mapped)
                    ->where('external_key', 'like', '%'.mb_strtolower($value).'%')
                    ->select('product_id'))))
            ->orderBy('name')
            ->limit(self::MAX_MATCHES)
            ->get();
    }

    /**
     * Plain pieces only. A decal is its own hunt and is found from the piece it
     * is on.
     *
     * @param  array{variants: array<int, true>, products: array<int, true>}  $wishlist
     * @return Collection<int, Variant>
     */
    private function pieces(User $viewer, array $wishlist, callable $scope): Collection
    {
        $query = Variant::query()
            ->with(['product', 'color'])
            ->withCount(['holdings' => fn (Builder $holdings) => $holdings->where('holdings.user_id', $viewer->id)])
            ->whereNull('decoration_id')
            ->join('products', 'products.id', '=', 'variants.product_id')
            ->join('colors', 'colors.id', '=', 'variants.color_id')
            ->addSelect('variants.*');

        $variants = $scope($query)->get();

        foreach ($variants as $variant) {
            $variant->setAttribute('wishlisted', isset($wishlist['variants'][$variant->id]) || isset($wishlist['products'][$variant->product_id]));
        }

        return $variants;
    }

    /**
     * The viewer's open wishlist as lookups: exact pieces, and products wanted
     * in any color.
     *
     * @return array{variants: array<int, true>, products: array<int, true>}
     */
    private function openWishlist(User $viewer): array
    {
        $items = WishlistItem::open()->where('user_id', $viewer->id)->get(['product_id', 'variant_id']);

        return [
            'variants' => $items->whereNotNull('variant_id')->pluck('variant_id')->flip()->map(fn () => true)->all(),
            'products' => $items->whereNull('variant_id')->pluck('product_id')->flip()->map(fn () => true)->all(),
        ];
    }
}
