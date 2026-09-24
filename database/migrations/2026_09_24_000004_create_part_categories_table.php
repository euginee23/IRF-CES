<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Parts were categorised by a free-text string, which drifted: the admin
     * screen offered eight hardcoded names ("Display & Input Components" and
     * friends) while the parts themselves carried short ones ("Display",
     * "Battery"). The two lists had no value in common, so filtering by
     * category matched nothing at all.
     *
     * A table rather than an enum or a config list, because the taxonomy is
     * still being settled and adding a category should not need a deploy.
     */
    public function up(): void
    {
        Schema::create('part_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // Shop order, not alphabetical — screens and batteries are most of
            // the work and belong at the top of the picker.
            $table->integer('sort_order')->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('parts', function (Blueprint $table) {
            // Nullable: a part whose category was blank stays uncategorised
            // rather than being forced into an invented bucket.
            $table->foreignId('part_category_id')
                ->nullable()
                ->after('name')
                ->constrained('part_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('part_category_id');
        });

        Schema::dropIfExists('part_categories');
    }
};
