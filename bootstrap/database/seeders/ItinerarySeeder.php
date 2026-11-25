<?php

namespace Database\Seeders;

use App\Models\Itinerary;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ItinerarySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            return;
        }

        $statuses = ['pending', 'approved', 'cancelled'];

        for ($i = 0; $i < 10; $i++) {
            $startDate = Carbon::now()->addDays(rand(1, 30));

            Itinerary::create([
                'date_from' => $startDate->toDateString(),
                'date_to' => $startDate->copy()->addDays(rand(0, 2))->toDateString(),
                'departure_time' => Carbon::createFromTime(rand(8, 16), 0, 0)->format('H:i:s'),
                'destination' => ['Manila', 'Quezon City', 'Makati'][rand(0, 2)],
                'purpose' => 'Business meeting with stakeholders',
                'vehicle_id' => null,
                'driver_id' => null,
                'status' => $statuses[rand(0, 2)],
            ]);
        }
    }
}
