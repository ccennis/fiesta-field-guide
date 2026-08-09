<?php

namespace Tests\Feature;

use App\Enums\ValueSource;
use App\Models\Color;
use App\Models\Line;
use App\Models\Product;
use App\Models\ValueObservation;
use App\Models\Variant;
use App\Services\ValuationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValuationServiceTest extends TestCase
{
    use RefreshDatabase;

    private Variant $variant;

    private Product $product;

    private Color $color;

    protected function setUp(): void
    {
        parent::setUp();

        $line = Line::create(['name' => 'Fiesta', 'slug' => 'fiesta']);
        $this->product = Product::create(['line_id' => $line->id, 'name' => '10" plate']);
        $this->color = Color::create(['line_id' => $line->id, 'name' => 'Lilac', 'produced_from' => 1993, 'produced_to' => 1995]);
        $this->variant = Variant::create(['product_id' => $this->product->id, 'color_id' => $this->color->id]);
    }

    public function test_it_falls_back_to_the_product_level_figure_when_no_variant_figure_exists(): void
    {
        $this->observation(null, 15.00, '2026-01-01');

        $resolved = app(ValuationService::class)->resolve($this->variant);

        $this->assertSame('15.00', $resolved->amount);
        $this->assertNull($resolved->color_id);
    }

    public function test_a_variant_figure_beats_a_newer_product_level_figure(): void
    {
        $this->observation(null, 15.00, '2026-06-01');
        $this->observation($this->color->id, 60.00, '2026-01-01');

        $resolved = app(ValuationService::class)->resolve($this->variant);

        $this->assertSame('60.00', $resolved->amount);
    }

    public function test_the_most_recent_figure_wins_within_the_same_scope(): void
    {
        $this->observation($this->color->id, 60.00, '2026-01-01');
        $this->observation($this->color->id, 75.00, '2026-06-01');

        $resolved = app(ValuationService::class)->resolve($this->variant);

        $this->assertSame('75.00', $resolved->amount);
    }

    public function test_it_returns_null_rather_than_inventing_a_figure(): void
    {
        $this->assertNull(app(ValuationService::class)->resolve($this->variant));
    }

    public function test_history_returns_every_applicable_observation(): void
    {
        $this->observation(null, 15.00, '2026-01-01');
        $this->observation($this->color->id, 60.00, '2026-06-01');

        $this->assertCount(2, app(ValuationService::class)->history($this->variant));
    }

    private function observation(?int $colorId, float $amount, string $date): void
    {
        ValueObservation::create([
            'product_id' => $this->product->id,
            'color_id' => $colorId,
            'amount' => $amount,
            'source' => $colorId ? ValueSource::OwnerEstimate : ValueSource::DefaultSchedule,
            'observed_on' => $date,
        ]);
    }
}
