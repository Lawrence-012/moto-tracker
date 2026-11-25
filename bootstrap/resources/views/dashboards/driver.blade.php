@extends('layouts.driver')

@section('content')
    <div class="py-6 sm:py-8 md:py-12">
        <div class="max-w-7xl mx-auto px-3 sm:px-4 md:px-6 lg:px-8">
            <!-- Error Message -->
            @if(session('error'))
                <div class="mb-6 rounded-md bg-red-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">
                                {{ session('error') }}
                            </h3>
                            <div class="mt-2 text-sm text-red-700">
                                <p>Please contact your system administrator to set up your driver account.</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if($driver)
                <!-- Welcome Section -->
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg border border-green-200 shadow-sm mb-4 sm:mb-6">
                    <div class="px-3 py-3 sm:px-6 sm:py-4">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start flex-1 min-w-0">
                                <!-- Icon -->
                                <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg p-2 sm:p-3 mr-2 sm:mr-3 flex-shrink-0 mt-1">
                                    <svg class="h-5 w-5 sm:h-8 sm:w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                
                                <!-- Text Content -->
                                <div class="min-w-0 flex-1">
                                    <h1 class="text-sm sm:text-lg md:text-xl font-semibold text-gray-800 mb-0 truncate">
                                        Welcome, {{ Auth::user()->name }}!
                                    </h1>
                                    <p class="text-xs sm:text-sm text-gray-600 mt-0.5 sm:mt-1 break-words">
                                        Your personal dashboard for managing trip assignments and tickets
                                    </p>
                                </div>
                            </div>
                            
                            <!-- User Account Dropdown -->
                            <div class="account-menu relative ml-2 sm:ml-4 flex-shrink-0">
                                <button onclick="toggleDriverDropdown()" class="flex items-center gap-1 sm:gap-2 leading-none focus:outline-none p-1.5 sm:p-2 rounded-lg hover:bg-gray-100 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 sm:w-8 sm:h-8 text-gray-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                    </svg>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 sm:h-4 sm:w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <div id="driverAccountDropdown" class="absolute right-0 mt-2 hidden bg-white min-w-[200px] sm:min-w-[240px] rounded-lg shadow-xl z-50 border border-gray-100 overflow-hidden">
                                    <div class="px-3 sm:px-4 py-2 border-b border-gray-100 bg-gradient-to-r from-green-50 to-emerald-50">
                                        <div class="flex items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 sm:w-4 sm:h-4 text-[#1e6031] flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                            </svg>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs font-medium text-gray-900 truncate">
                                                    {{ Auth::user()->name }}
                                                </p>
                                                <p class="text-xs text-gray-500 truncate mt-0.5">
                                                    {{ Auth::user()->email }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="py-2">
                                        <a href="{{ route('driver.profile.edit') }}" class="flex items-center px-3 sm:px-4 py-2 sm:py-3 text-xs sm:text-sm text-gray-700 hover:bg-green-50 hover:text-[#1e6031] transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 sm:w-5 sm:h-5 mr-2 sm:mr-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            Manage Profile
                                        </a>
                                    </div>
                                    <div class="border-t border-gray-100 py-2">
                                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                                            @csrf
                                            <button type="submit" class="flex items-center w-full px-3 sm:px-4 py-2 sm:py-3 text-xs sm:text-sm text-red-600 hover:bg-red-50 transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 sm:w-5 sm:h-5 mr-2 sm:mr-3 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                                </svg>
                                                Logout
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="dashboard-cards" style="
                    display:flex;
                    flex-wrap:wrap;
                    gap:12px;
                    justify-content:space-between;
                    width:calc(100% - 24px);
                    margin: 20px auto 30px auto;
                    padding: 0 12px;
                    box-sizing: border-box;
                ">
                                    
                    <!-- PENDING -->
                    <a href="{{ route('driver.trip-tickets', ['tab' => 'pending']) }}" class="dashboard-card" style="
                        flex:1 1 calc(33.333% - 8px);
                        min-width:calc(33.333% - 8px);
                        height:auto;
                        min-height:90px;
                        display:flex;
                        align-items:center;
                        justify-content:space-between;
                        padding:12px;
                        background:#FFF4E0;
                        border:2px solid #F0AD4E;
                        border-radius:8px;
                        box-shadow:0 4px 8px rgba(240,173,78,0.15);
                    ">
                        <i class="fas fa-file-alt" style="font-size:32px;color:#F0AD4E;"></i>
                        <div style="text-align:right;">
                            <p id="pendingCount" style="font-size:18px;font-weight:700;margin:0;color:#F0AD4E;">{{ $pendingCount ?? 0 }}</p>
                            <span style="font-size:12px;font-weight:600;color:#F0AD4E;">PENDING</span>
                        </div>
                    </a>
                                    
                    <!-- ON-GOING -->
                    <a href="{{ route('driver.trips') }}" class="dashboard-card" style="
                        flex:1 1 calc(33.333% - 8px);
                        min-width:calc(33.333% - 8px);
                        height:auto;
                        min-height:90px;
                        display:flex;
                        align-items:center;
                        justify-content:space-between;
                        padding:12px;
                        background:#E3F0F7;
                        border:2px solid #056295;
                        border-radius:8px;
                        box-shadow:0 4px 8px rgba(5,98,149,0.15);
                        text-decoration:none;
                        cursor:pointer;
                    ">
                        <i class="fas fa-spinner" style="font-size:32px;color:#056295;"></i>
                        <div style="text-align:right;">
                            <p id="ongoingCount" style="font-size:18px;font-weight:700;margin:0;color:#056295;">{{ $ongoingCount ?? 0 }}</p>
                            <span style="font-size:12px;font-weight:600;color:#056295;">ON-GOING</span>
                        </div>
                    </a>
                                    
                    <!-- COMPLETED -->
                    <a href="{{ route('driver.trip-tickets', ['tab' => 'completed']) }}" class="dashboard-card" style="
                        flex:1 1 calc(33.333% - 8px);
                        min-width:calc(33.333% - 8px);
                        height:auto;
                        min-height:90px;
                        display:flex;
                        align-items:center;
                        justify-content:space-between;
                        padding:12px;
                        background:#DFF0D8;
                        border:2px solid #00703C;
                        border-radius:8px;
                        box-shadow:0 4px 8px rgba(0,112,60,0.15);
                    ">
                        <i class="fas fa-check-circle" style="font-size:32px;color:#00703C;"></i>
                        <div style="text-align:right;">
                            <p id="completedCount" style="font-size:18px;font-weight:700;margin:0;color:#00703C;">{{ $completedCount ?? 0 }}</p>
                            <span style="font-size:12px;font-weight:600;color:#00703C;">COMPLETED</span>
                        </div>
                    </a>
                </div>
                
                <style>
                    /* Stack cards vertically on small screens (under 600px) */
                    @media (max-width: 600px) {
                        .dashboard-card {
                            flex: 1 1 100% !important;
                            min-width: 100% !important;
                        }
                    }
                    
                    /* Custom CLSU theme colors for calendar - STATUS BASED */
                    .fc-daygrid-day-number {
                        font-weight: 600;
                        color: #333;
                    }
                    
                    /* Highlight dates with trips based on status */
                    .fc-daygrid-day.has-ready-to-go {
                        background-color: rgba(240, 173, 78, 0.15) !important;
                    }
                    
                    .fc-daygrid-day.has-on-going {
                        background-color: rgba(5, 98, 149, 0.15) !important;
                    }
                    
                    .fc-daygrid-day.has-completed {
                        background-color: rgba(0, 112, 60, 0.15) !important;
                    }
                    
                    /* Custom styling for completed trip event buttons */
                    .fc-event.completed-event {
                        background-color: #006400 !important;
                        border-color: #006400 !important;
                        color: #ffffff !important;
                    }
                    
                    /* Custom styling for pending (Ready to go) trip event buttons */
                    .fc-event.pending-event {
                        background-color: #FFF4E0 !important;
                        border-color: #F0AD4E !important;
                        color: #F0AD4E !important;
                    }
                    
                    /* Custom styling for on-going trip event buttons */
                    .fc-event.ongoing-event {
                        background-color: #0056b3 !important;
                        border-color: #0056b3 !important;
                        color: #ffffff !important;
                    }
                    
                    /* Custom styling for rejected/cancelled trip event buttons */
                    .fc-event.rejected-event {
                        background-color: #F8D7DA !important;
                        border-color: #DC3545 !important;
                        color: #DC3545 !important;
                    }
                </style>


                <!-- Schedule of Trips -->
                <div class="text-center my-4 sm:my-6 md:my-8">
                    <h3 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl xl:text-6xl font-extrabold text-[#004d00] mb-0 uppercase tracking-wider px-2">
                        SCHEDULE OF TRIPS
                    </h3>
            <div class="flex justify-center items-center gap-3 sm:gap-4 md:gap-5 lg:gap-6 flex-wrap px-2">
    <label for="monthYear" class="flex items-center h-12 sm:h-14 md:h-16 lg:h-20 text-base sm:text-lg md:text-xl lg:text-2xl xl:text-3xl font-bold text-[#004d00]">
        MONTH OF:
    </label>
  <input 
    type="month" 
    id="monthYear"
    class="h-7 px-2 border-2 border-[#004d00] rounded-md text-xs font-semibold text-[#004d00] bg-[#f2f9f2] cursor-pointer"
    value="{{ $currentMonth }}"
/>
</div>
                </div>
                                        
                <div id="driverCalendar" class="bg-white border-2 border-[#004d00] rounded-lg p-2 sm:p-3 shadow-md mx-auto w-full max-w-full overflow-x-auto"></div>
                
                <!-- FullCalendar Script -->
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                  const monthYearInput = document.getElementById('monthYear');
                  const calendarEl = document.getElementById('driverCalendar');
                  
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
                  
                  // Get initial container width
                  const getContainerWidth = function() {
                    return calendarEl.offsetWidth || 600;
                  };
                  
                  const calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    initialDate: initialDate,
                    height: 'auto',
                    contentHeight: function() {
                      const containerWidth = getContainerWidth();
                      const screenHeight = window.innerHeight;
                      
                      // Calculate optimal height based on screen size
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
                    dayMaxEvents: true,
                    titleFormat: { year: 'numeric', month: 'long' },
                    aspectRatio: function() {
                      const containerWidth = getContainerWidth();
                      return containerWidth < 600 ? 1.2 : 1.5;
                    },
                    
                    // Custom CLSU theme colors
                    eventBackgroundColor: '#004d00',
                    eventBorderColor: '#004d00',
                    eventTextColor: '#ffffff',
                    
                    // Add CSS classes to dates based on trip status
                    dayCellDidMount: function(arg) {
                      const date = arg.date.toISOString().split('T')[0];
                      
                      // Check if this date has any trips and apply appropriate class
                      // This will be populated when events are loaded
                      if (window.tripDates && window.tripDates[date]) {
                        const statuses = window.tripDates[date];
                        
                        // Priority: On-Going > Ready to go (Approved/Pending) > Completed
                        if (statuses.includes('On-going') || statuses.includes('Ongoing') || statuses.includes('On-Going')) {
                          arg.el.classList.add('has-on-going');
                        } else if (statuses.includes('Approved') || statuses.includes('Pending')) {
                          arg.el.classList.add('has-ready-to-go');
                        } else if (statuses.includes('Completed')) {
                          arg.el.classList.add('has-completed');
                        }
                      }
                    },
                    
                    events: function(fetchInfo, successCallback, failureCallback) {
                      const mv = monthYearInput.value || (new Date()).toISOString().slice(0,7);
                      const startStr = toYMD(fetchInfo.start);
                      const endStr = toYMD(fetchInfo.end);
                  
                      // Fetch trip tickets for the selected month - DRIVER SPECIFIC
                      fetch(`/driver/api/calendar-events?start=${startStr}&end=${endStr}`, {
                        headers: { 
                          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                          'Accept': 'application/json'
                        }
                      })
                      .then(res => {
                        if (!res.ok) {
                          throw new Error('Server responded with status ' + res.status);
                        }
                       return res.json();
                      })
                      .then(data => {
                        // Check if data is an array (valid response)
                        if (!Array.isArray(data)) {
                          console.error('Invalid response format:', data);
                          failureCallback(new Error('Invalid response from server'));
                        return;
                        }
                        
                        // Build a map of dates to statuses for day cell coloring
                        window.tripDates = {};
                        
                        // Transform data to FullCalendar format - create event for EACH day in range
                        let allEvents = [];
                        
                        data.forEach(ticket => {
                          // Determine color based on status - APPROVED=ORANGE, ON-GOING=BLUE
                          let bgColor = '#6c757d';      // Gray (default)
                          let borderColor = '#6c757d';
                          
                          console.log('Ticket Status:', ticket.status);
                          console.log('Ticket Number:', ticket.ticket_number);
                          console.log('Passengers data:', ticket.passengers);
                          console.log('Passengers type:', Array.isArray(ticket.passengers) ? 'ARRAY' : typeof ticket.passengers);
                          
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
                          
                          // Add this ticket's status to the date map for day cell coloring
                          const startDateMap = ticket.itinerary?.date_from || ticket.created_at;
                          const endDateMap = ticket.itinerary?.date_to || startDateMap;
                          
                          if (startDateMap && endDateMap) {
                            const startMap = new Date(startDateMap);
                            const endMap = new Date(endDateMap);
                            
                            for (let d = new Date(startMap); d <= endMap; d.setDate(d.getDate() +1)) {
                              const dateStr = d.toISOString().split('T')[0];
                              if (!window.tripDates[dateStr]) {
                                window.tripDates[dateStr] = [];
                              }
                              window.tripDates[dateStr].push(ticket.status);
                            }
                          }
                          
                          // Get start and end dates
                          const startDate = ticket.itinerary?.date_from || ticket.created_at;
                          const endDate = ticket.itinerary?.date_to || startDate;
                          
                          // Create base event template
                          const eventTemplate = {
                            id: ticket.id,
                            title: `${ticket.ticket_number || 'Trip'}`,
                            backgroundColor: bgColor,
                            borderColor: borderColor,
                            textColor: textColor,
                            extendedProps: {
                              driver_name: ticket.driver_name,
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
                          
                          // Parse dates and create events for each day in range (inclusive)
                          if (startDate && endDate) {
                            const start= new Date(startDate);
                            const end = new Date(endDate);
                            
                            // Loop through each day from start to end (inclusive)
                            for (let d = new Date(start); d <= end; d.setDate(d.getDate() +1)) {
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
                      const driverName = arg.event.title || 'No Driver';
                      return { 
                        html: `<div style="padding: 4px 6px; font-size: 11px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; border-radius: 3px; background: rgba(255,255,255,0.15);">${driverName}</div>` 
                      };
                    },
                  
                    eventClick: function(info) {
                      const event = info.event;
                      const props = event.extendedProps;
                      
                      // Build the modal content HTML dynamically - EXACTLY LIKE TRIP TICKETS PAGE
                      let contentHtml = `
<div class="text-xs text-gray-800 sm:text-sm">
    <!-- Trip Information Section -->
    <div class="mb-2 sm:mb-3">
        <h4 class="font-bold text-gray-900 border-b pb-1 mb-1.5 text-xs sm:text-sm">Trip Information</h4>
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
                            ? `${props.plate_number} - ${props.vehicle_model}`
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
        <h4 class="font-bold text-gray-900 border-b pb-1 mb-1.5 text-xs sm:text-sm">Employee Information</h4>
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
                            let passengersArray = [];
                            
                            // Handle both array and pipe-separated string
                            if (Array.isArray(props.passengers)) {
                                passengersArray = props.passengers;
                            } else if (typeof props.passengers === 'string') {
                                // Split pipe-separated string into array
                                passengersArray = props.passengers.split('|').filter(p => p && p.trim() !== '');
                            }
                            
                            const hasPassengers = passengersArray.length > 0;
                            
                            if (!hasPassengers) {
                                return '<span class="text-gray-500 italic">No additional passengers</span>';
                            }
                            
                            // Filter out empty values
                            const validPassengers = passengersArray.filter(p => p && p.trim() !== '');
                            const totalPassengers = validPassengers.length;
                            
                            // Show first passenger + "+X more" button if there are more
                            if (totalPassengers > 0) {
                                const firstPassenger = validPassengers[0];
                                const remainingCount = totalPassengers - 1;
                                
                                if (remainingCount > 0) {
                                    const allPassengersStr = validPassengers.join('|');
                                    const ticketNum = (event.title || 'Trip').replace(/'/g, "\\'");
                                    return `<span>${firstPassenger}</span><button type="button" onclick="viewPassengersModalFromDashboard('${allPassengersStr}', '${ticketNum}')" class="ml-1 text-blue-600 hover:text-blue-800 font-medium cursor-pointer">+${remainingCount} more</button>`;
                                } else {
                                    return `<span>${firstPassenger}</span>`;
                                }
                            }
                            
                            return '<span class="text-gray-500 italic">No additional passengers</span>';
                        })()}
                    </td>
                </tr>

                <!-- Status/Remarks -->
                <tr class="border-t pt-2 mt-1">
                    <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">
                        ${['Pending', 'Approved'].includes(props.status) ? 'Remarks:' : 'Status:'}
                    </td>
                    <td class="py-0.5 align-top">
                        ${(() => {
                            // Handle Pending and Approved statuses with "Ready to go" or custom remarks
                            if (props.status === 'Pending') {
                                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold" style="background:#FFF4E0; color:#F0AD4E; border:1px solid #F0AD4E;">Pending</span>';
                            }
                            
                            if (props.status === 'Approved') {
                                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold" style="background:#FFF4E0; color:#F0AD4E; border:1px solid #F0AD4E;">Ready to go</span>';
                            }
                            
                            // Status badge colors matching dashboard cards
                            const statusColors = {
                                'On-going': { bg: '#E3F0F7', text: '#056295', border: '#056295' },
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
                  
                  // Watch for sidebar changes using MutationObserver
                  const sidebar = document.getElementById('sidebar');
                  if (sidebar) {
                    const observer = new MutationObserver(function(mutations) {
                      mutations.forEach(function(mutation) {
                        if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                          // Sidebar class changed - recalculate after transition
                          setTimeout(function() {
                            calendar.updateSize();
                            applyScrollable();
                          }, 350);
                        }
                      });
                    });
                    
                    observer.observe(sidebar, {
                      attributes: true,
                      attributeFilter: ['class']
                    });
                  }
                  
                  // Handle window resize - recalculate calendar dimensions
                  let resizeTimeout;
                  window.addEventListener('resize', function() {
                    clearTimeout(resizeTimeout);
                    resizeTimeout = setTimeout(function() {
                      calendar.updateSize();
                      applyScrollable();
                    }, 200);
                  });
                  
                  // Listen for sidebar toggle event
                  window.addEventListener('sidebar-toggled', function(e) {
                    // Wait for transition to complete, then update calendar size
                    setTimeout(function() {
                      calendar.updateSize();
                      applyScrollable();
                    }, 100);
                  });
                  
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
                  
                  // Handle month change
                  monthYearInput.addEventListener('change', function() {
                    const [y,m] = this.value.split('-').map(Number);
                    calendar.gotoDate(new Date(y, m - 1, 1));
                    calendar.refetchEvents();
                    setTimeout(applyScrollable, 500);
                  });
                });
                </script>
                
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
                
                <script>
                    // Modal close functionality
                    const tripModal = document.getElementById('trip-detail-modal');
                    const closeModalBtn = document.getElementById('close-modal');
                    const modalCloseBtn = document.getElementById('modal-close-btn');
                    
                    function closeTripModal() {
                        const modalContent = document.getElementById('modalContent');
                        
                        // Animate out
                        modalContent.classList.remove('scale-100', 'opacity-100');
                        modalContent.classList.add('scale-95', 'opacity-0');
                        
                        // Wait for animation to complete before hiding
                        setTimeout(() => {
                            tripModal.classList.add('hidden');
                            document.body.style.overflow = '';
                            document.body.classList.remove('modal-open');
                        }, 200); // Match this with transition duration
                    }
                    
                    if (closeModalBtn) {
                        closeModalBtn.addEventListener('click', closeTripModal);
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
                    
                    // Passengers modal functions
                    function viewPassengersModalFromDashboard(passengersStr, ticketNumber) {
                        // Convert pipe-separated string to array
                        const passengers = passengersStr.split('|').filter(p => p && p.trim() !== '');
                        viewPassengersModalArray(passengers, ticketNumber);
                    }
                    
                    function viewPassengersModalArray(passengers, ticketNumber) {
                        // Create modal dynamically
                        let modalHtml = `
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
                        `;
                        
                        // Append modal to body
                        document.body.insertAdjacentHTML('beforeend', modalHtml);
                        
                        // Build passengers list HTML
                        const modalBody = document.getElementById('passengersModalBody');
                        let passengersHtml = `
                            <div class="space-y-2">
                                <p class="text-sm text-gray-600 mb-3">
                                    <span class="font-semibold">Trip Ticket:</span> ${ticketNumber}
                                </p>
                                <p class="text-sm text-gray-600 mb-3">
                                    <span class="font-semibold">Total Passengers:</span> ${passengers.length}
                                </p>
                                <div class="border-t border-gray-200 pt-3">
                                    <h4 class="font-semibold text-gray-800 mb-2 text-sm">Complete List:</h4>
                                    <ul class="space-y-1.5">
                        `;
                        
                        passengers.forEach((passenger, index) => {
                            passengersHtml += `
                                <li class="flex items-start gap-2 text-sm text-gray-700">
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
                        const modal = document.getElementById('passengersModal');
                        const modalContent = document.getElementById('passengersModalContent');
                        
                        modal.classList.remove('hidden');
                        void modal.offsetWidth;
                        modalContent.classList.remove('scale-95', 'opacity-0');
                        modalContent.classList.add('scale-100', 'opacity-100');
                        document.body.style.overflow = 'hidden';
                    }
                    
                    function closePassengersModal() {
                        const modal = document.getElementById('passengersModal');
                        const modalContent = document.getElementById('passengersModalContent');
                        
                        modalContent.classList.remove('scale-100', 'opacity-100');
                        modalContent.classList.add('scale-95', 'opacity-0');
                        
                        setTimeout(() => {
                            modal.remove();
                            document.body.style.overflow = '';
                        }, 200);
                    }
                </script>

                <!-- Quick Actions -->
                <div class="mt-8 sm:mt-10 md:mt-12"></div>
                <h2 class="text-base sm:text-lg font-semibold text-[#1e6031] mb-3 sm:mb-4 px-3">Quick Actions</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6 px-3">
                    <!-- Trip Tickets -->
                    <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-xl border border-indigo-200 p-3 transition-all duration-200 hover:shadow-md">
                        <div class="flex items-start">
                            <div class="rounded-lg bg-indigo-600 p-2 flex-shrink-0">
                                <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-3 flex-1 min-w-0">
                                <h3 class="text-sm sm:text-base font-semibold text-gray-800 truncate">Trip Tickets</h3>
                                <p class="text-gray-600 text-xs sm:text-sm mt-1 mb-2 sm:mb-3 leading-relaxed">Manage your trip tickets and view travel details.</p>
                                <a href="{{ route('driver.trip-tickets') }}" class="inline-flex items-center px-2.5 py-1.5 sm:px-3 sm:py-1.5 bg-white border border-indigo-300 text-indigo-800 rounded-lg hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 text-xs sm:text-sm font-medium transition duration-200 shadow-sm no-underline">
                                    View Tickets
                                    <svg class="ml-1 h-3 w-3 sm:h-4 sm:w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            @else
                <!-- No Driver Record Section -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">Driver Account Not Found</h3>
                        <p class="mt-2 text-base text-gray-500">
                            Your user account is not linked to a driver record in the system.
                        </p>
                        <div class="mt-6">
                            <p class="text-sm text-gray-500">
                                Please contact your system administrator to set up your driver account.
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    
    <script>
        function toggleDriverDropdown() {
            const dropdown = document.getElementById('driverAccountDropdown');
            dropdown.classList.toggle('hidden');
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('driverAccountDropdown');
            const button = event.target.closest('.account-menu');
            
            if (!button && !dropdown.classList.contains('hidden')) {
                dropdown.classList.add('hidden');
            }
        });
    </script>

    @include('drivers.partials.auto-trip-tracking')
@endsection