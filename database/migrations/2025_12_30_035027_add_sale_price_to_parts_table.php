<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->renameColumn('unit_price', 'unit_cost_price');
        });
        
        Schema::table('parts', function (Blueprint $table) {
            $table->decimal('unit_sale_price', 10, 2)->after('unit_cost_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->dropColumn('unit_sale_price');
        });
        
        Schema::table('parts', function (Blueprint $table) {
            $table->renameColumn('unit_cost_price', 'unit_price');
        });
    }
};
