<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds office_id to divisions when the table was created without it.
     */
    public function up(): void
    {
        if (Schema::hasTable('divisions') && ! Schema::hasColumn('divisions', 'office_id')) {
            Schema::table('divisions', function (Blueprint $table) {
                $table->foreignId('office_id')->nullable()->after('division_abbr')
                    ->constrained('offices')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('divisions', 'office_id')) {
            Schema::table('divisions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('office_id');
            });
        }
    }
};
