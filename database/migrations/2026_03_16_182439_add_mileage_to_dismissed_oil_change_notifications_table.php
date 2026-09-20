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
        Schema::table('dismissed_oil_change_notifications', function (Blueprint $table) {
            $table->integer('mileage_at_dismissal')->nullable()->after('vehicle_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dismissed_oil_change_notifications', function (Blueprint $table) {
            $table->dropColumn('mileage_at_dismissal');
        });
    }
};
