<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Breadcrumb trail of GPS points recorded while a trip ticket is On-Going.
     * Used to draw the route line on the admin's live fleet map.
     */
    public function up(): void
    {
        if (Schema::hasTable('trip_ticket_locations')) {
            return; // Already created by a previous migration run.
        }

        Schema::create('trip_ticket_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trip_ticket_id');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('speed_kph', 6, 2)->nullable();
            $table->timestamp('recorded_at');

            $table->foreign('trip_ticket_id')->references('id')->on('trip_tickets')->onDelete('cascade');
            $table->index(['trip_ticket_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_ticket_locations');
    }
};
