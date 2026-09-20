<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('travel_orders', 'approved_by_employee_id') && !Schema::hasColumn('travel_orders', 'approver1_employee_id')) {
            // Rename approved_by_employee_id to approver1_employee_id
            Schema::table('travel_orders', function (Blueprint $table) {
                $table->renameColumn('approved_by_employee_id', 'approver1_employee_id');
            });
        }
        
        if (Schema::hasColumn('travel_orders', 'signed_by_employee_id') && !Schema::hasColumn('travel_orders', 'approver2_employee_id')) {
            // Rename signed_by_employee_id to approver2_employee_id
            Schema::table('travel_orders', function (Blueprint $table) {
                $table->renameColumn('signed_by_employee_id', 'approver2_employee_id');
            });
        }
        
        // Update existing records to use new status
        DB::statement("UPDATE travel_orders SET status = 'pending_approval_2' WHERE status = 'pending_signature' AND approver2_employee_id IS NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('travel_orders', 'approver1_employee_id')) {
            Schema::table('travel_orders', function (Blueprint $table) {
                $table->renameColumn('approver1_employee_id', 'approved_by_employee_id');
            });
        }
        
        if (Schema::hasColumn('travel_orders', 'approver2_employee_id')) {
            Schema::table('travel_orders', function (Blueprint $table) {
                $table->renameColumn('approver2_employee_id', 'signed_by_employee_id');
            });
        }
    }
};
