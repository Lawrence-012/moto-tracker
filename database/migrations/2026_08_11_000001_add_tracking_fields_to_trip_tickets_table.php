<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds the columns needed to drive the trip tracker:
     * - odometer readings taken by the driver at the start/end of the trip
     * - the actual departure/arrival timestamps (as opposed to the scheduled ones on the itinerary)
     * - the GPS coordinates captured at departure/arrival
     * - a flag noting whether the trip was started automatically (CLSU geofence exit)
     *   or manually by the driver/admin
     */
    public function up(): void
    {
        Schema::table('trip_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('trip_tickets', 'odometer_start_km')) {
                $table->unsignedInteger('odometer_start_km')->nullable()->after('status');
            }
            if (!Schema::hasColumn('trip_tickets', 'odometer_end_km')) {
                $table->unsignedInteger('odometer_end_km')->nullable()->after('odometer_start_km');
            }
            if (!Schema::hasColumn('trip_tickets', 'actual_departure_at')) {
                $table->timestamp('actual_departure_at')->nullable()->after('odometer_end_km');
            }
            if (!Schema::hasColumn('trip_tickets', 'actual_arrival_at')) {
                $table->timestamp('actual_arrival_at')->nullable()->after('actual_departure_at');
            }
            if (!Schema::hasColumn('trip_tickets', 'start_latitude')) {
                $table->decimal('start_latitude', 10, 7)->nullable()->after('actual_arrival_at');
            }
            if (!Schema::hasColumn('trip_tickets', 'start_longitude')) {
                $table->decimal('start_longitude', 10, 7)->nullable()->after('start_latitude');
            }
            if (!Schema::hasColumn('trip_tickets', 'end_latitude')) {
                $table->decimal('end_latitude', 10, 7)->nullable()->after('start_longitude');
            }
            if (!Schema::hasColumn('trip_tickets', 'end_longitude')) {
                $table->decimal('end_longitude', 10, 7)->nullable()->after('end_latitude');
            }
            if (!Schema::hasColumn('trip_tickets', 'auto_started')) {
                $table->boolean('auto_started')->default(false)->after('end_longitude');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trip_tickets', function (Blueprint $table) {
            foreach ([
                'odometer_start_km',
                'odometer_end_km',
                'actual_departure_at',
                'actual_arrival_at',
                'start_latitude',
                'start_longitude',
                'end_latitude',
                'end_longitude',
                'auto_started',
            ] as $column) {
                if (Schema::hasColumn('trip_tickets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
