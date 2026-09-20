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
        Schema::create('dismissed_oil_change_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // The user who dismissed
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade'); // The vehicle
            $table->timestamps();
            
            // Ensure unique combination of user and vehicle
            $table->unique(['user_id', 'vehicle_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dismissed_oil_change_notifications');
    }
};
