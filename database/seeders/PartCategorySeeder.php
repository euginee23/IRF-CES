<?php

namespace Database\Seeders;

use App\Models\PartCategory;
use Illuminate\Database\Seeder;

class PartCategorySeeder extends Seeder
{
    /**
     * The eight buckets the shop sorts parts into.
     *
     * These are the names PartsTableSeeder normalises its parts onto and that
     * the admin screen used to hardcode. Seeding them means a fresh install
     * starts with the same taxonomy an existing one is backfilled into, so
     * the two do not drift apart again.
     *
     * @see \Database\Seeders\PartsTableSeeder
     */
    public const CATEGORIES = [
        'Display & Input Components',
        'Power & Charging Components',
        'Motherboard & Core Components',
        'Camera & Audio Components',
        'Network & Connectivity Components',
        'Sensors & Security Components',
        'Structural & Physical Components',
        'Accessories & External Parts',
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $index => $name) {
            PartCategory::updateOrCreate(
                ['name' => $name],
                ['sort_order' => $index, 'is_active' => true],
            );
        }
    }
}
