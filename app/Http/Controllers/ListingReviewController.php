<?php

namespace App\Http\Controllers;

use App\Enums\AliasDecision;
use App\Enums\ListingNameKind;
use App\Enums\ListingSource;
use App\Http\Requests\CreateFromListingNameRequest;
use App\Http\Requests\CreateProductsFromListingsRequest;
use App\Http\Requests\IndexListingNamesRequest;
use App\Http\Requests\RuleListingNameRequest;
use App\Http\Requests\UndoListingRulingRequest;
use App\Http\Resources\ListingNameResource;
use App\Services\Sources\ListingReviewService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class ListingReviewController extends Controller
{
    public function __construct(
        private ListingReviewService $review,
    ) {}

    public function index(IndexListingNamesRequest $request, ListingSource $source): JsonResponse
    {
        $names = $this->review->names(
            $source,
            ListingNameKind::from($request->validated('kind')),
            $request->boolean('ruled'),
        );

        return $this->success(ListingNameResource::collection($names));
    }

    public function rule(RuleListingNameRequest $request, ListingSource $source): JsonResponse
    {
        try {
            $name = $this->review->rule(
                $source,
                ListingNameKind::from($request->validated('kind')),
                $request->validated('key'),
                AliasDecision::from($request->validated('decision')),
                $request->validated('target_id'),
            );
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(new ListingNameResource($name));
    }

    public function create(CreateFromListingNameRequest $request, ListingSource $source): JsonResponse
    {
        try {
            $name = $this->review->create(
                $source,
                ListingNameKind::from($request->validated('kind')),
                $request->validated('key'),
                $request->safe()->only(['name', 'produced_from', 'produced_to']),
            );
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->created(new ListingNameResource($name));
    }

    public function createProducts(CreateProductsFromListingsRequest $request, ListingSource $source): JsonResponse
    {
        try {
            $created = $this->review->createProducts($source, $request->validated('keys'));
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->created(['created' => $created], "Created {$created} products.");
    }

    public function undo(UndoListingRulingRequest $request, ListingSource $source): JsonResponse
    {
        try {
            $name = $this->review->undo(
                $source,
                ListingNameKind::from($request->validated('kind')),
                $request->validated('key'),
            );
        } catch (RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(new ListingNameResource($name));
    }
}
