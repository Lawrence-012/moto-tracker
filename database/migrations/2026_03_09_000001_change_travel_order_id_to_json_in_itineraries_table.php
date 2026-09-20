<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('itineraries', 'travel_order_id')) {
            return;
        }

        $foreignKeys = collect(DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'itineraries'
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        "))->pluck('CONSTRAINT_NAME')->all();

        if (in_array('itineraries_travel_order_id_foreign', $foreignKeys, true)) {
            Schema::table('itineraries', function (Blueprint $table) {
                $table->dropForeign(['travel_order_id']);
            });
        }

        DB::statement('ALTER TABLE itineraries MODIFY COLUMN travel_order_id JSON NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('itineraries', 'travel_order_id')) {
            return;
        }

        DB::statement('ALTER TABLE itineraries MODIFY COLUMN travel_order_id BIGINT UNSIGNED NULL');
    }
};
