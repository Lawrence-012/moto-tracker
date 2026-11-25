<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A dashcam creates its own code (shown as a QR on the device) and checks in
     * with the server. It starts "pending" (vehicle_id NULL). An admin then
     * registers it to exactly one vehicle. One vehicle has at most one dashcam.
     */
    public function up(): void
    {
        Schema::create('dashcams', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->foreignId('vehicle_id')->nullable()->unique()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->decimal('last_latitude', 10, 7)->nullable();
            $table->decimal('last_longitude', 10, 7)->nullable();
            $table->decimal('last_speed_kph', 6, 2)->nullable();
            $table->decimal('last_heading', 6, 2)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashcams');
    }
};
