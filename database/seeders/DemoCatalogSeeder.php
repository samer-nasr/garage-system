<?php

namespace Database\Seeders;

use App\Models\Listing;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DemoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $entries = json_decode(file_get_contents(__DIR__.'/demo-catalog.json'), true, 512, JSON_THROW_ON_ERROR);

        foreach ($entries as $entry) {
            $source = public_path('images/demo/'.$entry['asset']);

            if (! is_file($source)) {
                throw new RuntimeException('Missing demonstration image: '.$entry['asset']);
            }

            Storage::disk('public')->put('demo/'.$entry['asset'], file_get_contents($source));
        }

        DB::transaction(function () use ($entries): void {
            foreach ($entries as $entry) {
                $image = 'demo/'.$entry['asset'];

                if ($entry['type'] === 'service') {
                    $entry['part_number'] = 'DEMO-SVC-'.md5($entry['service_key']);
                }

                unset($entry['asset'], $entry['service_key']);

                $listing = Listing::withTrashed()->firstOrNew(['type' => $entry['type'], 'part_number' => $entry['part_number']]);

                if ($listing->exists && ! $listing->is_demo) {
                    continue;
                }

                $entry['is_demo'] = true;

                if ($listing->exists) {
                    unset($entry['stock']);
                } else {
                    $entry['is_active'] = true;
                }

                $existingImages = $listing->images ?? [];
                $hasUploadedImages = collect($existingImages)->contains(fn (string $path): bool => ! str_starts_with($path, 'demo/'));

                if (! $hasUploadedImages) {
                    $entry['images'] = [$image];
                }

                $listing->fill($entry)->save();
            }
        });

        $this->command?->info('Demo catalog ready: 12 parts, 12 cars, and 12 garage services. Existing real listings, stock levels, uploaded photos, and visibility are preserved.');
    }
}
