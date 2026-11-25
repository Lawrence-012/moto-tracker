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
        $foreignKeys = collect(DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'trip_tickets'
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        "))->pluck('CONSTRAINT_NAME')->all();

        Schema::table('trip_tickets', function (Blueprint $table) use ($foreignKeys) {
            if (in_array('trip_tickets_travel_order_id_foreign', $foreignKeys, true)) {
                $table->dropForeign(['travel_order_id']);
            }

            if (in_array('trip_tickets_itinerary_id_foreign', $foreignKeys, true)) {
                $table->dropForeign(['itinerary_id']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
