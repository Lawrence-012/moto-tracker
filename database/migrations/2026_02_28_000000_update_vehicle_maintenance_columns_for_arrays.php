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
        Schema::table('vehicle_maintenance', function (Blueprint $table) {
            // Change materials_parts to JSON type to store arrays
            $table->json('materials_parts')->nullable()->change();
            
            // Change mechanic_assigned to JSON type to store arrays
            $table->json('mechanic_assigned')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_maintenance', function (Blueprint $table) {
            // Revert materials_parts to text type
            $table->text('materials_parts')->nullable()->change();
            
            // Revert mechanic_assigned to string type
            $table->string('mechanic_assigned')->change();
        });
    }
};