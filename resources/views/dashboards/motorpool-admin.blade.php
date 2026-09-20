@extends('layouts.motorpool-admin')

@section('header')
    <div class="main">
        <div class="d-flex justify-content-between align-items-center px-4 py-3">
            <span style="color:#004d00; font-weight:700; font-size:2rem;">
                Admin Dashboard
            </span>
        </div>
    </div>

                    <!-- Approved Travel Orders Section -->
                    <div style="margin-top: 30px;">
                        <h3 style="color:#004d00; font-weight:900; font-size:1.8rem; margin-bottom:20px; text-transform:uppercase; letter-spacing:1px; text-align:center;">
                            APPROVED TRAVEL ORDERS
                        </h3>
                        
                        <!-- Search Bar -->
                        <div style="margin-bottom: 20px; display: flex; justify-content: center;">
                            <div style="position: relative; width: 100%; max-width: 500px;">
                                <input type="text" 
                                       id="travelOrderSearch" 
                                       placeholder="Search travel orders..." 
                                       style="
                                           width: 100%;
                                           padding: 12px 20px;
                                           border: 2px solid #004d00;
                                           border-radius: 8px;
                                           font-size: 16px;
                                           font-weight: 500;
                                           color: #004d00;
                                           background-color: #f2f9f2;
                                           box-sizing: border-box;
                                           transition: all 0.3s ease;
                                       "
                                       onkeyup="searchTravelOrders()">
                                <i class="fas fa-search" style="
                                    position: absolute;
                                    right: 15px;
                                    top: 50%;
                                    transform: translateY(-50%);
                                    color: #004d00;
                                    font-size: 18px;
                                "></i>
                            </div>
                        </div>
                        
                        <!-- Travel Orders Table -->
                        <div style="
                            background: #fff;
                            border: 2px solid #004d00;
                            border-radius: 12px;
                            box-shadow: 0 4px 8px rgba(0,77,0,0.1);
                            overflow: hidden;
                            margin-bottom: 20px;
                        ">
                            <div style="overflow-x: auto;">
                                <table style="
                                    width: 100%;
                                    border-collapse: collapse;
                                    font-family: Arial, sans-serif;
                                ">
                                    <thead>
                                        <tr style="background-color: #004d00; color: white;">
                                            <th style="padding: 15px 12px; text-align: left; font-weight: 700; font-size: 14px; border-right: 1px solid rgba(255,255,255,0.2);">No.</th>
                                            <th style="padding: 15px 12px; text-align: left; font-weight: 700; font-size: 14px; border-right: 1px solid rgba(255,255,255,0.2);">Employee Name</th>
                                            <th style="padding: 15px 12px; text-align: left; font-weight: 700; font-size: 14px; border-right: 1px solid rgba(255,255,255,0.2);">Purpose</th>
                                            <th style="padding: 15px 12px; text-align: left; font-weight: 700; font-size: 14px; border-right: 1px solid rgba(255,255,255,0.2);">Destination</th>
                                            <th style="padding: 15px 12px; text-align: left; font-weight: 700; font-size: 14px; border-right: 1px solid rgba(255,255,255,0.2);">Date</th>
                                            <th style="padding: 15px 12px; text-align: left; font-weight: 700; font-size: 14px; border-right: 1px solid rgba(255,255,255,0.2);">Time</th>
                                            <th style="padding: 15px 12px; text-align: left; font-weight: 700; font-size: 14px; border-right: 1px solid rgba(255,255,255,0.2);">Vehicle</th>
                                            <th style="padding: 15px 12px; text-align: left; font-weight: 700; font-size: 14px; border-right: 1px solid rgba(255,255,255,0.2);">Driver</th>
                                            <th style="padding: 15px 12px; text-align: left; font-weight: 700; font-size: 14px; border-right: 1px solid rgba(255,255,255,0.2);">Remarks</th>
                                            <th style="padding: 15px 12px; text-align: center; font-weight: 700; font-size: 14px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="travelOrdersTableBody">
                                        @if(isset($travelOrders) && $travelOrders->count() > 0)
                                            @foreach($travelOrders as $index => $order)
                                                <tr style="border-bottom: 1px solid #e0e0e0; {{ $loop->even ? 'background-color: #f8f9fa;' : '' }}" class="travel-order-row">
                                                    <td style="padding: 12px; font-size: 14px; color: #333;">{{ $index + 1 }}</td>
                                                    <td style="padding: 12px; font-size: 14px; color: #333;">{{ $order->employee->full_name ?? 'N/A' }}</td>
                                                    <td style="padding: 12px; font-size: 14px; color: #333;">{{ $order->purpose ?? 'N/A' }}</td>
                                                    <td style="padding: 12px; font-size: 14px; color: #333;">{{ $order->destination ?? 'N/A' }}</td>
                                                    <td style="padding: 12px; font-size: 14px; color: #333;">{{ $order->date_needed ? date('M d, Y', strtotime($order->date_needed)) : 'N/A' }}</td>
                                                    <td style="padding: 12px; font-size: 14px; color: #333;">{{ $order->time_needed ?? 'N/A' }}</td>
                                                    <td style="padding: 12px; font-size: 14px; color: #333;">{{ $order->vehicle_assigned ?? 'Not assigned' }}</td>
                                                    <td style="padding: 12px; font-size: 14px; color: #333;">{{ $order->driver_assigned ?? 'Not assigned' }}</td>
                                                    <td style="padding: 12px; font-size: 14px; color: #333;">{{ $order->remarks ?? 'None' }}</td>
                                                    <td style="padding: 12px; text-align: center;">
                                                        <button onclick="viewTravelOrder({{ $order->id }})" style="
                                                            background: #004d00;
                                                            color: white;
                                                            border: none;
                                                            padding: 8px 12px;
                                                            border-radius: 4px;
                                                            cursor: pointer;
                                                            font-size: 12px;
                                                            font-weight: 600;
                                                            transition: background 0.2s;
                                                        " onmouseover="this.style.background='#003300'" onmouseout="this.style.background='#004d00'">
                                                            <i class="fas fa-eye"></i> View
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="10" style="padding: 30px; text-align: center; font-size: 16px; color: #666; font-style: italic;">
                                                    No approved travel orders found.
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Pagination -->
                        @if(isset($travelOrders) && $travelOrders->hasPages())
                            <div style="
                                display: flex;
                                justify-content: space-between;
                                align-items: center;
                                padding: 15px 20px;
                                background: #f8f9fa;
                                border: 1px solid #dee2e6;
                                border-radius: 8px;
                                font-size: 14px;
                                color: #495057;
                            ">
                                <div>
                                    Showing {{ $travelOrders->firstItem() }} to {{ $travelOrders->lastItem() }} of {{ $travelOrders->total() }} entries
                                </div>
                                <div style="display: flex; gap: 8px; align-items: center;">
                                    {{-- Previous Button --}}
                                    @if($travelOrders->onFirstPage())
                                        <button disabled style="
                                            padding: 8px 12px;
                                            background: #e9ecef;
                                            color: #6c757d;
                                            border: 1px solid #dee2e6;
                                            border-radius: 4px;
                                            cursor: not-allowed;
                                            font-size: 13px;
                                        ">
                                            <i class="fas fa-chevron-left"></i> Prev
                                        </button>
                                    @else
                                        <a href="{{ $travelOrders->previousPageUrl() }}" style="
                                            padding: 8px 12px;
                                            background: #004d00;
                                            color: white;
                                            text-decoration: none;
                                            border: 1px solid #004d00;
                                            border-radius: 4px;
                                            font-size: 13px;
                                            transition: background 0.2s;
                                        " onmouseover="this.style.background='#003300'" onmouseout="this.style.background='#004d00'">
                                            <i class="fas fa-chevron-left"></i> Prev
                                        </a>
                                    @endif
                                    
                                    {{-- Page Numbers --}}
                                    @for($i = max(1, $travelOrders->currentPage() - 2); $i <= min($travelOrders->lastPage(), $travelOrders->currentPage() + 2); $i++)
                                        @if($i == $travelOrders->currentPage())
                                            <span style="
                                                padding: 8px 12px;
                                                background: #004d00;
                                                color: white;
                                                border: 1px solid #004d00;
                                                border-radius: 4px;
                                                font-size: 13px;
                                                font-weight: 600;
                                            ">{{ $i }}</span>
                                        @else
                                            <a href="{{ $travelOrders->url($i) }}" style="
                                                padding: 8px 12px;
                                                background: white;
                                                color: #004d00;
                                                text-decoration: none;
                                                border: 1px solid #004d00;
                                                border-radius: 4px;
                                                font-size: 13px;
                                                transition: all 0.2s;
                                            " onmouseover="this.style.background='#f2f9f2'" onmouseout="this.style.background='white'">{{ $i }}</a>
                                        @endif
                                    @endfor
                                    
                                    {{-- Next Button --}}
                                    @if($travelOrders->hasMorePages())
                                        <a href="{{ $travelOrders->nextPageUrl() }}" style="
                                            padding: 8px 12px;
                                            background: #004d00;
                                            color: white;
                                            text-decoration: none;
                                            border: 1px solid #004d00;
                                            border-radius: 4px;
                                            font-size: 13px;
                                            transition: background 0.2s;
                                        " onmouseover="this.style.background='#003300'" onmouseout="this.style.background='#004d00'">
                                            Next <i class="fas fa-chevron-right"></i>
                                        </a>
                                    @else
                                        <button disabled style="
                                            padding: 8px 12px;
                                            background: #e9ecef;
                                            color: #6c757d;
                                            border: 1px solid #dee2e6;
                                            border-radius: 4px;
                                            cursor: not-allowed;
                                            font-size: 13px;
                                        ">
                                            Next <i class="fas fa-chevron-right"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Search Script -->
    <script>
        let searchTimeout;
        
        function searchTravelOrders() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const searchTerm = document.getElementById('travelOrderSearch').value.trim();
                
                if (searchTerm.length >= 2) {
                    // Show loading indicator
                    const tbody = document.getElementById('travelOrdersTableBody');
                    tbody.innerHTML = `<tr><td colspan="10" style="padding: 30px; text-align: center; font-size: 16px; color: #666;">Searching...</td></tr>`;
                    
                    // Make AJAX request
                    fetch(`/api/travel-orders/search?q=${encodeURIComponent(searchTerm)}`)
                        .then(response => response.json())
                        .then(data => {
                            updateTable(data.data);
                        })
                        .catch(error => {
                            console.error('Search error:', error);
                            tbody.innerHTML = `<tr><td colspan="10" style="padding: 30px; text-align: center; font-size: 16px; color: #666;">Error searching travel orders.</td></tr>`;
                        });
                } else if (searchTerm.length === 0) {
                    // Reload original data
                    location.reload();
                }
            }, 300); // Debounce for 300ms
        }
        
        function updateTable(orders) {
            const tbody = document.getElementById('travelOrdersTableBody');
            
            if (orders.length === 0) {
                tbody.innerHTML = `<tr><td colspan="10" style="padding: 30px; text-align: center; font-size: 16px; color: #666; font-style: italic;">No travel orders found matching your search.</td></tr>`;
                return;
            }
            
            let html = '';
            orders.forEach((order, index) => {
                const rowClass = index % 2 === 0 ? '' : 'background-color: #f8f9fa;';
                const employeeName = order.employee ? `${order.employee.first_name} ${order.employee.last_name}` : 'N/A';
                const dateNeeded = order.date_needed ? new Date(order.date_needed).toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'short', 
                    day: 'numeric' 
                }) : 'N/A';
                
                html += `
                    <tr style="border-bottom: 1px solid #e0e0e0; ${rowClass}" class="travel-order-row">
                        <td style="padding: 12px; font-size: 14px; color: #333;">${index + 1}</td>
                        <td style="padding: 12px; font-size: 14px; color: #333;">${employeeName}</td>
                        <td style="padding: 12px; font-size: 14px; color: #333;">${order.purpose || 'N/A'}</td>
                        <td style="padding: 12px; font-size: 14px; color: #333;">${order.destination || 'N/A'}</td>
                        <td style="padding: 12px; font-size: 14px; color: #333;">${dateNeeded}</td>
                        <td style="padding: 12px; font-size: 14px; color: #333;">${order.time_needed || 'N/A'}</td>
                        <td style="padding: 12px; font-size: 14px; color: #333;">${order.vehicle_assigned || 'Not assigned'}</td>
                        <td style="padding: 12px; font-size: 14px; color: #333;">${order.driver_assigned || 'Not assigned'}</td>
                        <td style="padding: 12px; font-size: 14px; color: #333;">${order.remarks || 'None'}</td>
                        <td style="padding: 12px; text-align: center;">
                            <button onclick="viewTravelOrder(${order.id})" style="
                                background: #004d00;
                                color: white;
                                border: none;
                                padding: 8px 12px;
                                border-radius: 4px;
                                cursor: pointer;
                                font-size: 12px;
                                font-weight: 600;
                                transition: background 0.2s;
                            " onmouseover="this.style.background='#003300'" onmouseout="this.style.background='#004d00'">
                                <i class="fas fa-eye"></i> View
                            </button>
                        </td>
                    </tr>
                `;
            });
            
            tbody.innerHTML = html;
        }
        
        function viewTravelOrder(id) {
            // Navigate to the travel order details page
            window.location.href = '/motorpool/travel-orders/' + id;
        }
    </script>
@endsection

<style>
    
.dashboard-card{
    flex: 1;
    min-width: 180px;
    height: 100px;
    display: flex;
    align-items: center;
    padding: 15px;

    background: #DFF0D8;
    border-radius: 10px;

    /* ✅ GREEN OUTER BORDER */
    border: 2px solid #00703C;

    box-shadow: 0 4px 8px rgba(0,112,60,0.15);

    cursor: pointer;

    transition: all 0.25s ease;
}

.dashboard-card:hover{
    transform: translateY(-3px);
    box-shadow: 0 8px 16px rgba(0,112,60,0.20);
}

/* Responsive design for dashboard cards */
@media (min-width: 1200px) {
    .dashboard-cards {
        flex-wrap: nowrap;
    }
    
    .dashboard-card {
        flex: 1 1 calc(25% - 11.25px); /* 4 cards per row with gap */
    }
}

@media (min-width: 768px) and (max-width: 1199px) {
    .dashboard-card {
        flex: 1 1 calc(50% - 7.5px); /* 2 cards per row with gap */
    }
}

/* Custom styling for completed trip event buttons */
.fc-event.completed-event {
    background-color: #DFF0D8 !important;
    border-color: #00703C !important;
    color: #00703C !important;
}

/* Custom styling for pending trip event buttons */
.fc-event.pending-event {
    background-color: #FFF4E0 !important;
    border-color: #F0AD4E !important;
    color: #F0AD4E !important;
}

/* Custom styling for on-going trip event buttons */
.fc-event.ongoing-event {
    background-color: #E3F0F7 !important;
    border-color: #056295 !important;
    color: #056295 !important;
}

/* Custom styling for rejected/cancelled trip event buttons */
.fc-event.rejected-event {
    background-color: #F8D7DA !important;
    border-color: #DC3545 !important;
    color: #DC3545 !important;
}

/* Ensure calendar maintains full width and doesn't collapse when sidebar is toggled */
#tripCalendar {
    width: 100%;
    min-width: 100%;
}

#tripCalendar .fc {
    max-width: 100%;
}

/* Prevent calendar from being affected by sidebar collapse */
.fc-theme-standard .fc-scrollgrid {
    width: 100% !important;
}
</style>
@section('content')
    <div class="py-12">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Welcome Section -->
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg border border-green-200 shadow-sm mb-4">
                <div class="px-4 py-3 sm:px-6 sm:py-4">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start flex-1">
                            <!-- Icon -->
                            <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg p-2 sm:p-3 mr-3 flex-shrink-0 mt-1">
                                <svg class="h-6 w-6 sm:h-8 sm:w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            
                            <!-- Text Content -->
                            <div style="max-width: calc(100% - 50px);">
                                <h1 class="text-base sm:text-xl font-semibold text-gray-800 mb-0" style="font-size: clamp(14px, 4vw, 20px);">
                                    Welcome, {{ Auth::user()->name }}!
                                </h1>
                                <p class="text-xs sm:text-sm text-gray-600 mt-1" style="font-size: clamp(11px, 3vw, 14px);">
                                    Your personal dashboard for vehicle reservations and requests
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="overflow-hidden sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <!-- Dashboard Cards -->
                    <div class="dashboard-cards" style="
                        display: flex;
                        gap: 15px;
                        flex-wrap: wrap;
                        margin-top: 20px;
                    ">
                    
                    <!-- Click handlers for cards -->
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            // Vehicles card click handler
                            const vehiclesCard = document.getElementById('vehicles-card');
                            if (vehiclesCard) {
                                vehiclesCard.addEventListener('click', function() {
                                    window.location.href = '{{ route("vehicles.index") }}';
                                });
                            }
                            
                            // Drivers card click handler
                            const driversCard = document.getElementById('drivers-card');
                            if (driversCard) {
                                driversCard.addEventListener('click', function() {
                                    window.location.href = '{{ route("drivers.index") }}';
                                });
                            }
                            
                            // Itineraries card click handler
                            const itinerariesCard = document.getElementById('itineraries-card');
                            if (itinerariesCard) {
                                itinerariesCard.addEventListener('click', function() {
                                    window.location.href = '{{ route("itinerary.index") }}';
                                });
                            }
                            
                            // Trip Tickets card click handler
                            const tripTicketsCard = document.getElementById('tripTickets-card');
                            if (tripTicketsCard) {
                                tripTicketsCard.addEventListener('click', function() {
                                    window.location.href = '{{ route("trip-tickets.index") }}';
                                });
                            }
                        });
                    </script>
                    
                        <!-- ITINERARIES -->
                        <div id="itineraries-card" class="dashboard-card">
                            <div style="
                                width: 45px;
                                height: 45px;
                                border-radius: 50%;
                                background-color: #A8DFAA; /* darker green circle */
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                margin-right: 10px;
                            ">
                                <i class="fas fa-file-alt" style="font-size:20px; color:#00703C;"></i>
                            </div>
                            <div style="flex:1; text-align:right;">
                                <p style="font-size:18px; font-weight:700; margin:0; color:#00703C;">{{ $itinerariesCount ?? 0 }}</p>
                                <span style="font-size:12px; font-weight:600; color:#00703C;">ITINERARIES</span>
                            </div>
                        </div>
                    
                        <!-- TRIP TICKETS -->
                        <div id="tripTickets-card" class="dashboard-card">
                            <div style="
                                width: 45px;
                                height: 45px;
                                border-radius: 50%;
                                background-color: #A8DFAA; /* darker green circle */
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                margin-right: 10px;
                            ">
                                <i class="fas fa-file-signature" style="font-size:20px; color:#00703C;"></i>
                            </div>
                            <div style="flex:1; text-align:right;">
                                <p style="font-size:18px; font-weight:700; margin:0; color:#00703C;">{{ $tripTicketsCount ?? 0 }}</p>
                                <span style="font-size:12px; font-weight:600; color:#00703C;">TRIP TICKETS</span>
                            </div>
                        </div>
                    
                        <!-- VEHICLES -->
                      <div id="vehicles-card" class="dashboard-card">
                        <div style="width: 45px; height: 45px; border-radius: 50%; background-color: #A8DFAA; display: flex; align-items: center; justify-content: center; margin-right: 10px;">
                                <i class="fas fa-car" style="font-size:20px; color:#00703C;"></i>
                            </div>
                            <div style="flex:1; text-align:right;">
                                <p style="font-size:18px; font-weight:700; margin:0; color:#00703C;">{{ $vehiclesCount ?? 0 }}</p>
                                <span style="font-size:12px; font-weight:600; color:#00703C;">VEHICLES</span>
                            </div>
                        </div>
                         
                        <!-- DRIVERS CARD -->
                        <div id="drivers-card" class="dashboard-card">
                         <div style="width: 45px; height: 45px; border-radius: 50%; background-color: #A8DFAA; display: flex; align-items: center; justify-content: center; margin-right: 10px;">
                                <i class="fas fa-id-badge" style="font-size:20px; color:#00703C;"></i>
                            </div>

                            <div style="flex:1; text-align:right;">
                                <p style="font-size:18px; font-weight:700; margin:0; color:#00703C;">{{ $driversCount ?? 0 }}</p>
                                <span style="font-size:12px; font-weight:600; color:#00703C;">DRIVERS</span>
                            </div>
                        </div>
                    </div>
                                        
                    <!-- Schedule of Trips -->
                    <div style="text-align:center; margin: 30px 0 20px 0;">
                 <h3 style="color:#004d00; font-weight:900; font-size:1.8rem; margin-bottom:5px; text-transform:uppercase; letter-spacing:1px;">
    SCHEDULE OF TRIPS
  </h3>
                        <div style="display:flex; justify-content:center; gap:8px; align-items:center;">
                            <label for="monthYear" style="font-weight:600; color:#004d00; font-size:1rem;">MONTH OF:</label>
                            <input type="month" id="monthYear" style="
                                padding:6px 12px;
                                border:2px solid #004d00;
                                border-radius:6px;
                                font-weight:600;
                                color:#004d00;
                                background-color:#f2f9f2;
                                font-size:1rem;
                                cursor: pointer;
                            " value="{{ date('Y-m') }}" />
                        </div>
                    </div>
                                        
                    <div id="tripCalendar" style="
                        background: #fff;
                        border: 2px solid #004d00;
                        border-radius: 12px;
                        padding: 20px;
                        box-shadow: 0 4px 8px rgba(0,77,0,0.1);
                        margin-bottom: 30px;
                        max-width: 100%;
                        overflow-x: auto;
                    "></div>
                    
                    <!-- Trip Detail Modal -->
                    <div id="trip-detail-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 transition-opacity duration-300">
                        <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto border-t-4 border-[#1e6031] transform transition-all duration-200 scale-95 opacity-0" id="modalContent">
                            <!-- Modal Header - DSU Themed -->
                            <div class="px-6 py-3 border-b border-gray-200 flex items-center justify-between" style="background: linear-gradient(135deg, #1e6031 0%, #2d7a3e 50%, #1e6031 100%);">
                                <div class="flex items-center gap-3">
                                    <!-- Icon Circle -->
                                    <div class="w-10 h-10 rounded-full bg-white bg-opacity-20 flex items-center justify-center">
                                        <svg class="h-6 w-6 text-[#1e6031]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 13l1-3a2 2 0 012-1h12a2 2 0 012 1l1 3M5 13h14M5 13v4a1 1 0 001 1h1m10-5v4a1 1 0 01-1 1h-1M7 18a1 1 0 100-2 1 1 0 000 2zm10 0a1 1 0 100-2 1 1 0 000 2z"/>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-bold text-white">Trip Ticket Details</h3>
                                </div>
                            </div>
                            
                            <!-- Modal Content -->
                            <div id="modal-body-content" class="px-6 py-3">
                                <!-- Content will be loaded dynamically -->
                            </div>
                            
                            <!-- Modal Footer -->
                            <div class="px-6 py-3 border-t border-gray-200 flex justify-end bg-gradient-to-r from-gray-50 to-white">
                                <button id="modal-close-btn" class="px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-all duration-200 text-sm font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- FullCalendar Script -->
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                      const monthYearInput = document.getElementById('monthYear');
                      const calendarEl = document.getElementById('tripCalendar');
                      
                      // Helper functions
                      function toYMD(d){ return d.toISOString().slice(0,10); }
                      
                      // Format date for FullCalendar (handles timezone issues by using date-only format)
                      function formatDateForCalendar(dateStr) {
                        if (!dateStr) return null;
                        // Extract just the date part (YYYY-MM-DD) to avoid timezone conversion
                        const date = new Date(dateStr);
                        const year = date.getFullYear();
                        const month = String(date.getMonth() + 1).padStart(2, '0');
                        const day = String(date.getDate()).padStart(2, '0');
                        return `${year}-${month}-${day}`;
                      }
                      
                      function formatDate(dateStr) {
                        const options = { year: 'numeric', month: 'long', day: 'numeric' };
                        const d = new Date(dateStr);
                        return d.toLocaleDateString('en-US', options);
                      }
                      
                      function formatTime(timeStr) {
                        if (!timeStr) return '';
                        const [hour, minute] = timeStr.split(':').map(Number);
                        const date = new Date();
                        date.setHours(hour, minute);
                        return date.toLocaleTimeString('en-US', { hour:'numeric', minute:'2-digit' });
                      }
                      
                      // Set initial month value
                      if (!monthYearInput.value) {
                        const now = new Date();
                        monthYearInput.value = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}`;
                      }
                      
                      const [initYear, initMonth] = monthYearInput.value.split('-').map(Number);
                      // Create date at noon to avoid timezone issues
                      const initialDate = new Date(initYear, initMonth - 1, 1, 12, 0, 0);
                      
                      const calendar = new FullCalendar.Calendar(calendarEl, {
                        initialView: 'dayGridMonth',
                        initialDate: initialDate,
                        height: 600,
                        dayHeaderFormat: { weekday: 'short' },
                        nowIndicator: true,
                        editable: false,
                        selectable: false,
                        navLinks: false,
                        headerToolbar: { 
                          left: '', 
                          center: 'title', 
                          right: '' 
                        },
                        displayEventTime: false,
                        dayMaxEvents: false,
                        
                        // Custom CLSU theme colors
                        eventBackgroundColor: '#004d00',
                        eventBorderColor: '#004d00',
                        eventTextColor: '#ffffff',
                        
                        events: function(fetchInfo, successCallback, failureCallback) {
                          const mv = monthYearInput.value || (new Date()).toISOString().slice(0,7);
                          const startStr = toYMD(fetchInfo.start);
                          const endStr = toYMD(fetchInfo.end);
                      
                          // Fetch trip tickets for the selected month - SHOW ALL TRIP TICKETS
                          fetch(`/api/trip-tickets/calendar-events?start=${startStr}&end=${endStr}`, {
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
                          })
                          .then(res => res.json())
                          .then(data => {
                            // Transform data to FullCalendar format - SIMILAR TO DRIVER DASHBOARD
                            // Create individual events for EACH DAY in the date range
                            let allEvents = [];
                            
                            data.forEach(ticket => {
                              // Determine color based on status - EXACT MATCH WITH DRIVER DASHBOARD
                              let bgColor = '#6c757d';      // Gray (default)
                              let borderColor = '#6c757d';
                              let textColor = '#6c757d';
                              
                              console.log('Ticket Status:', ticket.status);
                              console.log('Ticket Number:', ticket.ticket_number);
                              
                              if (ticket.status === 'Approved') {
                                bgColor = '#FFF4E0';      // Light orange background (exact match for Ready to go)
                                borderColor = '#F0AD4E';  // Orange border
                                textColor = '#F0AD4E';    // Orange text
                              } else if (ticket.status === 'On-going' || ticket.status === 'Ongoing' || ticket.status === 'On-Going') {
                                bgColor = '#E3F0F7';      // Light blue background (exact match for On-Going)
                                borderColor = '#056295';  // Blue border
                                textColor = '#056295';    // Blue text
                              } else if (ticket.status === 'Completed') {
                                bgColor = '#DFF0D8';      // Light green background (exact match for Completed)
                                borderColor = '#00703C';  // Green border
                                textColor = '#00703C';    // Green text
                              } else if (ticket.status === 'Pending') {
                                bgColor = '#FFF4E0';      // Light orange background (exact match for Pending)
                                borderColor = '#F0AD4E';  // Orange border
                                textColor = '#F0AD4E';    // Orange text
                              } else if (ticket.status === 'Rejected' || ticket.status === 'Cancelled') {
                                bgColor = '#FFE0E0';      // Light red background (exact match for Rejected/Cancelled)
                                borderColor = '#DC3545';  // Red border
                                textColor = '#DC3545';    // Red text
                              }
                              
                              console.log('Selected Color:', bgColor);
                              console.log('Ticket Itinerary Data:', ticket.itinerary);
                              console.log('Date From:', ticket.itinerary?.date_from);
                              console.log('Date To:', ticket.itinerary?.date_to);
                              console.log('Departure Time:', ticket.itinerary?.departure_time);
                              console.log('Driver Data:', ticket.itinerary?.driver);
                              console.log('Driver full_name:', ticket.itinerary?.driver?.full_name);
                              console.log('Driver full_name2:', ticket.itinerary?.driver?.full_name2);
                              
                              // Get start and end dates
                              const startDate = ticket.itinerary?.date_from || ticket.created_at;
                              const endDate = ticket.itinerary?.date_to || startDate;
                              
                              // Create base event template
                              const eventTemplate = {
                                id: ticket.id,
                                title: ticket.ticket_number || 'No Ticket Number',
                                backgroundColor: bgColor,
                                borderColor: borderColor,
                                textColor: textColor,
                                classNames: ticket.status === 'Completed' ? ['completed-event'] : 
                                           ticket.status === 'Pending' ? ['pending-event'] :
                                           (ticket.status === 'On-going' || ticket.status === 'Ongoing') ? ['ongoing-event'] :
                                           (ticket.status === 'Rejected' || ticket.status === 'Cancelled') ? ['rejected-event'] : [],
                                extendedProps: {
                                  driver_name: ticket.driver_name || 'No Driver Assigned',
                                  driver_contact_num: ticket.driver_contact_num || null,
                                  vehicle_model: ticket.itinerary?.vehicle?.model || '',
                                  vehicle_type: ticket.itinerary?.vehicle?.type || '',
                                  plate_number: ticket.itinerary?.vehicle?.plate_number || '',
                                  destination: ticket.itinerary?.destination || 'No Destination',
                                  status: ticket.status,
                                  purpose: ticket.itinerary?.purpose || 'No Purpose',
                                  head_of_party: ticket.head_of_party || 'Not specified',
                                  passengers: Array.isArray(ticket.passengers) ? ticket.passengers : [],
                                  travel_order_employee_contact_num: ticket.itinerary?.travel_order_employee?.contact_num || '-',
                                  date_from: ticket.itinerary?.date_from,
                                  date_to: ticket.itinerary?.date_to,
                                  departure_time: ticket.itinerary?.departure_time
                                }
                              };
                              
                              // Parse dates and create events for each day in range (inclusive)
                              if (startDate && endDate) {
                                const start = new Date(startDate);
                                const end = new Date(endDate);
                                
                                // Loop through each day from start to end (inclusive)
                                for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
                                  const event = {
                                    ...eventTemplate,
                                    start: new Date(d).toISOString().split('T')[0] // Format as YYYY-MM-DD
                                  };
                                  allEvents.push(event);
                                }
                              } else {
                                // Fallback if no dates
                                const event = {
                                  ...eventTemplate,
                                  start: startDate || new Date()
                                };
                                allEvents.push(event);
                              }
                            });
                            
                            successCallback(allEvents);
                          })
                          .catch(err => failureCallback(err));
                        },
                      
                        eventContent: function(arg) {
                          const ticketNumber = arg.event.title || 'No Ticket';
                          return { 
                            html: `<div style="padding: 2px 4px; font-size: 11px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; border-radius: 3px; background: rgba(255,255,255,0.15);">${ticketNumber}</div>` 
                          };
                        },
                      
                        eventClick: function(info) {
                          const event = info.event;
                          const props = event.extendedProps;
                          
                          // Build the modal content HTML - EXACTLY LIKE DRIVER DASHBOARD
                          let contentHtml = `
<div class="text-sm text-gray-800">
    <!-- Trip Information Section -->
    <div class="mb-3">
        <h4 class="font-bold text-gray-900 border-b pb-1.5 mb-2 text-base">Trip Information</h4>
        <table class="w-full">
            <tbody>
                <!-- Driver -->
                <tr>
                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Driver:</td>
                    <td class="text-gray-900 py-0.5 align-top">${props.driver_name || 'N/A'}</td>
                </tr>

                <!-- Contact Number -->
                <tr>
                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Contact Number:</td>
                    <td class="text-gray-900 py-0.5 align-top">${props.driver_contact_num || '-'}</td>
                </tr>

                <!-- Vehicle -->
                <tr>
                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Vehicle:</td>
                    <td class="text-gray-900 py-0.5 align-top">
                        ${(() => {
                            const model = props.vehicle_model || '';
                            const plate = props.plate_number || '';
                            
                            if (!model) return 'N/A';
                            
                            // Format: "Model - Plate Number"
                            return plate 
                                ? `${model} - ${plate}`
                                : model;
                        })()}
                    </td>
                </tr>

                <!-- Destination -->
                <tr>
                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Destination:</td>
                    <td class="text-gray-900 py-0.5 align-top">${props.destination || 'N/A'}</td>
                </tr>

                <!-- Purpose -->
                <tr>
                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Purpose:</td>
                    <td class="text-gray-900 py-0.5 align-top whitespace-pre-wrap">${props.purpose || 'N/A'}</td>
                </tr>

                <!-- Departure Date -->
                <tr>
                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Departure Date:</td>
                    <td class="text-gray-900 py-0.5 align-top">
                        ${(() => {
                            if (!props.date_from) return 'N/A';
                            
                            const dateFrom = new Date(props.date_from);
                            const dateTo = props.date_to ? new Date(props.date_to) : dateFrom;
                            
                            // Check if same date
                            const isSameDate = dateFrom.toDateString() === dateTo.toDateString();
                            
                            if (isSameDate) {
                                // Same date: Show "Mar 14, 2026"
                              return dateFrom.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                            } else {
                                // Different dates
                                const fromMonth = dateFrom.toLocaleDateString('en-US', { month: 'short' });
                                const fromDay = dateFrom.getDate();
                                const fromYear = dateFrom.getFullYear();
                                const toMonth = dateTo.toLocaleDateString('en-US', { month: 'short' });
                                const toDay = dateTo.getDate();
                                const toYear = dateTo.getFullYear();
                                
                                // Check if same month and year
                                if (fromMonth === toMonth && fromYear === toYear) {
                                    // Same month/year: "Jan 1-2, 2026"
                                  return `${fromMonth} ${fromDay}-${toDay}, ${fromYear}`;
                                }
                                // Check if same year but different month
                                else if (fromYear === toYear) {
                                    // Same year: "Jan 31 - Feb 1, 2026"
                                  return `${fromMonth} ${fromDay} - ${toMonth} ${toDay}, ${fromYear}`;
                                }
                                // Different years
                                else {
                                    // Different years: "Dec 31, 2026 - Jan 1, 2027"
                                  return `${fromMonth} ${fromDay}, ${fromYear} - ${toMonth} ${toDay}, ${toYear}`;
                                }
                            }
                        })()}
                    </td>
                </tr>

                <!-- Departure Time -->
                <tr>
                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Departure Time:</td>
                    <td class="text-gray-900 py-0.5 align-top">
                        ${(() => {
                            if (!props.departure_time) return 'N/A';
                            
                            // Parse the time and format as "9:00 AM"or "1:30 PM"
                            const timeStr = props.departure_time;
                            const [hours, minutes] = timeStr.split(':');
                            const hour = parseInt(hours);
                            const ampm = hour >= 12 ? 'PM' : 'AM';
                            const displayHour = hour % 12 || 12;
                          return `${displayHour}:${minutes} ${ampm}`;
                        })()}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Employee Information Section -->
    <div>
        <h4 class="font-bold text-gray-900 border-b pb-1.5 mb-2 text-base">Employee Information</h4>
        <table class="w-full">
            <tbody>
                <!-- Head of Party -->
                <tr>
                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Head of Party:</td>
                    <td class="text-gray-900 py-0.5 align-top">${props.head_of_party || 'Not specified'}</td>
                </tr>

                <!-- Contact Number -->
                <tr>
                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Contact Number:</td>
                    <td class="text-gray-900 py-0.5 align-top">${props.travel_order_employee_contact_num || '-'}</td>
                </tr>

                <!-- Additional Passengers -->
                <tr>
                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Passengers:</td>
                    <td class="text-gray-900 py-0.5 align-top">
                        ${(() => {
                            const passengers = Array.isArray(props.passengers) ? props.passengers : [];
                            const validPassengers = passengers.filter(p => p && p.trim() !== '');
                            
                            if (validPassengers.length === 0) {
                                return '<span class="text-gray-500 italic">No passengers listed</span>';
                            }
                            
                            // Display as bullet list like trip-ticket-show
                            let html = '<ul class="list-disc pl-5 space-y-1">';
                            validPassengers.forEach(p => {
                                html += `<li>${p}</li>`;
                            });
                            html += '</ul>';
                            return html;
                        })()}
                    </td>
                </tr>

                <!-- Status/Remarks -->
                <tr class="border-t pt-2 mt-1">
                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">
                        ${props.status === 'Approved' ? 'Remarks:' : 'Status:'}
                    </td>
                    <td class="py-0.5 align-top">
                        ${(() => {
                            if (props.status === 'Approved') {
                              return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold" style="background:#FFF4E0; color:#F0AD4E; border:1px solid #F0AD4E;">Ready to go</span>';
                            }
                            
                            // Status badge colors matching dashboard cards
                          const statusColors = {
                                'Pending': { bg: '#FFF4E0', text: '#F0AD4E', border: '#F0AD4E' },
                                'On-Going': { bg: '#E3F0F7', text: '#056295', border: '#056295' },
                                'Ongoing': { bg: '#E3F0F7', text: '#056295', border: '#056295' },
                                'Completed': { bg: '#DFF0D8', text: '#00703C', border: '#00703C' },
                                'Cancelled': { bg: '#FFE0E0', text: '#DC3545', border: '#DC3545' }
                            };
                            
                          const colors = statusColors[props.status] || { bg: '#f3f4f6', text: '#6b7280', border: '#d1d5db' };
                          return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold" style="background:${colors.bg}; color:${colors.text}; border:1px solid ${colors.border};">${props.status}</span>`;
                        })()}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>`;
                          
                          // Debug: Log the props to see what data we have
                          console.log('Modal Props:', props);
                          console.log('Modal Props Date From:', props.date_from);
                          console.log('Modal Props Date To:', props.date_to);
                          console.log('Modal Props Departure Time:', props.departure_time);
                          
                          // Set the modal content
                          document.getElementById('modal-body-content').innerHTML = contentHtml;
                          
                          // Show modal
                          const modal = document.getElementById('trip-detail-modal');
                          const modalContent = document.getElementById('modalContent');
                          
                          // Show modal backdrop immediately
                          modal.classList.remove('hidden');
                          
                          // Add overlay to body
                          document.body.classList.add('modal-open');
                          
                          // Trigger reflow
                          void modal.offsetWidth;
                          
                          // Animate in
                          modalContent.classList.remove('scale-95', 'opacity-0');
                          modalContent.classList.add('scale-100', 'opacity-100');
                          
                          // Prevent body scroll
                          document.body.style.overflow = 'hidden';
                        }
                      });
                      
                      calendar.render();
                      
                      // Apply scrollable events
                      function applyScrollable() {
                        document.querySelectorAll('.fc-daygrid-day-frame').forEach(dayFrame => {
                          const eventsContainer = dayFrame.querySelector('.fc-daygrid-day-events');
                          if (eventsContainer) {
                            eventsContainer.style.maxHeight = '80px';
                            eventsContainer.style.overflowY = 'auto';
                          }
                        });
                      }
                      
                      setTimeout(applyScrollable, 500);
                      
                      // Handle month change - update both calendar and status cards
                      monthYearInput.addEventListener('change', function() {
                        const [y,m] = this.value.split('-').map(Number);
                        calendar.gotoDate(new Date(y, m - 1, 1));
                        calendar.refetchEvents();
                        setTimeout(applyScrollable, 500);
                        
                        // Update status cards for selected month
                        updateStatusCards(this.value);
                      });
                      
                      // Function to update status cards based on selected month
                      function updateStatusCards(monthYear) {
                        fetch(`/api/trip-tickets/status-counts?month=${monthYear}`)
                          .then(res => res.json())
                          .then(data => {
                            document.getElementById('pendingCount').innerText = data.pending || 0;
                            document.getElementById('ongoingCount').innerText = data.ongoing || 0;
                            document.getElementById('completedCount').innerText = data.completed || 0;
                          })
                          .catch(err => console.error('Error updating status counts:', err));
                      }
                      
                      // Initial status card update
                      updateStatusCards(monthYearInput.value);
                    });
                    </script>
                    
                    <script>
                        // Modal close functionality
                        const tripModal = document.getElementById('trip-detail-modal');
                        const modalCloseBtn = document.getElementById('modal-close-btn');
                        
                        function closeTripModal() {
                            const modalContent = document.getElementById('modalContent');
                            
                            // Animate out
                            modalContent.classList.remove('scale-100', 'opacity-100');
                            modalContent.classList.add('scale-95', 'opacity-0');
                            
                            // Wait for animation to complete before hiding
                            setTimeout(() => {
                                tripModal.classList.add('hidden');
                                document.body.classList.remove('modal-open');
                                document.body.style.overflow = '';
                            }, 200); // Match this with transition duration
                        }
                        
                        if (modalCloseBtn) {
                            modalCloseBtn.addEventListener('click', closeTripModal);
                        }
                        
                        // Close modal when clicking outside
                        tripModal.addEventListener('click', function(event) {
                            if (event.target === tripModal) {
                                closeTripModal();
                            }
                        });
                        
                        // Close modal with Escape key
                        document.addEventListener('keydown', function(e) {
                            if (e.key === 'Escape') {
                                closeTripModal();
                            }
                        });
                    </script>
                                        
                    <!-- Status Cards -->
                    <div class="dashboard-cards" style="
                        display:flex;
                        flex-wrap:wrap;
                        gap:15px;
                        justify-content:space-between;
                        width:100%;
                        margin-top:10px;
                    ">
                                        
                        <!-- PENDING -->
                        <div class="dashboard-card" style="
                            flex:1;
                            min-width:250px;
                            height:100px;
                            display:flex;
                            align-items:center;
                            justify-content:space-between;
                            padding:20px;
                            background:#FFF4E0; /* lighter amber for bg */
                            border:2px solid #F0AD4E;
                            border-radius:8px;
                            box-shadow:0 4px 8px rgba(240,173,78,0.15);
                        ">
                            <i class="fas fa-file-alt" style="font-size:40px;color:#F0AD4E;"></i>
                            <div style="text-align:right;">
                                <p id="pendingCount" style="font-size:22px;font-weight:700;margin:0;color:#F0AD4E;">{{ $pendingCount ?? 0 }}</p>
                                <span style="font-size:16px;font-weight:600;color:#F0AD4E;">PENDING</span>
                            </div>
                        </div>
                                        
                        <!-- ON-GOING -->
                        <div class="dashboard-card" style="
                            flex:1;
                            min-width:250px;
                            height:100px;
                            display:flex;
                            align-items:center;
                            justify-content:space-between;
                            padding:20px;
                            background:#E3F0F7; /* lighter blue for bg */
                            border:2px solid #056295;
                            border-radius:8px;
                            box-shadow:0 4px 8px rgba(5,98,149,0.15);
                        ">
                            <i class="fas fa-spinner" style="font-size:40px;color:#056295;"></i>
                            <div style="text-align:right;">
                                <p id="ongoingCount" style="font-size:22px;font-weight:700;margin:0;color:#056295;">{{ $ongoingCount ?? 0 }}</p>
                                <span style="font-size:16px;font-weight:600;color:#056295;">ON-GOING</span>
                            </div>
                        </div>
                                        
                        <!-- COMPLETED -->
                        <div class="dashboard-card" style="
                            flex:1;
                            min-width:250px;
                            height:100px;
                            display:flex;
                            align-items:center;
                            justify-content:space-between;
                            padding:20px;
                            background:#DFF0D8; /* lighter green for bg */
                            border:2px solid #00703C;
                            border-radius:8px;
                            box-shadow:0 4px 8px rgba(0,112,60,0.15);
                        ">
                            <i class="fas fa-check-circle" style="font-size:40px;color:#00703C;"></i>
                            <div style="text-align:right;">
                                <p id="completedCount" style="font-size:22px;font-weight:700;margin:0;color:#00703C;">{{ $completedCount ?? 0 }}</p>
                                <span style="font-size:16px;font-weight:600;color:#00703C;">COMPLETED</span>
                            </div>
                        </div>
                    </div>
                                        

                                        
                    </div>
<!-- ===============================
VEHICLES & DRIVERS ROW
================================ -->

<div style="display:flex; gap:20px; margin-top:20px; flex-wrap:wrap; width:100%; max-width:100%; overflow-x:auto;">

  <!-- ===== LEFT COLUMN: MOST USED VEHICLES (PIE CHART) ===== -->
  <div style="flex:1; min-width:300px; width:100%;">
    <div class="analytics-card p-3 mb-4" style="background:#f0f8ff;border-radius:8px;">

      <div class="analytics-header" style="padding:6px 8px; margin-bottom:1px;">
        <h4 class="mb-0" style="font-size:.9rem; color:#00703C; font-weight: bold;">
          Most Used Vehicles (<span id="vehiclesDateRange">--Timeframe--</span>)
        </h4>
        <p class="text-success small mb-0" style="font-size:.8rem; color:#00703C;">
          Vehicles with the highest number of completed trips.
        </p>
      </div>

      <div class="d-flex gap-1 align-items-center flex-wrap" style="padding:2px 4px 6px; margin-bottom:2px;">
        <select id="vehiclePeriod" class="form-select form-select-sm" style="max-width:100px; font-size:.75rem; height:30px; padding:2px 8px;">
          <option value="monthly" selected>Monthly</option>
          <option value="weekly">Weekly</option>
          <option value="yearly">Yearly</option>
        </select>
        <input type="week" id="vehicleFilterWeek" class="form-control form-control-sm" style="max-width:135px; display:none; font-size:.75rem; height:30px; padding:2px 8px;">
        <input type="month" id="vehicleFilterMonth" class="form-control form-control-sm" style="max-width:135px; font-size:.75rem; height:30px; padding:2px 8px;" value="{{ date('Y-m') }}">
        <input type="number" id="vehicleFilterYear" class="form-control form-control-sm" style="max-width:100px; display:none; font-size:.75rem; height:30px; padding:2px 8px;" placeholder="Year" min="2000" max="2100" value="{{ date('Y') }}">
      </div>

      <div style="height:300px; display:flex; align-items:center; justify-content:center;">
        <canvas id="vehiclePieChart"></canvas>
      </div>

    </div>
  </div>

  <!-- ===== RIGHT COLUMN: MOST ASSIGNED DRIVERS (BAR GRAPH) ===== -->
  <div style="flex:1; min-width:300px; width:100%;">
    <div class="analytics-card p-3 mb-4" style="background:#f0f8ff;border-radius:8px;">

      <div class="analytics-header" style="padding:6px 8px; margin-bottom:1px;">
        <h4 class="mb-0" style="font-size:.9rem; color:#00703C; font-weight: bold;">
          Most Assigned Drivers (<span id="driversDateRange">--Timeframe--</span>)
        </h4>
        <p class="text-success small mb-0" style="font-size:.8rem; color:#00703C;">
          Drivers with the highest number of completed trips.
        </p>
      </div>

      <div class="d-flex gap-1 align-items-center flex-wrap" style="padding:2px 4px 6px; margin-bottom:2px;">
        <select id="driverPeriod" class="form-select form-select-sm" style="max-width:100px; font-size:.75rem; height:30px; padding:2px 8px;">
          <option value="monthly" selected>Monthly</option>
          <option value="weekly">Weekly</option>
          <option value="yearly">Yearly</option>
        </select>
        <input type="week" id="driverFilterWeek" class="form-control form-control-sm" style="max-width:135px; display:none; font-size:.75rem; height:30px; padding:2px 8px;">
        <input type="month" id="driverFilterMonth" class="form-control form-control-sm" style="max-width:135px; font-size:.75rem; height:30px; padding:2px 8px;" value="{{ date('Y-m') }}">
        <input type="number" id="driverFilterYear" class="form-control form-control-sm" style="max-width:100px; display:none; font-size:.75rem; height:30px; padding:2px 8px;" placeholder="Year" min="2000" max="2100" value="{{ date('Y') }}">
      </div>

      <div style="height:300px;">
        <canvas id="driverBarChart"></canvas>
      </div>

    </div>
  </div>

</div>

<!-- ===============================
COMPLETED TRIPS & DESTINATIONS ROW
================================ -->

<div style="display:flex; gap:20px; margin-top:20px; flex-wrap:wrap; width:100%; max-width:100%; overflow-x:auto;">

  <!-- ===== LEFT COLUMN: COMPLETED TRIPS ===== -->
  <div style="flex:1; min-width:300px; width:100%;">
    <div class="analytics-card p-3 mb-4" style="background:#f0f8ff;border-radius:8px;">

      <div class="analytics-header" style="padding:6px 8px; margin-bottom:1px;">
        <h4 class="mb-0" style="font-size:.9rem; color:#00703C; font-weight: bold;">
          Completed Trips (<span id="completedTripsDateRange">--Timeframe--</span>)
        </h4>
        <p class="text-success small mb-0" style="font-size:.8rem; color:#00703C;">
          Overview of completed trips for the selected period.
        </p>
      </div>

      <div class="d-flex gap-1 align-items-center flex-wrap" style="padding:2px 4px 6px; margin-bottom:2px;">
        <select id="completedTripsPeriod" class="form-select form-select-sm" style="max-width:100px; font-size:.75rem; height:30px; padding:2px 8px;">
          <option value="yearly" selected>Yearly</option>
          <option value="monthly">Monthly</option>
          <option value="weekly">Weekly</option>
        </select>
        <input type="week" id="completedTripsFilterWeek" class="form-control form-control-sm" style="max-width:135px; display:none; font-size:.75rem; height:30px; padding:2px 8px;">
        <input type="month" id="completedTripsFilterMonth" class="form-control form-control-sm" style="max-width:135px; font-size:.75rem; height:30px; padding:2px 8px;" value="{{ date('Y-m') }}">
        <input type="number" id="completedTripsFilterYear" class="form-control form-control-sm" style="max-width:100px; display:none; font-size:.75rem; height:30px; padding:2px 8px;" placeholder="Year" min="2000" max="2100" value="{{ date('Y') }}">
      </div>

      <div style="height:300px; display:flex; align-items:center; justify-content:center;">
        <canvas id="completedTripsChart"></canvas>
      </div>

    </div>
  </div>

  <!-- ===== RIGHT COLUMN: TOP 10 DESTINATIONS ===== -->
  <div style="flex:1; min-width:300px; width:100%;">
    <div class="analytics-card p-3 mb-4" style="background:#f0f8ff;border-radius:8px;">

      <div class="analytics-header" style="padding:6px 8px; margin-bottom:1px;">
        <h4 class="mb-0" style="font-size:.9rem; color:#00703C; font-weight: bold;">
          Top 10 Regions (<span id="destinationsDateRange">--Timeframe--</span>)
        </h4>
        <p class="text-success small mb-0" style="font-size:.8rem; color:#00703C;">
          Most visited regions for the selected period.
        </p>
      </div>

      <div class="d-flex gap-1 align-items-center flex-wrap" style="padding:2px 4px 6px; margin-bottom:2px;">
        <select id="destinationPeriod" class="form-select form-select-sm" style="max-width:100px; font-size:.75rem; height:30px; padding:2px 8px;">
          <option value="monthly" selected>Monthly</option>
          <option value="weekly">Weekly</option>
          <option value="yearly">Yearly</option>
        </select>
        <input type="week" id="destinationFilterWeek" class="form-control form-control-sm" style="max-width:135px; display:none; font-size:.75rem; height:30px; padding:2px 8px;">
        <input type="month" id="destinationFilterMonth" class="form-control form-control-sm" style="max-width:135px; font-size:.75rem; height:30px; padding:2px 8px;" value="{{ date('Y-m') }}">
        <input type="number" id="destinationFilterYear" class="form-control form-control-sm" style="max-width:100px; display:none; font-size:.75rem; height:30px; padding:2px 8px;" placeholder="Year" min="2000" max="2100" value="{{ date('Y') }}">
      </div>

      <div style="height:300px;">
        <canvas id="topDestinationsChart"></canvas>
      </div>

    </div>
  </div>

</div>

<!-- Chart.js Initialization -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all charts
    initVehicleChart();
    initDriverChart();
    initCompletedTripsChart();
    initDestinationsChart();
    
    // ========== VEHICLE PIE CHART ==========
    function initVehicleChart() {
        const ctx = document.getElementById('vehiclePieChart');
        if (!ctx) return;
        
        window.vehicleChart = new Chart(ctx, {
            type: 'pie',
           data: {
                labels: [],
               datasets: [{
                   data: [],
                    backgroundColor: [
                        '#00703C', '#056295', '#F0AD4E', '#D9534F', '#5CB85C',
                        '#5BC0DE', '#428BCA', '#915FBB', '#B6A6D6', '#E0E0E0'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            font: { size: 10 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${label}: ${value} trips (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        
        // Load initial data (none - waiting for timeframe selection)
        updateVehicleChart();
    }
    
    function updateVehicleChart() {
        const period = document.getElementById('vehiclePeriod').value;
        const filterValue = getFilterValue('vehicle', period);
            
        if (!period || !filterValue) {
            document.getElementById('vehiclesDateRange').textContent = '--Timeframe--';
            window.vehicleChart.data.labels = [];
            window.vehicleChart.data.datasets[0].data = [];
            window.vehicleChart.update();
            return;
        }
            
        fetch(`/motorpool/api/analytics/most-used-vehicles?period=${period}&filter_value=${encodeURIComponent(filterValue)}`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('vehiclesDateRange').textContent = data.dateRange;
                
                // Check if there's data
                if (!data.data || data.data.length === 0) {
                    // No data - show empty chart with message
                    window.vehicleChart.data.labels = ['No Data Available'];
                    window.vehicleChart.data.datasets[0].data = [0];
                    window.vehicleChart.options.plugins.tooltip.enabled = false;
                } else {
                    // Has data - show normal chart
                    window.vehicleChart.data.labels = data.data.map(v => v.label);
                    window.vehicleChart.data.datasets[0].data = data.data.map(v => v.count);
                    window.vehicleChart.options.plugins.tooltip.enabled = true;
                }
                
                window.vehicleChart.update();
            })
            .catch(err => console.error('Error loading vehicle data:', err));
    }
    
    // ========== DRIVER BAR CHART ==========
    function initDriverChart() {
        const ctx = document.getElementById('driverBarChart');
        if (!ctx) return;
        
        window.driverChart = new Chart(ctx, {
            type: 'bar',
           data: {
                labels: [],
               datasets: [{
                    label: 'Completed Trips',
                   data: [],
                    backgroundColor: '#00703C',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ` ${context.parsed.x} trips`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    },
                    y: {
                        ticks: {
                            font: { size: 10 },
                            autoSkip: false
                        }
                    }
                }
            }
        });
        
        updateDriverChart();
    }
    
    function updateDriverChart() {
        const period= document.getElementById('driverPeriod').value;
        const filterValue = getFilterValue('driver', period);
            
        if (!period || !filterValue) {
            document.getElementById('driversDateRange').textContent = '--Timeframe--';
            window.driverChart.data.labels = [];
            window.driverChart.data.datasets[0].data = [];
            window.driverChart.update();
            return;
        }
            
        fetch(`/motorpool/api/analytics/most-assigned-drivers?period=${period}&filter_value=${encodeURIComponent(filterValue)}`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('driversDateRange').textContent = data.dateRange;
                
                // Check if there's data
                if (!data.data || data.data.length === 0) {
                    // No data - show empty chart with message
                    window.driverChart.data.labels = ['No Data Available'];
                    window.driverChart.data.datasets[0].data = [0];
                    window.driverChart.options.plugins.tooltip.enabled = false;
                } else {
                    // Has data - show normal chart
                    window.driverChart.data.labels = data.data.map(d => d.label);
                    window.driverChart.data.datasets[0].data = data.data.map(d => d.count);
                    window.driverChart.options.plugins.tooltip.enabled = true;
                }
                
                window.driverChart.update();
            })
            .catch(err => console.error('Error loading driver data:', err));
    }
    
    // ========== COMPLETED TRIPS CHART (LINE CHART) ==========
    function initCompletedTripsChart() {
        const ctx = document.getElementById('completedTripsChart');
        if (!ctx) return;
        
        window.completedTripsChart = new Chart(ctx, {
            type: 'line',
           data: {
                labels: [],
               datasets: [{
                   label: 'Completed Trips',
                   data: [],
                    backgroundColor: 'rgba(0, 112, 60, 0.2)',
                    borderColor: '#00703C',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#00703C',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            font: { size: 10 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed.y || 0;
                                return `${value} completed trip(s)`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            stepSize: 1
                        },
                        title: {
                            display: true,
                            text: 'Number of Completed Trips',
                            font: { size: 11, weight: 'bold' }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Month',
                            font: { size: 11, weight: 'bold' }
                        }
                    }
                }
            }
        });
        
        updateCompletedTripsChart();
    }
    
    function updateCompletedTripsChart() {
        const period = document.getElementById('completedTripsPeriod').value;
        const filterValue = getFilterValue('completedTrips', period);
            
        if (!period || !filterValue) {
            document.getElementById('completedTripsDateRange').textContent = '--Timeframe--';
            window.completedTripsChart.data.labels = [];
            window.completedTripsChart.data.datasets[0].data = [];
            window.completedTripsChart.update();
            return;
        }
        
        let apiUrl, year, xAxisLabel;
                
        // Determine API URL, year, and X-axis label based on period type
        if (period === 'yearly') {
            // Yearly: Show all 12 months of the selected year
            year= filterValue;
            apiUrl = `/motorpool/api/analytics/completed-trips-monthly?year=${year}`;
          xAxisLabel = 'Month';
        } else if (period === 'monthly') {
            // Monthly: Show days within the selected month
            // Parse YYYY-MM format
            const [selectedYear, selectedMonth] = filterValue.split('-');
            year = selectedYear;
            apiUrl = `/motorpool/api/analytics/completed-trips-daily?year=${year}&month=${selectedMonth}`;
          xAxisLabel = 'Day of Month';
        } else if (period === 'weekly') {
            // Weekly: Show days within the selected week
            // Parse YYYY-Www format (e.g., 2026-W10)
            const match = filterValue.match(/(\d{4})-W(\d{2})/);
            if (match) {
                year= match[1];
                const weekNum = match[2];
                apiUrl = `/motorpool/api/analytics/completed-trips-weekly?year=${year}&week=${weekNum}`;
            } else {
                // Fallback to current week
                const now = new Date();
                year= now.getFullYear();
                const weekNum = getWeekNumber(now);
                apiUrl = `/motorpool/api/analytics/completed-trips-weekly?year=${year}&week=${weekNum}`;
            }
          xAxisLabel = 'Day of Week';
        } else {
            // Default to yearly
            year=new Date().getFullYear();
            apiUrl = `/motorpool/api/analytics/completed-trips-monthly?year=${year}`;
          xAxisLabel = 'Month';
        }
                
        // Update X-axis label before fetching data
        window.completedTripsChart.options.scales.x.title.text = xAxisLabel;
            
        fetch(apiUrl)
            .then(res => res.json())
            .then(data => {
                document.getElementById('completedTripsDateRange').textContent = data.dateRange;
                
                // Check if there's data
                if (!data.data || data.data.length === 0) {
                    // No data - show empty chart with message
                    window.completedTripsChart.data.labels = ['No Data Available'];
                    window.completedTripsChart.data.datasets[0].data = [0];
                    window.completedTripsChart.options.plugins.tooltip.enabled = false;
                } else {
                    // Has data - show chart based on period
                    if (period === 'yearly') {
                        // Monthly view: Jan-Dec
                        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                                           'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                        window.completedTripsChart.data.labels = data.data.map(d => monthNames[d.month - 1]);
                    } else if (period === 'monthly') {
                        // Daily view: Day numbers (1-31)
                        window.completedTripsChart.data.labels = data.data.map(d => `Day ${d.day}`);
                    } else if (period === 'weekly') {
                        // Daily view within week: Show dates
                        window.completedTripsChart.data.labels = data.data.map(d => {
                            const date = new Date(d.date);
                            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                        });
                    }
                    
                    window.completedTripsChart.data.datasets[0].data = data.data.map(d => d.count);
                    window.completedTripsChart.options.plugins.tooltip.enabled = true;
                }
                
                window.completedTripsChart.update();
            })
            .catch(err => console.error('Error loading completed trips data:', err));
    }
    
    // Helper function to get ISO week number
    function getWeekNumber(date) {
        const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
        const dayNum = d.getUTCDay() || 7;
        d.setUTCDate(d.getUTCDate() +4 - dayNum);
        const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
        return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
    }
    
    // ========== TOP DESTINATIONS CHART ==========
    function initDestinationsChart() {
        const ctx = document.getElementById('topDestinationsChart');
        if (!ctx) return;
        
        window.destinationsChart = new Chart(ctx, {
            type: 'bar',
           data: {
                labels: [],
               datasets: [{
                    label: 'Visits',
                   data: [],
                    backgroundColor: '#056295',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ` ${context.parsed.y} visits`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    },
                    x: {
                        ticks: {
                            font: { size: 9 },
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                }
            }
        });
        
        updateDestinationsChart();
    }
    
    function updateDestinationsChart() {
        const period= document.getElementById('destinationPeriod').value;
        const filterValue = getFilterValue('destination', period);
        
        if (!period || !filterValue) {
            document.getElementById('destinationsDateRange').textContent = '--Timeframe--';
            window.destinationsChart.data.labels = [];
            window.destinationsChart.data.datasets[0].data = [];
            window.destinationsChart.update();
            return;
        }
        
        fetch(`/motorpool/api/analytics/top-destinations?period=${period}&filter_value=${encodeURIComponent(filterValue)}`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('destinationsDateRange').textContent = data.dateRange;
                
                // Check if there's data
                if (!data.data || data.data.length === 0) {
                    // No data - show empty chart with message
                    window.destinationsChart.data.labels = ['No Data Available'];
                    window.destinationsChart.data.datasets[0].data = [0];
                    window.destinationsChart.options.plugins.tooltip.enabled = false;
                } else {
                    // Has data - show normal chart
                    window.destinationsChart.data.labels = data.data.map(d => d.destination);
                    window.destinationsChart.data.datasets[0].data = data.data.map(d => d.count);
                    window.destinationsChart.options.plugins.tooltip.enabled = true;
                }
                
                window.destinationsChart.update();
            })
            .catch(err => console.error('Error loading destinations data:', err));
    }
    
    // ========== HELPER FUNCTIONS ==========
    function getFilterValue(prefix, period) {
        if (!period) return null;
        
        switch(period) {
            case 'weekly':
                const weekInput = document.getElementById(`${prefix}FilterWeek`);
                return weekInput ? weekInput.value : null;
            case 'monthly':
                const monthInput = document.getElementById(`${prefix}FilterMonth`);
                return monthInput ? monthInput.value : null;
            case 'yearly':
                const yearInput = document.getElementById(`${prefix}FilterYear`);
                return yearInput ? yearInput.value : null;
            default:
                return null;
        }
    }
    
    // ========== EVENT LISTENERS FOR PERIOD SELECTION ==========
    function setupPeriodListeners(prefix) {
        const periodSelect = document.getElementById(`${prefix}Period`);
        const weekInput = document.getElementById(`${prefix}FilterWeek`);
        const monthInput = document.getElementById(`${prefix}FilterMonth`);
        const yearInput = document.getElementById(`${prefix}FilterYear`);
        
        if (!periodSelect) return;
        
        periodSelect.addEventListener('change', function() {
            const period = this.value;
            
            // Hide all inputs first
            if (weekInput) weekInput.style.display = 'none';
            if (monthInput) monthInput.style.display = 'none';
            if (yearInput) yearInput.style.display = 'none';
            
            // Show appropriate input based on selection
            if (period === 'weekly' && weekInput) {
                weekInput.style.display = '';
                weekInput.value = getCurrentWeek();
            } else if (period === 'monthly' && monthInput) {
                monthInput.style.display = '';
                monthInput.value = getCurrentMonth();
            } else if (period === 'yearly' && yearInput) {
                yearInput.style.display = '';
            }
            
            // Update chart based on selection
            updateChart(prefix);
        });
        
        // Add change listeners to filter inputs
        if (weekInput) weekInput.addEventListener('change', () => updateChart(prefix));
        if (monthInput) monthInput.addEventListener('change', () => updateChart(prefix));
        if (yearInput) yearInput.addEventListener('change', () => updateChart(prefix));
    }
    
    function updateChart(prefix) {
        switch(prefix) {
            case 'vehicle': updateVehicleChart(); break;
            case 'driver': updateDriverChart(); break;
            case 'completedTrips': updateCompletedTripsChart(); break;
            case 'destination': updateDestinationsChart(); break;
        }
    }
    
    function getCurrentWeek() {
        const now = new Date();
        const startOfYear = new Date(now.getFullYear(), 0, 1);
        const pastDays = Math.floor((now- startOfYear) / (24 * 60 * 60 * 1000));
        const weekNumber = Math.ceil((pastDays + startOfYear.getDay() +1) / 7);
        return `${now.getFullYear()}-W${String(weekNumber).padStart(2, '0')}`;
    }
    
    function getCurrentMonth() {
        const now = new Date();
        return `${now.getFullYear()}-${String(now.getMonth() +1).padStart(2, '0')}`;
    }
    
    // Setup listeners for all charts
    setupPeriodListeners('vehicle');
    setupPeriodListeners('driver');
    setupPeriodListeners('completedTrips');
    setupPeriodListeners('destination');
    
    // Initialize all charts with default monthly view (current month)
    setTimeout(() => {
        updateVehicleChart();
        updateDriverChart();
        updateCompletedTripsChart();
        updateDestinationsChart();
    }, 100);
});
</script>
@endsection

