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
            if (!Schema::hasColumn('travel_orders', 'approver1_approved_at')) {
                $table->timestamp('approver1_approved_at')->nullable()->after('president_approved_at');
            }
            
            if (!Schema::hasColumn('travel_orders', 'approver2_approved_at')) {
                $table->timestamp('approver2_approved_at')->nullable()->after('approver1_approved_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $table->dropColumn(['approver1_approved_at', 'approver2_approved_at']);
        });
    }
};
