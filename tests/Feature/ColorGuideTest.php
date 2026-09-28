<?php

namespace Tests\Feature;

use App\Models\Color;
use App\Models\Line;
use App\Models\Product;
use App\Models\Variant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ColorGuideTest extends TestCase
{
    use RefreshDatabase;

    private Line $fiesta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fiesta = Line::create(['name' => 'Fiesta', 'slug' => 'fiesta']);
        Product::create(['line_id' => $this->fiesta->id, 'name' => 'Carafe']);
    }

    public function test_the_guide_sets_swatches_and_years_matching_reused_names_by_nearest_year(): void
    {
        $vintage = $this->color('Cobalt', 1936, 1951);
        $modern = $this->color('Cobalt', 1986, null);
        $heather = $this->color('Heather', 2006, 2009);
        $orangeRed = $this->color('Red (Orange Red)', null, null);

        $this->artisan('fiesta:apply-color-guide')->assertSuccessful();

        $this->assertSame([1936, 1951, '#13294b'], $this->facts($vintage));
        $this->assertSame([1987, 2021, '#13294b'], $this->facts($modern));
        $this->assertSame([2006, 2008, '#62374e'], $this->facts($heather));
        $this->assertSame([1959, 1972, '#d2042d'], $this->facts($orangeRed));
    }

    public function test_a_color_the_catalog_lacks_is_added_with_its_share_of_the_catalog(): void
    {
        $this->artisan('fiesta:apply-color-guide')->assertSuccessful();

        $raspberry = Color::where('name', 'Raspberry')->sole();

        $this->assertSame([1997, 1997, '#892a33'], $this->facts($raspberry));
        $this->assertTrue(Variant::where('color_id', $raspberry->id)->exists());
    }

    public function test_running_it_again_changes_nothing(): void
    {
        $this->artisan('fiesta:apply-color-guide')->assertSuccessful();
        $count = Color::count();
        $updated = Color::max('updated_at');

        $this->travel(1)->minutes();
        $this->artisan('fiesta:apply-color-guide')->assertSuccessful();

        $this->assertSame($count, Color::count());
        $this->assertSame($updated, Color::max('updated_at'));
    }

    private function color(string $name, ?int $from, ?int $to): Color
    {
        return Color::create(['line_id' => $this->fiesta->id, 'name' => $name, 'produced_from' => $from, 'produced_to' => $to]);
    }

    /**
     * @return array{0: ?int, 1: ?int, 2: ?string}
     */
    private function facts(Color $color): array
    {
        $color->refresh();

        return [$color->produced_from, $color->produced_to, $color->hex];
    }
}
