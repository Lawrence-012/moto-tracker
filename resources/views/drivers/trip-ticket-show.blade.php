<x-app-layout>
    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Back Button -->
            <div class="mb-4">
                <a href="{{ route('driver.trip-tickets', ['tab' => $tripTicket->status === 'Completed' ? 'completed' : ($tripTicket->status === 'Cancelled' ? 'cancelled' : 'approved')]) }}" 
                   class="text-[#1e6031] hover:text-[#164f2a] font-medium text-sm flex items-center no-underline" style="text-decoration: none;">
                    <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Trip Tickets
                </a>
            </div>
            
            <!-- Trip Ticket Details -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Trip Ticket Details
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Detailed information about your trip ticket.
                    </p>
                </div>
                <div class="px-4 py-5 sm:p-0">
                    <dl class="sm:divide-y sm:divide-gray-200">
                        <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500">
                                @if($tripTicket->status === 'Approved')
                                    Remarks
                                @else
                                    Status
                                @endif
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                @if($tripTicket->status === 'Approved')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-orange-100 text-orange-800 border border-orange-300">
                                        Ready to go
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                                        @if($tripTicket->status === 'Pending') style="background:#FFF4E0; color:#F0AD4E; border:1px solid #F0AD4E;"
                                        @elseif($tripTicket->status === 'On-going') style="background:#E3F0F7; color:#056295; border:1px solid #056295;"
                                        @elseif($tripTicket->status === 'Completed') style="background:#DFF0D8; color:#00703C; border:1px solid #00703C;"
                                        @elseif($tripTicket->status === 'Cancelled') style="background:#FFE0E0; color:#DC3545; border:1px solid #DC3545;"
                                        @else style="background:#f3f4f6; color:#6b7280; border:1px solid #d1d5db;" @endif>
                                        {{ $tripTicket->status }}
                                    </span>
                                @endif
                            </dd>
                        </div>
                        
                        <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500">
                                Ticket Number
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                {{ $tripTicket->ticket_number }}
                            </dd>
                        </div>
                        
                        <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500">
                                Itinerary Details
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                <p><strong>Destination:</strong> {{ optional($tripTicket->itinerary)->destination }}</p>
                                <p><strong>Purpose:</strong> {{ optional($tripTicket->itinerary)->purpose }}</p>
                                
                                {{-- Smart Date Formatting --}}
                                @php
                                    $dateFrom = optional($tripTicket->itinerary)->date_from;
                                    $dateTo = optional($tripTicket->itinerary)->date_to ?? $dateFrom;
                                    $departureTime = optional($tripTicket->itinerary)->departure_time;
                                    
                                    // Format departure date with smart logic
                                    if ($dateFrom && $dateTo) {
                                        $isSameDate = $dateFrom->format('Y-m-d') === $dateTo->format('Y-m-d');
                                        
                                        if ($isSameDate) {
                                            // Same date: "Mar 14, 2026"
                                            $formattedDate = $dateFrom->format('M d, Y');
                                        } else {
                                            $fromMonth = $dateFrom->format('M');
                                            $fromDay = $dateFrom->day;
                                            $fromYear = $dateFrom->year;
                                            $toMonth = $dateTo->format('M');
                                            $toDay = $dateTo->day;
                                            $toYear = $dateTo->year;
                                            
                                            if ($fromMonth === $toMonth && $fromYear === $toYear) {
                                                // Same month/year: "Jan 1-2, 2026"
                                                $formattedDate = "{$fromMonth} {$fromDay}-{$toDay}, {$fromYear}";
                                            } elseif ($fromYear === $toYear) {
                                                // Same year: "Jan 31 - Feb 1, 2026"
                                                $formattedDate = "{$fromMonth} {$fromDay} - {$toMonth} {$toDay}, {$fromYear}";
                                            } else {
                                                // Different years: "Dec 31, 2026 - Jan 1, 2027"
                                                $formattedDate = "{$fromMonth} {$fromDay}, {$fromYear} - {$toMonth} {$toDay}, {$toYear}";
                                            }
                                        }
                                    } else {
                                        $formattedDate = 'N/A';
                                    }
                                    
                                    // Format departure time (e.g., "9:00 AM" or "1:30 PM")
                                    if ($departureTime) {
                                        $timeObj = \Carbon\Carbon::parse($departureTime);
                                        $formattedTime = $timeObj->format('g:i A');
                                    } else {
                                        $formattedTime = 'N/A';
                                    }
                                @endphp
                                
                                <p><strong>Departure Date:</strong> {{ $formattedDate }}</p>
                                <p><strong>Departure Time:</strong> {{ $formattedTime }}</p>
                            </dd>
                        </div>
                        
                        <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500">
                                Vehicle
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                @if($tripTicket->itinerary && $tripTicket->itinerary->vehicle)
                                    {{ $tripTicket->itinerary->vehicle->make }} {{ $tripTicket->itinerary->vehicle->model }}
                                    <br><span class="text-gray-500">Plate Number: {{ $tripTicket->itinerary->vehicle->plate_number }}</span>
                                @else
                                    <span class="text-gray-500 italic">No vehicle assigned</span>
                                @endif
                            </dd>
                        </div>
                        
                        <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500">
                                Head of Party
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                {{ $tripTicket->head_of_party ?? 'Not specified' }}
                            </dd>
                        </div>
                        
                        <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500">
                                Passengers
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                @if($tripTicket->passengers && is_array($tripTicket->passengers) && count($tripTicket->passengers) > 0)
                                    <ul class="list-disc pl-5 space-y-1">
                                        @foreach($tripTicket->passengers as $passenger)
                                            <li>{{ $passenger }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-gray-500 italic">No passengers listed</p>
                                @endif
                            </dd>
                        </div>
                        
                        <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500">
                                Created At
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                {{ $tripTicket->created_at->format('M d, Y h:i A') }}
                            </dd>
                        </div>

                        @if(in_array($tripTicket->status, ['On-Going', 'Completed']))
                        <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt class="text-sm font-medium text-gray-500">
                                Odometer Reading
                            </dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                <p><strong>Start:</strong> {{ $tripTicket->odometer_start_km !== null ? number_format($tripTicket->odometer_start_km).' km' : 'Not recorded' }}
                                    @if($tripTicket->auto_started)
                                        <span class="ml-1 inline-block px-1.5 py-0.5 text-[10px] rounded bg-yellow-100 text-yellow-800" title="Trip auto-started when leaving CLSU; odometer is an estimate from the vehicle's last known mileage.">Auto-started &middot; estimate</span>
                                    @endif
                                </p>
                                <p><strong>End:</strong> {{ $tripTicket->odometer_end_km !== null ? number_format($tripTicket->odometer_end_km).' km' : 'Not yet recorded' }}</p>
                                @if($tripTicket->odometer_distance_km !== null)
                                    <p class="mt-1"><strong>Distance traveled:</strong> {{ number_format($tripTicket->odometer_distance_km) }} km</p>
                                @endif
                            </dd>
                        </div>
                        @endif
                    </dl>
                </div>

                @if(in_array($tripTicket->status, ['Approved', 'On-Going']))
                <div class="px-4 py-4 sm:px-6 border-t border-gray-200 bg-green-50">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-9 h-9 rounded-full bg-white flex items-center justify-center border border-green-200">
                            <i class="fas fa-location-arrow text-[#1e6031]"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-[#1e6031]">
                                Automatic Trip Tracking
                            </p>
                            <p class="text-xs text-green-800 mt-1">
                                @if($tripTicket->status === 'Approved')
                                    No action is required from the driver. The trip will automatically start when the vehicle exits the CLSU Motorpool Gate.
                                @else
                                    GPS tracking is currently recording this trip. The trip will automatically complete when the vehicle returns through the CLSU Motorpool Gate.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>


</x-app-layout>
