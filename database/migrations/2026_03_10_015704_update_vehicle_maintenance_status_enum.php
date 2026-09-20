<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE vehicle_maintenance MODIFY COLUMN status ENUM('Pending', 'Ongoing', 'Completed', 'Need Maintenance') DEFAULT 'Pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE vehicle_maintenance MODIFY COLUMN status ENUM('Pending', 'Ongoing', 'Completed') DEFAULT 'Pending'");
    }
};
