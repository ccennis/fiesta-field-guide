<?php

namespace Tests\Feature;

use App\Enums\ValueSource;
use App\Enums\VariantExistence;
use App\Models\Color;
use App\Models\Holding;
use App\Models\Line;
use App\Models\ValueObservation;
use App\Models\Variant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Runs the real collection export through both importers. These assertions
 * pin the decisions that were made about the source data's ambiguities.
 */
class SeedDataImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('fiesta:import-catalog');
        $this->artisan('fiesta:import-holdings');
    }

    public function test_it_imports_every_owned_piece_as_its_own_row(): void
    {
        // 136 from the detail tab, one blank quantity resolved by its recorded
        // value, and 13 from colors only the matrix tab knows about.
        $this->assertSame(150, Holding::count());
    }

    public function test_it_keeps_the_same_color_name_separate_across_lines(): void
    {
        $fiesta = Line::where('name', 'Fiesta')->first();
        $harlequin = Line::where('name', 'Harlequin')->first();

        $fiestaRed = Color::where('line_id', $fiesta->id)->where('name', 'Red')->first();
        $harlequinRed = Color::where('line_id', $harlequin->id)->where('name', 'Red')->first();

        $this->assertNotNull($fiestaRed);
        $this->assertNotNull($harlequinRed);
        $this->assertNotSame($fiestaRed->id, $harlequinRed->id);

        // Fiesta's years are never copied onto another line's color.
        $this->assertSame(1936, $fiestaRed->produced_from);
        $this->assertNull($harlequinRed->produced_from);
        $this->assertNull($harlequinRed->era);
    }

    public function test_it_splits_a_color_name_reused_across_eras(): void
    {
        $cobalts = Color::whereHas('line', fn ($query) => $query->where('name', 'Fiesta'))
            ->where('name', 'Cobalt')
            ->orderBy('produced_from')
            ->get();

        $this->assertCount(2, $cobalts);
        $this->assertSame([1936, 1986], $cobalts->pluck('produced_from')->all());
        $this->assertSame('vintage', $cobalts->first()->era->value);
        $this->assertSame('post_86', $cobalts->last()->era->value);
    }

    public function test_it_treats_the_value_column_as_extended_and_divides_by_quantity(): void
    {
        // The sheet records two Lilac teacups at $60.00 total, and a single
        // Harlequin Rose teacup at $30.00. The unit figure has to match.
        $observation = ValueObservation::whereHas('product', fn ($query) => $query->where('name', 'Teacup'))
            ->whereHas('color', fn ($query) => $query->where('name', 'Lilac'))
            ->first();

        $this->assertNotNull($observation);
        $this->assertSame('30.00', $observation->amount);
    }

    public function test_it_stores_blanket_figures_once_at_product_level(): void
    {
        $schedule = ValueObservation::where('source', ValueSource::DefaultSchedule)->get();

        $this->assertTrue($schedule->every(fn ($observation) => $observation->color_id === null));

        // A single $15 guess must not become one row per color.
        $this->assertLessThan(10, $schedule->count());
        $this->assertGreaterThan(0, $schedule->count());
    }

    public function test_it_only_confirms_variants_the_source_data_evidences(): void
    {
        $confirmed = Variant::where('existence', VariantExistence::Confirmed)->count();

        $this->assertGreaterThan(0, $confirmed);
        $this->assertLessThan(Variant::count(), $confirmed);

        // Anything owned must be confirmed; the catalog may never present a
        // generated combination as a real listing.
        $ownedButUnconfirmed = Variant::has('holdings')
            ->where('existence', VariantExistence::Unconfirmed)
            ->count();

        $this->assertSame(0, $ownedButUnconfirmed);
    }

    public function test_it_reads_a_decal_as_a_decoration_rather_than_a_color(): void
    {
        // "cat-face black" in the export is a holiday decal on the Black glaze,
        // not a color of its own, so it must never reach the color axis.
        $this->assertNull(Color::where('name', 'cat-face black')->first());

        $decorated = Variant::whereNotNull('decoration_id')->with(['color', 'product', 'decoration'])->first();

        $this->assertNotNull($decorated);
        $this->assertSame('Black', $decorated->color->name);
        $this->assertSame('T&J Mug', $decorated->product->name);
        $this->assertSame('Cat Face', $decorated->decoration->name);
        $this->assertSame('holiday', $decorated->decoration->category->value);

        // The decal carries its own production run, not the glaze's.
        $this->assertSame(2003, $decorated->decoration->produced_from);
        $this->assertSame(1986, $decorated->color->produced_from);
    }

    public function test_a_decorated_piece_is_distinct_from_its_plain_counterpart(): void
    {
        $decorated = Variant::whereNotNull('decoration_id')->first();

        $plain = Variant::whereNull('decoration_id')
            ->where('color_id', $decorated->color_id)
            ->where('product_id', $decorated->product_id)
            ->first();

        $this->assertNotNull($plain);
        $this->assertNotSame($plain->id, $decorated->id);
        $this->assertSame(1, $plain->holdings()->count());
        $this->assertSame(1, $decorated->holdings()->count());
    }

    public function test_decorated_variants_are_never_part_of_the_cross_product(): void
    {
        // A decorated variant exists only where the export evidences one, so
        // every decorated row must be backed by a piece actually owned.
        $this->assertSame(
            Variant::whereNotNull('decoration_id')->count(),
            Variant::whereNotNull('decoration_id')->has('holdings')->count()
        );
    }

    public function test_it_never_pairs_a_product_with_a_color_from_another_line(): void
    {
        $crossLine = Variant::join('products', 'products.id', '=', 'variants.product_id')
            ->join('colors', 'colors.id', '=', 'variants.color_id')
            ->whereColumn('products.line_id', '!=', 'colors.line_id')
            ->count();

        $this->assertSame(0, $crossLine);
    }
}
