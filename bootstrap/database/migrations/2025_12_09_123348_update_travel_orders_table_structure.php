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
        if (!Schema::hasTable('travel_orders')) {
            return;
        }

        Schema::table('travel_orders', function (Blueprint $table) {
            $columnsToDrop = [];

            foreach (['departure_time', 'arrival_time', 'divisionhead_approved', 'divisionhead_approved_at', 'president_approved', 'president_approved_at'] as $column) {
                if (Schema::hasColumn('travel_orders', $column)) {
                    $columnsToDrop[] = $column;
                }
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }

            if (!Schema::hasColumn('travel_orders', 'head_remarks')) {
                $table->text('head_remarks')->nullable();
            }

            if (!Schema::hasColumn('travel_orders', 'vp_remarks')) {
                $table->text('vp_remarks')->nullable();
            }
        });

        if (Schema::hasColumn('travel_orders', 'status')) {
            try {
                Schema::table('travel_orders', function (Blueprint $table) {
                    $table->string('status')->default('pending')->change();
                });
            } catch (\Throwable $e) {
                // Ignore SQLite/legacy schema issues during test bootstrapping.
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            // Drop new columns
            $table->dropColumn([
                'head_remarks',
                'vp_remarks'
            ]);
            
            // Add back old columns
            $table->time('departure_time')->nullable();
            $table->time('arrival_time')->nullable();
            $table->boolean('divisionhead_approved')->nullable();
            $table->dateTime('divisionhead_approved_at')->nullable();
            $table->boolean('president_approved')->nullable();
            $table->dateTime('president_approved_at')->nullable();
            
            // Revert status column
            $table->enum('status', ['Pending', 'Approved', 'Cancelled'])->nullable()->change();
        });
    }
};
