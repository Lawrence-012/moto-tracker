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
        Schema::table('vehicle_travel_history', function (Blueprint $table) {
            if (!Schema::hasColumn('vehicle_travel_history', 'odometer_start_km')) {
                $table->integer('odometer_start_km')->nullable()->after('departure_time');
            }

            if (!Schema::hasColumn('vehicle_travel_history', 'odometer_end_km')) {
                $table->integer('odometer_end_km')->nullable()->after('odometer_start_km');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_travel_history', function (Blueprint $table) {
            if (Schema::hasColumn('vehicle_travel_history', 'odometer_end_km')) {
                $table->dropColumn('odometer_end_km');
            }

            if (Schema::hasColumn('vehicle_travel_history', 'odometer_start_km')) {
                $table->dropColumn('odometer_start_km');
            }
        });
    }
};
