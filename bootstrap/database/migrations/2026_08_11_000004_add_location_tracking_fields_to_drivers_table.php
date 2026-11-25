<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            if (!Schema::hasColumn('drivers', 'location_sharing_enabled')) {
                $table->boolean('location_sharing_enabled')->default(false)->after('availability_status');
            }
            if (!Schema::hasColumn('drivers', 'location_consent_at')) {
                $table->timestamp('location_consent_at')->nullable()->after('location_sharing_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            foreach (['location_sharing_enabled', 'location_consent_at'] as $column) {
                if (Schema::hasColumn('drivers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
