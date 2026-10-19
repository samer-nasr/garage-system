<?php

namespace Tests\Feature;

use App\Models\Listing;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_catalog_has_twelve_listings_per_section_with_local_images(): void
    {
        Storage::fake('public');
        $this->seed(DemoCatalogSeeder::class);

        foreach (['part', 'car', 'service'] as $type) {
            $this->assertSame(12, Listing::where('type', $type)->where('is_demo', true)->count());
        }

        foreach (Listing::all() as $listing) {
            Storage::disk('public')->assertExists($listing->images[0]);

            if (str_ends_with($listing->images[0], '.svg')) {
                $image = new \DOMDocument;
                $this->assertTrue($image->loadXML(Storage::disk('public')->get($listing->images[0])));
            }

            foreach (['lt', 'en', 'ru'] as $locale) {
                $this->assertNotEmpty($listing->title[$locale]);
                $this->assertNotEmpty($listing->description[$locale]);
            }
        }

        foreach (['parts', 'rentals', 'services'] as $section) {
            $this->get('/en/'.$section)->assertOk()->assertSee('/storage/demo/', false)->assertDontSee('part-placeholder');
        }
    }

    public function test_reseeding_preserves_real_inventory_stock_visibility_and_uploaded_images(): void
    {
        Storage::fake('public');
        $this->seed(DemoCatalogSeeder::class);

        $demo = Listing::where('part_number', 'DEMO-BRK-001')->firstOrFail();
        $demo->update(['stock' => 2, 'is_active' => false, 'images' => ['listings/owner-photo.jpg']]);

        $real = Listing::where('part_number', 'DEMO-LGT-002')->firstOrFail();
        $real->update(['is_demo' => false, 'price' => 222, 'title' => ['lt' => 'Owner listing']]);

        $this->seed(DemoCatalogSeeder::class);

        $this->assertDatabaseCount('listings', 36);
        $this->assertSame(2, $demo->fresh()->stock);
        $this->assertFalse($demo->fresh()->is_active);
        $this->assertSame(['listings/owner-photo.jpg'], $demo->fresh()->images);
        $this->assertSame('222.00', $real->fresh()->price);
        $this->assertSame(['lt' => 'Owner listing'], $real->fresh()->title);
        $this->assertFalse($real->fresh()->is_demo);
    }
}
