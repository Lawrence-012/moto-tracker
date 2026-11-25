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
            // Add approver fields if they don't exist
            if (!Schema::hasColumn('travel_orders', 'approved_by_employee_id')) {
                $table->unsignedBigInteger('approved_by_employee_id')->nullable()->after('status');
                $table->foreign('approved_by_employee_id')->references('id')->on('employees')->onDelete('set null');
            }
            
            // Add signer fields if they don't exist
            if (!Schema::hasColumn('travel_orders', 'signed_by_employee_id')) {
                $table->unsignedBigInteger('signed_by_employee_id')->nullable()->after('approved_by_employee_id');
                $table->foreign('signed_by_employee_id')->references('id')->on('employees')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['approved_by_employee_id']);
            $table->dropForeign(['signed_by_employee_id']);
            
            // Then drop columns
            $table->dropColumn(['approved_by_employee_id', 'signed_by_employee_id']);
        });
    }
};
