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
        Schema::table('itineraries', function (Blueprint $table) {
            if (! Schema::hasColumn('itineraries', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable();
            }

            if (! Schema::hasColumn('itineraries', 'rejected_by_employee_id')) {
                $table->foreignId('rejected_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            }

            if (! Schema::hasColumn('itineraries', 'rejected_at')) {
                $table->dateTime('rejected_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('itineraries', function (Blueprint $table) {
            if (Schema::hasColumn('itineraries', 'rejected_by_employee_id')) {
                $table->dropForeign(['rejected_by_employee_id']);
            }

            $columns = array_values(array_filter(
                ['rejection_reason', 'rejected_by_employee_id', 'rejected_at'],
                fn (string $column) => Schema::hasColumn('itineraries', $column)
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
