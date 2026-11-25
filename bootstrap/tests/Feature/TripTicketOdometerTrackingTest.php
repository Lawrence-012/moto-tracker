<?php

namespace Tests\Feature;

use App\Http\Controllers\TripTicketController;
use App\Models\Itinerary;
use App\Models\TripTicket;
use App\Models\Vehicle;
use App\Models\VehicleTravelHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TripTicketOdometerTrackingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();

        Schema::create('vehicles', function ($table) {
            $table->id();
            $table->string('plate_number')->nullable();
            $table->string('model')->nullable();
            $table->string('type')->nullable();
            $table->string('fuel_type')->nullable();
            $table->integer('seating_capacity')->nullable();
            $table->integer('mileage')->nullable();
            $table->integer('last_maintenance_mileage')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });

        Schema::create('itineraries', function ($table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->string('destination')->nullable();
            $table->text('purpose')->nullable();
            $table->time('departure_time')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });

        Schema::create('trip_tickets', function ($table) {
            $table->id();
            $table->unsignedBigInteger('itinerary_id')->nullable();
            $table->string('status')->nullable();
            $table->string('head_of_party')->nullable();
            $table->json('passengers')->nullable();
            $table->integer('odometer_start_km')->nullable();
            $table->integer('odometer_end_km')->nullable();
            $table->timestamps();
        });

        Schema::create('vehicle_travel_history', function ($table) {
            $table->id();
            $table->unsignedBigInteger('trip_ticket_id')->nullable();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('head_of_party')->nullable();
            $table->string('destination')->nullable();
            $table->date('departure_date')->nullable();
            $table->time('departure_time')->nullable();
            $table->date('arrival_date')->nullable();
            $table->time('arrival_time')->nullable();
            $table->integer('odometer_start_km')->nullable();
            $table->integer('odometer_end_km')->nullable();
            $table->decimal('distance_km', 10, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('dismissed_oil_change_notifications', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->integer('mileage_at_dismissal')->nullable();
            $table->timestamps();
        });
    }

    public function test_completed_trip_ticket_records_odometer_start_and_end_and_updates_vehicle_mileage(): void
    {
        $vehicle = Vehicle::create([
            'plate_number' => 'ABC-123',
            'model' => 'Toyota Hiace',
            'type' => 'Van',
            'fuel_type' => 'Diesel',
            'seating_capacity' => 12,
            'mileage' => 1200,
            'last_maintenance_mileage' => 0,
            'status' => 'Active',
        ]);

        $itinerary = Itinerary::create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => null,
            'date_from' => now()->subDay(),
            'date_to' => now(),
            'destination' => 'Cabanatuan City',
            'purpose' => 'Office visit',
            'departure_time' => '08:00:00',
            'status' => 'Approved',
        ]);

        $tripTicket = TripTicket::create([
            'itinerary_id' => $itinerary->id,
            'status' => 'Approved',
            'head_of_party' => 'Juan Dela Cruz',
            'passengers' => [],
        ]);

        $controller = new TripTicketController();
        $request = new Request([
            'status' => 'Completed',
            'odometer_start_km' => 1250,
            'odometer_end_km' => 1400,
        ]);

        $response = $controller->updateStatus($request, $tripTicket->id);

        $this->assertTrue($response->isRedirect());

        $travelHistory = VehicleTravelHistory::where('trip_ticket_id', $tripTicket->id)->first();
        $this->assertNotNull($travelHistory);
        $this->assertSame(1250, $travelHistory->odometer_start_km);
        $this->assertSame(1400, $travelHistory->odometer_end_km);
        $this->assertSame(150.0, (float) $travelHistory->distance_km);
        $this->assertSame(1400, $vehicle->fresh()->mileage);
    }
}
