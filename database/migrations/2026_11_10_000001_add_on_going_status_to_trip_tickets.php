<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Adds the On-Going status, used when a trip starts (automatically, from the vehicle's GPS).
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE trip_tickets MODIFY COLUMN status ENUM('Pending', 'Approved', 'On-Going', 'Completed', 'Cancelled', 'Archived') DEFAULT 'Pending'");
    }

    public function down(): void
    {
        // Trips that are still On-Going go back to Approved before the column is narrowed again
        DB::table('trip_tickets')->where('status', 'On-Going')->update(['status' => 'Approved']);

        DB::statement("ALTER TABLE trip_tickets MODIFY COLUMN status ENUM('Pending', 'Approved', 'Completed', 'Cancelled', 'Archived') DEFAULT 'Pending'");
    }
};
