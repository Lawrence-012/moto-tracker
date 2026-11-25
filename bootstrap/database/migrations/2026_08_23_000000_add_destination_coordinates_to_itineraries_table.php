<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Stores the confirmed pin location for an itinerary's destination
     * (set via the "Search" + draggable-pin picker on the itinerary
     * create/edit forms), so drivers get an exact point instead of just a
     * free-text address to interpret.
     */
    public function up(): void
    {
        Schema::table('itineraries', function (Blueprint $table) {
            $table->decimal('destination_latitude', 10, 7)->nullable()->after('destination');
            $table->decimal('destination_longitude', 10, 7)->nullable()->after('destination_latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('itineraries', function (Blueprint $table) {
            $table->dropColumn(['destination_latitude', 'destination_longitude']);
        });
    }
};
