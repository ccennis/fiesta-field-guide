<?php

namespace App\Services\Sources;

use App\Services\BaseService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use RuntimeException;

/**
 * Reads the public storefront catalog the store's robots.txt allows. It names
 * itself, pauses between pages, and saves every raw page so a parser change
 * can be replayed without fetching again.
 */
class FiestaFactoryDirectClient extends BaseService
{
    private const PAGE_SIZE = 250;

    private const MAX_PAGES = 20;

    public const SNAPSHOT_DIR = 'sources/fiesta-factory-direct';

    /**
     * @return array{products: array<int, array<string, mixed>>, snapshot: string}
     */
    public function fetch(): array
    {
        $snapshot = now()->format('Y-m-d-His');
        $products = [];

        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            if ($page > 1) {
                Sleep::for(1)->second();
            }

            $response = Http::withUserAgent($this->userAgent())
                ->acceptJson()
                ->timeout(30)
                ->retry(2, 2000)
                ->get(FiestaFactoryDirectParser::STORE_URL.'/products.json', [
                    'limit' => self::PAGE_SIZE,
                    'page' => $page,
                ])
                ->throw();

            $batch = $response->json('products') ?? [];

            if ($batch === []) {
                return ['products' => $products, 'snapshot' => $snapshot];
            }

            Storage::disk('local')->put(self::SNAPSHOT_DIR."/{$snapshot}/page-{$page}.json", $response->body());
            array_push($products, ...$batch);
        }

        throw new RuntimeException('The store returned more than '.self::MAX_PAGES.' pages. Stopped rather than keep paging.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fromSnapshot(string $snapshot): array
    {
        $files = Storage::disk('local')->files(self::SNAPSHOT_DIR."/{$snapshot}");

        if ($files === []) {
            throw new RuntimeException('No snapshot found at storage/app/private/'.self::SNAPSHOT_DIR."/{$snapshot}.");
        }

        natsort($files);
        $products = [];

        foreach ($files as $file) {
            array_push($products, ...(json_decode(Storage::disk('local')->get($file), true)['products'] ?? []));
        }

        return $products;
    }

    private function userAgent(): string
    {
        return 'FiestaFieldGuide/1.0 (+'.config('app.url').')';
    }
}
