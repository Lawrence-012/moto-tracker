@extends('layouts.motorpool-admin')
@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <!-- Dashboard Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Pending Card -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-orange-100 rounded-md p-3">
                            <svg class="h-6 w-6 text-orange-600"fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Pending</dt>
                                <dd class="text-2xl font-semibold text-gray-900">{{ $pendingCount }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- On-going Card -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
                            <svg class="h-6 w-6 text-blue-600"fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">On-going</dt>
                                <dd class="text-2xl font-semibold text-gray-900">{{ $ongoingCount }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Completed Card -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                            <svg class="h-6 w-6 text-green-600"fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Completed</dt>
                                <dd class="text-2xl font-semibold text-gray-900">{{ $completedCount }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Vehicles Card -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-purple-100 rounded-md p-3">
                            <svg class="h-6 w-6 text-purple-600"fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Vehicles</dt>
                                <dd class="text-2xl font-semibold text-gray-900">{{ $vehiclesCount }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendar Section -->
        <div class="bg-white rounded-lg shadow mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-bold text-gray-800">Trip Tickets Calendar</h2>
            </div>
            
            <div class="p-6">
                <!-- Month Selector -->
                <div class="text-center mb-6">
                    <div class="inline-flex items-center gap-3">
                        <label for="monthYear" class="text-sm font-semibold text-gray-700">MONTH OF:</label>
                        <input type="month" id="monthYear" 
                               value="{{ $currentMonth }}"
                               class="px-3 py-1.5 border-2 border-[#1e6031] rounded-md text-sm font-semibold text-[#1e6031] bg-green-50 focus:outline-none focus:border-[#1e6031] cursor-pointer" />
                    </div>
                </div>
                
                <!-- Calendar Container -->
                <div id="motorpoolCalendar" class="w-full"></div>
            </div>
        </div>

        <!-- Approved Travel Orders Table -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-bold text-gray-800">Approved Travel Orders</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Destination</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Created</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($travelOrders as $order)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $order->employee->full_name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $order->destination ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ Str::limit($order->purpose, 50) ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $order->created_at->format('M d, Y') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No approved travel orders found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($travelOrders->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $travelOrders->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Trip Detail Modal (Same as Driver Dashboard) -->
<div id="trip-detail-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 transition-opacity duration-300">
    <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto border-t-4 border-[#1e6031] transform transition-all duration-200 scale-95 opacity-0" id="modalContent">
        <!-- Modal Header -->
        <div class="px-6 py-3 border-b border-gray-200 flex items-center justify-between" style="background: linear-gradient(135deg, #1e6031 0%, #2d7a3e 50%, #1e6031 100%);">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-white bg-opacity-20 flex items-center justify-center">
                    <svg class="h-6 w-6 text-[#1e6031]"fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 13l1-3a2 2 0 012-1h12a2 2 0 012 1l1 3M5 13h14M5 13v4a1 1 0 001 1h1m10-5v4a1 1 0 01-1 1h-1M7 18a1 1 0 100-2 1 1 0 000 2zm10 0a1 1 0 100-2 1 1 0 000 2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-white">Trip Ticket Details</h3>
            </div>
            <button id="modal-close-btn" class="text-white hover:text-gray-200">
                <svg class="h-6 w-6"fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <!-- Modal Content -->
        <div id="modal-body-content" class="px-6 py-3">
            <!-- Content will be loaded dynamically -->
        </div>
        
        <!-- Modal Footer -->
        <div class="px-6 py-3 border-t border-gray-200 flex justify-end bg-gradient-to-r from-gray-50 to-white">
            <button id="modal-close-btn-footer" class="px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-all duration-200 text-sm font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                Close
            </button>
        </div>
    </div>
</div>

<!-- Passengers List Modal -->
<div id="passengersModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 transition-opacity duration-300">
    <div class="bg-white rounded-lg shadow-2xl max-w-md w-full max-h-[80vh] overflow-y-auto border-t-4 border-[#1e6031] transform transition-all duration-200 scale-95 opacity-0" id="passengersModalContent">
        <!-- Modal Header -->
        <div class="px-6 py-3 border-b border-gray-200 flex items-center justify-between" style="background: linear-gradient(135deg, #1e6031 0%, #2d7a3e 50%, #1e6031 100%);">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-white bg-opacity-20 flex items-center justify-center">
                    <svg class="h-6 w-6 text-[#1e6031]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-white">Passengers List</h3>
            </div>
        </div>
        
        <!-- Modal Content -->
        <div id="passengersModalBody" class="px-6 py-4">
            <!-- Content will be loaded dynamically -->
        </div>
        
        <!-- Modal Footer -->
        <div class="px-6 py-3 border-t border-gray-200 flex justify-end bg-gradient-to-r from-gray-50 to-white">
            <button type="button" onclick="closePassengersModal()" class="px-6 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-all duration-200 text-sm font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                Close
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const monthYearInput = document.getElementById('monthYear');
  const calendarEl = document.getElementById('motorpoolCalendar');
  
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
    height: 'auto',
    contentHeight: function() {
      const containerWidth = calendarEl.offsetWidth;
      const screenHeight = window.innerHeight;
      
      if (containerWidth < 400) {
       return Math.min(350, screenHeight * 0.6);
      } else if (containerWidth < 600) {
       return Math.min(450, screenHeight * 0.7);
      } else {
       return Math.min(600, screenHeight * 0.75);
      }
    },
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
    eventLimit: true,
    eventLimitText: 'more',
    titleFormat: { year: 'numeric', month: 'long' },
    aspectRatio: function() {
      const containerWidth = calendarEl.offsetWidth;
     return containerWidth < 600 ? 1.2 : 1.5;
    },
    
    // Custom CLSU theme colors
    eventBackgroundColor: '#004d00',
    eventBorderColor: '#004d00',
    eventTextColor: '#ffffff',
    
    events: function(fetchInfo, successCallback, failureCallback) {
      const mv = monthYearInput.value || (new Date()).toISOString().slice(0,7);
      const startStr = toYMD(fetchInfo.start);
      const endStr = toYMD(fetchInfo.end);
  
      // Fetch ALL trip tickets for the selected month (not driver-specific)
      fetch(`/motorpool/api/calendar-events?start=${startStr}&end=${endStr}`, {
        headers: { 
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          'Accept': 'application/json'
        }
      })
      .then(res => res.json())
      .then(data => {
        console.log('=== API DATA RECEIVED ===');
        console.log('Total events:', data.length);
        if (data.length > 0) {
          console.log('First event:', data[0]);
          console.log('First driver_name:', data[0].driver_name);
        }
        console.log('All driver_names:', data.map(d => d.driver_name));
        console.log('========================');
        
        // Transform data to FullCalendar format
        const events = data.map(ticket => {
          // Determine color based on status
          let bgColor = '#6c757d';      // Gray (default)
          let borderColor = '#6c757d';
          
          console.log('Ticket Status:', ticket.status);
          console.log('Ticket Number:', ticket.ticket_number);
          
          if (ticket.status === 'Pending') {
            bgColor = '#F0AD4E';      // Orange (matches PENDING card)
            borderColor = '#F0AD4E';
          } else if (ticket.status === 'On-going' || ticket.status === 'Ongoing') {
            bgColor = '#056295';      // Blue (matches ON-GOING card)
            borderColor = '#056295';
          } else if (ticket.status === 'Completed') {
            bgColor = '#00703C';      // Green (matches COMPLETED card)
            borderColor = '#00703C';
          } else if (ticket.status === 'Approved') {
            bgColor = '#20C997';      // Teal Green
            borderColor = '#20C997';
          } else if (ticket.status === 'Rejected' || ticket.status === 'Cancelled') {
            bgColor = '#DC3545';      // Red (matches rejection)
            borderColor = '#DC3545';
          }
          
          console.log('Selected Color:', bgColor);
          
          return {
            id: ticket.id,
            title: ticket.title || `${ticket.ticket_number || 'Trip'}`,
            // Use all-day events by specifying date only (no time component)
            start: ticket.itinerary?.date_from ? formatDateForCalendar(ticket.itinerary.date_from) : formatDateForCalendar(ticket.created_at),
            end: ticket.itinerary?.date_to ? formatDateForCalendar(ticket.itinerary.date_to) : null,
            backgroundColor: bgColor,
            borderColor: borderColor,
            textColor: '#ffffff',
            classNames: ticket.status === 'Completed' ? ['completed-event'] : 
                       ticket.status === 'Pending' ? ['pending-event'] :
                       (ticket.status === 'On-going' || ticket.status === 'Ongoing') ? ['ongoing-event'] :
                       (ticket.status === 'Rejected' || ticket.status === 'Cancelled') ? ['rejected-event'] : [],
            extendedProps: {
               driver_name: ticket.driver_name || 'No Driver Assigned',
              vehicle_model: ticket.itinerary?.vehicle?.model || 'No Vehicle',
              plate_number: ticket.itinerary?.vehicle?.plate_number || '',
              destination: ticket.itinerary?.destination || 'No Destination',
              status: ticket.status,
              purpose: ticket.itinerary?.purpose || 'No Purpose',
              head_of_party: ticket.head_of_party || 'Not specified',
              passengers: ticket.passengers || [],
              date_from: ticket.itinerary?.date_from,
              date_to: ticket.itinerary?.date_to,
              departure_time: ticket.itinerary?.departure_time,
              travel_order_employee_full_name: ticket.itinerary?.travel_order_employee?.full_name || null,
              travel_order_employee_contact_num: ticket.itinerary?.travel_order_employee?.contact_num || '-'
            }
          };
        });
        successCallback(events);
      })
      .catch(err => failureCallback(err));
    },
  
    eventContent: function(arg) {
      const driverName = arg.event.title || 'No Driver';
     return { 
        html: `<div style="padding: 4px 6px; font-size: 11px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; border-radius: 3px; background: rgba(255,255,255,0.15);">${driverName}</div>` 
      };
    },
  
    eventClick: function(info) {
      const event = info.event;
      const props = event.extendedProps;
      
      console.log('=== EVENT CLICK DEBUG ===');
      console.log('Full event:', event);
      console.log('Event title:', event.title);
      console.log('Event extendedProps:', props);
      console.log('Driver name from props.driver_name:', props.driver_name);
      console.log('Type of driver_name:', typeof props.driver_name);
      console.log('========================');
      
      // Build the modal content HTML dynamically - EXACTLY LIKE TRIP TICKETS PAGE
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

                <!-- Vehicle -->
                <tr>
                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Vehicle:</td>
                    <td class="text-gray-900 py-0.5 align-top">
                        ${props.vehicle_model && props.plate_number 
                            ? `${props.vehicle_model} - ${props.plate_number}`
                            : props.vehicle 
                                ? props.vehicle 
                                : 'N/A'}
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
                            const hasPassengers = Array.isArray(props.passengers) && props.passengers.length > 0;
                            
                            if (!hasPassengers) {
                                return '<span class="text-gray-500 italic">No additional passengers</span>';
                            }
                            
                            const validPassengers = props.passengers.filter(p => p && p.trim() !== '');
                            const totalPassengers = validPassengers.length;
                            
                            if (totalPassengers === 0) {
                                return '<span class="text-gray-500 italic">No additional passengers</span>';
                            }
                            
                            const firstPassenger = validPassengers[0];
                            
                            if (totalPassengers > 1) {
                                const remainingCount = totalPassengers - 1;
                                const passengersStr = validPassengers.join('|');
                                const ticketNum = (props.ticket_number || 'Trip').replace(/'/g, "\\'");
                                return `<span class="text-gray-900">${firstPassenger}</span><button type="button" onclick="viewPassengersModalFromModal('${passengersStr}', '${ticketNum}')" class="ml-1 text-blue-600 hover:text-blue-800 font-medium cursor-pointer">+${remainingCount} more</button>`;
                            } else {
                                return `<span class="text-gray-900">${firstPassenger}</span>`;
                            }
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
                               return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-800 border border-green-300">Ready to go</span>';
                            }
                            
                            const statusClasses = {
                                'Pending': 'bg-orange-100 text-orange-800 border border-orange-300',
                                'On-going': 'bg-blue-100 text-blue-800 border border-blue-300',
                                'Completed': 'bg-green-100 text-green-800 border border-green-300',
                                'Cancelled': 'bg-red-100 text-red-800 border border-red-300'
                            };
                            
                            const statusClass = statusClasses[props.status] || 'bg-gray-100 text-gray-800 border border-gray-300';
                           return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold ${statusClass}">${props.status}</span>`;
                        })()}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>`;
      
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
  
  // Handle window resize
  let resizeTimeout;
  window.addEventListener('resize', function() {
    clearTimeout(resizeTimeout);
   resizeTimeout = setTimeout(function() {
      calendar.updateSize();
    }, 200);
  });
  
  // Handle month change
  monthYearInput.addEventListener('change', function() {
    const [y,m] = this.value.split('-').map(Number);
    calendar.gotoDate(new Date(y, m - 1, 1));
    calendar.refetchEvents();
  });
  
  // Modal close buttons
 document.getElementById('modal-close-btn').addEventListener('click', function() {
    closeModal();
  });
  
 document.getElementById('modal-close-btn-footer').addEventListener('click', function() {
    closeModal();
  });
  
  function closeModal() {
    const modal = document.getElementById('trip-detail-modal');
    const modalContent = document.getElementById('modalContent');
    
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');
    
    setTimeout(function() {
      modal.classList.add('hidden');
      document.body.classList.remove('modal-open');
      document.body.style.overflow = 'auto';
    }, 200);
  }
  
  // Function to view passengers modal from trip ticket modal
  window.viewPassengersModalFromModal = function(passengersStr, ticketNumber) {
    // Convert pipe-separated string to array
    const passengers = passengersStr.split('|').filter(p => p && p.trim() !== '');
    
    const modal = document.getElementById('passengersModal');
    const modalContent = document.getElementById('passengersModalContent');
    const modalBody = document.getElementById('passengersModalBody');
    
    // Build passengers list HTML
    let passengersHtml = `
        <div class="space-y-2">
            <p class="text-sm text-gray-600 mb-2">
                <span class="font-semibold">Trip Ticket:</span> ${ticketNumber}
            </p>
            <p class="text-sm text-gray-600 mb-2">
                <span class="font-semibold">Total Passengers:</span> ${passengers.length}
            </p>
            <div class="border-t border-gray-200 pt-2">
                <h4 class="font-semibold text-gray-800 mb-2 text-sm">Complete List:</h4>
                <ul class="space-y-1">
        `;
    
    passengers.forEach((passenger, index) => {
        passengersHtml += `
            <li class="flex items-start gap-2 text-sm text-gray-700 py-1">
                <span class="flex-shrink-0 w-5 h-5 bg-green-100 text-green-700 rounded-full flex items-center justify-center text-xs font-semibold">${index + 1}</span>
                <span class="flex-1">${passenger}</span>
            </li>
        `;
    });
    
    passengersHtml += `
                </ul>
            </div>
        </div>
    `;
    
    modalBody.innerHTML = passengersHtml;
    
    // Show modal
    modal.classList.remove('hidden');
    void modal.offsetWidth;
    modalContent.classList.remove('scale-95', 'opacity-0');
    modalContent.classList.add('scale-100', 'opacity-100');
    document.body.style.overflow = 'hidden';
  };
  
  // Function to close passengers modal
  window.closePassengersModal = function() {
    const modal = document.getElementById('passengersModal');
    const modalContent = document.getElementById('passengersModalContent');
    
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
      modal.classList.add('hidden');
      document.body.style.overflow = '';
    }, 200);
  };
  
  // Close passengers modal when clicking outside
  document.getElementById('passengersModal')?.addEventListener('click', function(event) {
    if (event.target === this) {
      closePassengersModal();
    }
  });
  
  // Close passengers modal with Escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      const modal = document.getElementById('passengersModal');
      if (modal && !modal.classList.contains('hidden')) {
        closePassengersModal();
      }
    }
  });
});
</script>
@endpush
@endsection