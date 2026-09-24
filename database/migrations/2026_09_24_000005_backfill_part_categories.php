<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Turn each distinct parts.category string into a real category row and
     * point the parts at it.
     *
     * Deliberately faithful: whatever names the shop has been using become
     * the starting taxonomy, near-duplicates included. Merging "Motherboard"
     * into "Motherboard & Internal Components" is a judgement call about their
     * stock, so it belongs to them in the admin screen, not to this migration.
     *
     * Query builder rather than the Part model, because the model's casts and
     * fillable change in later phases and a migration bound to them breaks on
     * the next fresh migrate.
     */
    public function up(): void
    {
        $names = DB::table('parts')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        foreach ($names as $index => $name) {
            $name = trim($name);

            if ($name === '') {
                continue;
            }

            // firstOrCreate by hand: re-running this on a database that has
            // already been backfilled must not duplicate the category or
            // trip the unique index.
            $categoryId = DB::table('part_categories')->where('name', $name)->value('id');

            if ($categoryId === null) {
                $categoryId = DB::table('part_categories')->insertGetId([
                    'name' => $name,
                    'slug' => $this->uniqueSlug($name),
                    'sort_order' => $index,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('parts')
                ->where('category', $name)
                ->whereNull('part_category_id')
                ->update(['part_category_id' => $categoryId]);
        }
    }

    /**
     * Two different names can slug the same ("Display & Input" and
     * "Display Input"), and slug carries a unique index.
     */
    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $suffix = 2;

        while (DB::table('part_categories')->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    public function down(): void
    {
        DB::table('parts')->update(['part_category_id' => null]);
        DB::table('part_categories')->delete();
    }
};
