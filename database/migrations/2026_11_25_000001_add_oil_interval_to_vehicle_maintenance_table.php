<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_maintenance', function (Blueprint $table) {
            if (!Schema::hasColumn('vehicle_maintenance', 'remarks')) {
                $table->text('remarks')->nullable();
            }
            if (!Schema::hasColumn('vehicle_maintenance', 'oil_interval_km')) {
                // Km the oil change is good for: 5000 or 10000
                $table->unsignedInteger('oil_interval_km')->nullable();
            }
            if (!Schema::hasColumn('vehicle_maintenance', 'oil_interval_months')) {
                // Months the oil change is good for: 3 or 6
                $table->unsignedTinyInteger('oil_interval_months')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_maintenance', function (Blueprint $table) {
            foreach (['remarks', 'oil_interval_km', 'oil_interval_months'] as $col) {
                if (Schema::hasColumn('vehicle_maintenance', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
