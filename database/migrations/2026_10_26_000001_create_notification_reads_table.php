<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Mark as read" per admin. Reading a notification only lowers the bell count.
     * It does not remove the notification, and it does not change the Maintenance Needed badge.
     */
    public function up(): void
    {
        Schema::create('notification_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type', 30);
            $table->unsignedBigInteger('ref_id');
            $table->timestamp('read_at')->nullable();

            $table->unique(['user_id', 'type', 'ref_id'], 'notif_reads_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_reads');
    }
};
