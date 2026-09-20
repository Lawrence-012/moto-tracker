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
            $hasDivisionHead = Schema::hasColumn('travel_orders', 'divisionhead_approved_at');

            if (! Schema::hasColumn('travel_orders', 'vp_approved')) {
                $col = $table->boolean('vp_approved')->nullable();
                if ($hasDivisionHead) {
                    $col->after('divisionhead_approved_at');
                }
            }

            if (! Schema::hasColumn('travel_orders', 'vp_approved_at')) {
                $table->dateTime('vp_approved_at')->nullable()->after('vp_approved');
            }

            if (! Schema::hasColumn('travel_orders', 'vp_declined')) {
                $table->boolean('vp_declined')->nullable()->after('vp_approved_at');
            }

            if (! Schema::hasColumn('travel_orders', 'vp_declined_at')) {
                $table->dateTime('vp_declined_at')->nullable()->after('vp_declined');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('travel_orders', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['vp_approved', 'vp_approved_at', 'vp_declined', 'vp_declined_at'],
                fn (string $column) => Schema::hasColumn('travel_orders', $column)
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};