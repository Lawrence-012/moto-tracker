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
        if (!Schema::hasTable('trip_tickets') || Schema::hasColumn('trip_tickets', 'completion_file')) {
            return;
        }

        Schema::table('trip_tickets', function (Blueprint $table) {
            $table->string('completion_file')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('trip_tickets') || !Schema::hasColumn('trip_tickets', 'completion_file')) {
            return;
        }

        Schema::table('trip_tickets', function (Blueprint $table) {
            $table->dropColumn('completion_file');
        });
    }
};
