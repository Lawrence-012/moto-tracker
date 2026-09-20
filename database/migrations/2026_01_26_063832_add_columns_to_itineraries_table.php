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
            // Add the required columns if they don't exist
            if (!Schema::hasColumn('itineraries', 'title')) {
                $table->string('title');
            }
            if (!Schema::hasColumn('itineraries', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('itineraries', 'date')) {
                $table->date('date');
            }
            if (!Schema::hasColumn('itineraries', 'start_time')) {
                $table->time('start_time');
            }
            if (!Schema::hasColumn('itineraries', 'end_time')) {
                $table->time('end_time');
            }
            if (!Schema::hasColumn('itineraries', 'destination')) {
                $table->string('destination');
            }
            if (!Schema::hasColumn('itineraries', 'purpose')) {
                $table->string('purpose');
            }
            if (!Schema::hasColumn('itineraries', 'vehicle_id')) {
                $table->unsignedBigInteger('vehicle_id')->nullable();
            }
            if (!Schema::hasColumn('itineraries', 'driver_id')) {
                $table->unsignedBigInteger('driver_id')->nullable();
            }
            if (!Schema::hasColumn('itineraries', 'created_by')) {
                $table->unsignedBigInteger('created_by');
            }
            if (!Schema::hasColumn('itineraries', 'status')) {
                $table->string('status')->default('pending');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('itineraries', function (Blueprint $table) {
            foreach (['vehicle_id', 'driver_id', 'created_by'] as $column) {
                try {
                    $table->dropForeign([$column]);
                } catch (\Exception $e) {
                    //
                }
            }
        });

        // Now drop the columns
        Schema::table('itineraries', function (Blueprint $table) {
            $columns = Schema::getColumnListing('itineraries');
            
            $columnsToDrop = [
                'title', 'description', 'date', 'start_time', 'end_time', 
                'destination', 'purpose', 'vehicle_id', 'driver_id', 'created_by', 'status'
            ];
            
            $existingColumns = array_intersect($columns, $columnsToDrop);
            
            if (!empty($existingColumns)) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};