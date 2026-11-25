<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-admin "removed" flag for open maintenance work orders shown in the
     * header bell and the Maintenance Needed page. Removing a notification does
     * NOT touch the work order itself.
     *
     * Index/constraint names are set explicitly because MySQL limits identifiers to 64 chars.
     */
    public function up(): void
    {
        Schema::create('dismissed_maintenance_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('vehicle_maintenance_id')
                ->constrained('vehicle_maintenance', 'id', 'dismissed_maint_vm_fk')
                ->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'vehicle_maintenance_id'], 'dismissed_maint_user_vm_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dismissed_maintenance_notifications');
    }
};
