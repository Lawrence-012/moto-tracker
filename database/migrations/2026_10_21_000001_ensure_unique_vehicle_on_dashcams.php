<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One dashcam per vehicle, enforced by the database.
     * Adds a unique index on dashcams.vehicle_id if it is missing.
     */
    public function up(): void
    {
        $hasUnique = DB::select(
            "SHOW INDEX FROM dashcams WHERE Column_name = 'vehicle_id' AND Non_unique = 0"
        );

        if (empty($hasUnique)) {
            Schema::table('dashcams', function (Blueprint $table) {
                $table->unique('vehicle_id', 'dashcams_vehicle_id_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('dashcams', function (Blueprint $table) {
            $table->dropUnique('dashcams_vehicle_id_unique');
        });
    }
};
