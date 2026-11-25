<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tracks when the vehicle was last inside the Motorpool Gate, and how many fixes in a row it has been there.
     */
    public function up(): void
    {
        Schema::table('dashcams', function (Blueprint $table) {
            if (! Schema::hasColumn('dashcams', 'last_motorpool_at')) {
                $table->timestamp('last_motorpool_at')->nullable();
            }
            if (! Schema::hasColumn('dashcams', 'motorpool_streak')) {
                $table->unsignedInteger('motorpool_streak')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('dashcams', function (Blueprint $table) {
            $table->dropColumn(['last_motorpool_at', 'motorpool_streak']);
        });
    }
};
