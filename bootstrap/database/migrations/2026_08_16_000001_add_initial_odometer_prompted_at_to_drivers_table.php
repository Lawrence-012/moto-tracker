<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tracks whether a driver has already submitted an actual reading for
     * the "vehicle + current odometer" prompt shown at login, used to
     * establish an accurate baseline for maintenance tracking. Only set
     * when the driver submits a reading - if they skip, this stays null
     * so the prompt appears again on their next login.
     */
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            if (!Schema::hasColumn('drivers', 'initial_odometer_prompted_at')) {
                $table->timestamp('initial_odometer_prompted_at')->nullable()->after('location_consent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            if (Schema::hasColumn('drivers', 'initial_odometer_prompted_at')) {
                $table->dropColumn('initial_odometer_prompted_at');
            }
        });
    }
};