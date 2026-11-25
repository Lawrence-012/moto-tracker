<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * odometer_baseline_km: the vehicle's odometer when the dashcam was registered.
     * distance_km: distance the dashcam has measured from its GPS fixes since then.
     * Current odometer = baseline + distance.
     */
    public function up(): void
    {
        Schema::table('dashcams', function (Blueprint $table) {
            $table->unsignedInteger('odometer_baseline_km')->default(0)->after('registered_at');
            $table->decimal('distance_km', 12, 3)->default(0)->after('odometer_baseline_km');
        });
    }

    public function down(): void
    {
        Schema::table('dashcams', function (Blueprint $table) {
            $table->dropColumn(['odometer_baseline_km', 'distance_km']);
        });
    }
};
