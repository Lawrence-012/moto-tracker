<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $columnsToDrop = array_values(array_filter(
                ['ext_name', 'sex', 'prefix'],
                fn (string $column) => Schema::hasColumn('drivers', $column)
            ));

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });

        if (Schema::hasColumn('drivers', 'first_name') && ! Schema::hasColumn('drivers', 'firsts_name')) {
            DB::statement('ALTER TABLE drivers CHANGE first_name firsts_name VARCHAR(255) NULL');
        }

        Schema::table('drivers', function (Blueprint $table) {
            $columns = [
                'contact_num' => fn (Blueprint $table) => $table->string('contact_num')->nullable(),
                'email' => fn (Blueprint $table) => $table->string('email')->nullable(),
                'password' => fn (Blueprint $table) => $table->string('password')->nullable(),
                'address' => fn (Blueprint $table) => $table->text('address')->nullable(),
                'position' => fn (Blueprint $table) => $table->string('position')->nullable(),
                'official_station' => fn (Blueprint $table) => $table->string('official_station')->nullable(),
                'availability_status' => fn (Blueprint $table) => $table->string('availability_status')->default('Available'),
            ];

            foreach ($columns as $column => $callback) {
                if (! Schema::hasColumn('drivers', $column)) {
                    $callback($table);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
