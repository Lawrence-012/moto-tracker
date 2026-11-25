<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One dashcam per vehicle and one vehicle per dashcam (vehicle_id is unique).
     * Each dashcam has its own code; the QR shown to the admin encodes the same
     * code that is typed in manually on the dashcam app.
     */
    public function up(): void
    {
        Schema::create('dashcams', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->foreignId('vehicle_id')->unique()->constrained('vehicles')->onDelete('cascade');
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paired_at')->nullable();
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
