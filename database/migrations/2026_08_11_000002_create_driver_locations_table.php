<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per driver holding their latest known GPS position.
     * This is what powers the "tracking device" screen for both the
     * driver (their own position) and the admin (fleet map).
     */
    public function up(): void
    {
        if (Schema::hasTable('driver_locations')) {
            return; // Already created by a previous migration run.
        }

        Schema::create('driver_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('driver_id')->unique();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->unsignedBigInteger('trip_ticket_id')->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy_meters', 8, 2)->nullable();
            $table->decimal('speed_kph', 6, 2)->nullable();
            $table->decimal('heading', 6, 2)->nullable();
            $table->boolean('is_sharing')->default(true);
            $table->boolean('inside_clsu_geofence')->default(true);
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();

            $table->foreign('driver_id')->references('id')->on('drivers')->onDelete('cascade');
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('set null');
            $table->foreign('trip_ticket_id')->references('id')->on('trip_tickets')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_locations');
    }
};
