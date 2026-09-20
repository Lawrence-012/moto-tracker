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
        Schema::table('emp_positions', function (Blueprint $table) {
            if (! Schema::hasColumn('emp_positions', 'position_name')) {
                $table->string('position_name')->after('id');
            }

            if (! Schema::hasColumn('emp_positions', 'description')) {
                $table->text('description')->nullable();
            }

            if (! Schema::hasColumn('emp_positions', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emp_positions', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['position_name', 'description', 'is_active'],
                fn (string $column) => Schema::hasColumn('emp_positions', $column)
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};