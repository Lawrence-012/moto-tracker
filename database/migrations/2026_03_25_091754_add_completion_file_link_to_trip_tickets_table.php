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
        Schema::table('trip_tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('trip_tickets', 'completion_file')) {
                $table->string('completion_file')->nullable();
            }

            if (! Schema::hasColumn('trip_tickets', 'completion_file_link')) {
                $table->string('completion_file_link')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trip_tickets', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['completion_file_link'],
                fn (string $column) => Schema::hasColumn('trip_tickets', $column)
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
