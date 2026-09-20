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
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable()->after('destination');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->string('region', 100)->nullable()->after('longitude');
            $table->decimal('distance_km', 8, 2)->nullable()->after('region')->comment('Distance from CLSU in kilometers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'region', 'distance_km']);
        });
    }
};
