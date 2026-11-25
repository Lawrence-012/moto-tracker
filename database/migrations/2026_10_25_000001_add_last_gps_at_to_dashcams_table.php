<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Time of the last GPS fix (separate from last_seen_at, which is updated by check-ins too). */
    public function up(): void
    {
        Schema::table('dashcams', function (Blueprint $table) {
            $table->timestamp('last_gps_at')->nullable()->after('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::table('dashcams', function (Blueprint $table) {
            $table->dropColumn('last_gps_at');
        });
    }
};
