<?php

namespace App\Services;

use App\Enums\WishlistPriority;
use App\Enums\WishlistSource;
use App\Models\Holding;
use App\Models\Variant;
use App\Models\WishlistItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

/**
 * The wishlist names either one exact variant or a product in any plain color.
 * An any-color item never matches a decorated piece, since a decal is its own
 * thing to hunt for. Fulfilled items are kept rather than deleted.
 */
class WishlistService extends BaseService
{
    public function __construct(
        private ValuationService $valuationService,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     * @return Collection<int, WishlistItem>
     */
    public function list(array $input): Collection
    {
        $fulfilled = isset($input['fulfilled']) ? filter_var($input['fulfilled'], FILTER_VALIDATE_BOOLEAN) : null;
        $priority = isset($input['priority']) ? WishlistPriority::from($input['priority']) : null;
        $lineId = isset($input['line_id']) ? (int) $input['line_id'] : null;
        $productId = isset($input['product_id']) ? (int) $input['product_id'] : null;

        $items = WishlistItem::with(['product.line', 'variant.color', 'variant.decoration'])
            ->when($fulfilled !== null, fn (Builder $query) => $fulfilled
                ? $query->whereNotNull('fulfilled_at')
                : $query->open())
            ->when($priority, fn (Builder $query, $value) => $query->where('priority', $value))
            ->when($lineId, fn (Builder $query, $id) => $query->whereHas('product', fn (Builder $product) => $product->where('line_id', $id)))
            ->when($productId, fn (Builder $query, $id) => $query->where('product_id', $id))
            ->orderByRaw('priority = ? desc', [WishlistPriority::Grail->value])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $this->attachValues($items);

        return $items;
    }

    public function create(array $data): WishlistItem
    {
        $variantId = $data['variant_id'] ?? null;

        if ($variantId !== null && (int) Variant::whereKey($variantId)->value('product_id') !== (int) $data['product_id']) {
            throw new RuntimeException('That variant belongs to a different product.');
        }

        if ($this->openFor((int) $data['product_id'], $variantId) !== null) {
            throw new RuntimeException('This is already on your wishlist.');
        }

        $item = WishlistItem::create($data + [
            'priority' => WishlistPriority::Want,
            'source' => WishlistSource::Manual,
        ]);

        return $this->loaded($item);
    }

    /**
     * `fulfilled` false reopens an item, as long as nothing else is already
     * open for the same target.
     */
    public function update(WishlistItem $item, array $data): WishlistItem
    {
        if (array_key_exists('fulfilled', $data)) {
            $data += $data['fulfilled']
                ? ['fulfilled_at' => $item->fulfilled_at ?? now()]
                : $this->reopenFields($item);

            unset($data['fulfilled']);
        }

        $item->update($data);

        return $this->loaded($item->fresh());
    }

    public function delete(WishlistItem $item): void
    {
        $item->delete();
    }

    /**
     * The open item a variant satisfies: the exact variant first, then the
     * product in any color when the piece is plain.
     */
    public function matchFor(Variant $variant): ?WishlistItem
    {
        $exact = WishlistItem::open()->where('variant_id', $variant->id)->first();

        if ($exact !== null || $variant->decoration_id !== null) {
            return $exact;
        }

        return WishlistItem::open()
            ->where('product_id', $variant->product_id)
            ->whereNull('variant_id')
            ->first();
    }

    public function fulfill(Holding $holding): ?WishlistItem
    {
        $item = $this->matchFor($holding->variant);

        if ($item === null) {
            return null;
        }

        $item->update([
            'fulfilled_at' => now(),
            'fulfilled_by_holding_id' => $holding->id,
        ]);

        return $item;
    }

    /**
     * Constrain a variant query to variants on, or off, the open wishlist.
     */
    public function constrainVariants(Builder $query, bool $wishlisted): Builder
    {
        $onWishlist = fn (Builder $inner) => $inner
            ->whereHas('wishlistItems', fn (Builder $items) => $items->open())
            ->orWhere(fn (Builder $plain) => $plain
                ->whereNull('variants.decoration_id')
                ->whereHas('product.wishlistItems', fn (Builder $items) => $items->open()->whereNull('variant_id')));

        return $wishlisted ? $query->where($onWishlist) : $query->whereNot($onWishlist);
    }

    /**
     * @return array<string, int>
     */
    public function counts(): array
    {
        return [
            'wishlist_open' => WishlistItem::open()->count(),
            'grails_open' => WishlistItem::open()->where('priority', WishlistPriority::Grail)->count(),
        ];
    }

    /**
     * @param  Collection<int, WishlistItem>  $items
     */
    private function attachValues(Collection $items): void
    {
        $variants = $items->whereNotNull('variant_id')->pluck('variant');
        $byVariant = $this->valuationService->resolveMany($variants);
        $byProduct = $this->valuationService->resolveForProducts(
            $items->whereNull('variant_id')->pluck('product_id')->toBase()
        );

        foreach ($items as $item) {
            $item->setRelation('resolvedValue', $item->isAnyColor()
                ? ($byProduct[$item->product_id] ?? null)
                : ($byVariant[$item->variant_id] ?? null));
        }
    }

    private function loaded(WishlistItem $item): WishlistItem
    {
        $item->load(['product.line', 'variant.color', 'variant.decoration']);
        $this->attachValues(new Collection([$item]));

        return $item;
    }

    /**
     * @return array<string, null>
     */
    private function reopenFields(WishlistItem $item): array
    {
        if ($this->openFor($item->product_id, $item->variant_id, $item->id) !== null) {
            throw new RuntimeException('Something is already open on your wishlist for this piece.');
        }

        return ['fulfilled_at' => null, 'fulfilled_by_holding_id' => null];
    }

    private function openFor(int $productId, ?int $variantId, ?int $exceptId = null): ?WishlistItem
    {
        return WishlistItem::open()
            ->where('product_id', $productId)
            ->when(
                $variantId,
                fn (Builder $query, $id) => $query->where('variant_id', $id),
                fn (Builder $query) => $query->whereNull('variant_id')
            )
            ->when($exceptId, fn (Builder $query, $id) => $query->whereKeyNot($id))
            ->first();
    }
}
