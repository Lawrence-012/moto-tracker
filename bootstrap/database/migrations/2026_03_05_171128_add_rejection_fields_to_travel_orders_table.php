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
        Schema::table('travel_orders', function (Blueprint $table) {
            // Add rejection reason field
            if (!Schema::hasColumn('travel_orders', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('status');
            }
            
            // Add who rejected and when
            if (!Schema::hasColumn('travel_orders', 'rejected_by_employee_id')) {
                $table->foreignId('rejected_by_employee_id')->nullable()->after('rejection_reason')->constrained('employees')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('travel_orders', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejected_by_employee_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->dropForeign(['rejected_by_employee_id']);
            $table->dropColumn(['rejection_reason', 'rejected_by_employee_id', 'rejected_at']);
        });
    }
};
