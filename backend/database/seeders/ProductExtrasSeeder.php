<?php

namespace Database\Seeders;

use App\Models\Industry;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Industries and example variants for the demo products. Idempotent.
 * `php artisan db:seed --class=ProductExtrasSeeder --force`
 */
class ProductExtrasSeeder extends Seeder
{
    public function run(): void
    {
        if (Industry::doesntExist()) {
            foreach ([
                ['Beverage & Water', 'droplet'], ['Food', 'package'], ['Pharmaceutical', 'flask'], ['Cosmetics & Personal Care', 'sparkles'],
                ['Household & Chemicals', 'box'], ['Dairy', 'bottle'], ['Agro & Industrial', 'factory'],
            ] as [$name, $icon]) {
                Industry::create(compact('name', 'icon'));
            }
        }

        $map = [
            'pet-water-bottle' => ['beverage-water', 'dairy'],
            'hdpe-pharma-bottle' => ['pharmaceutical'],
            'flip-top-cap' => ['cosmetics-personal-care', 'food', 'household-chemicals'],
            'screw-cap' => ['beverage-water', 'food', 'pharmaceutical', 'household-chemicals'],
            'cosmetic-jar' => ['cosmetics-personal-care'],
            'food-storage-jar' => ['food'],
            'hdpe-gallon-container' => ['household-chemicals', 'agro-industrial', 'food'],
            'pet-preform' => ['beverage-water'],
        ];
        foreach ($map as $slug => $industrySlugs) {
            $product = Product::where('slug', $slug)->first();
            if ($product && $product->industries()->doesntExist()) {
                $product->industries()->sync(Industry::whereIn('slug', $industrySlugs)->pluck('id'));
            }
        }

        $water = Product::where('slug', 'pet-water-bottle')->first();
        if ($water && $water->variants()->doesntExist()) {
            foreach ([
                ['250 ml', '250 ml', '12 g', '28 mm PCO 1881', 'Clear', '20,000 pcs'],
                ['500 ml', '500 ml', '16 g', '28 mm PCO 1881', 'Clear / Blue tint', '20,000 pcs'],
                ['1 L', '1000 ml', '24 g', '28 mm PCO 1881', 'Clear', '10,000 pcs'],
                ['2 L', '2000 ml', '38 g', '28 mm PCO 1881', 'Clear', '10,000 pcs'],
            ] as $i => [$name, $capacity, $weight, $neck, $color, $moq]) {
                $water->variants()->create(['name' => $name, 'capacity' => $capacity, 'weight' => $weight, 'neck_size' => $neck, 'color' => $color, 'moq' => $moq, 'sort_order' => $i + 1]);
            }
        }
    }
}
