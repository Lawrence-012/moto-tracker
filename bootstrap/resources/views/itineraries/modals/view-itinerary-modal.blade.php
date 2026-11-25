<!-- View Itinerary Modal -->
<div id="viewItineraryModal" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4 transition-all duration-200">
  <div class="bg-white rounded-lg shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto border-t-4 border-[#1e6031] transform transition-all duration-200 scale-95 opacity-0" id="viewItineraryModalContent">
    
    <!-- Modal Header - DSU Themed -->
    <div class="px-6 py-3 border-b border-gray-200 flex items-center justify-between" style="background: linear-gradient(135deg, #1e6031 0%, #2d7a3e 50%, #1e6031 100%);">
      <div class="flex items-center gap-3">
        <!-- Icon Circle -->
        <div class="w-10 h-10 rounded-full bg-white bg-opacity-20 flex items-center justify-center">
          <!-- Document Icon -->
          <svg class="h-6 w-6 text-[#1e6031]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
        </div>
        <h3 class="text-lg font-bold text-white">Itinerary Details</h3>
      </div>
  
    </div>

    <!-- Content -->
    <div class="px-6 py-3">
      <div class="text-sm text-gray-800">
        <!-- Driver Information Section -->
        <div class="mb-3">
          <h4 class="font-bold text-gray-900 border-b pb-1.5 mb-2 text-base">Driver Information</h4>
          <table class="w-full">
            <tbody>
              <tr>
                <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Driver:</td>
                <td class="text-gray-900 py-0.5 align-top" id="view_driver"></td>
              </tr>
              <tr>
                <td class="font-semibold text-gray-700 py-0.5 align-top">Contact Number:</td>
                <td class="text-gray-900 py-0.5 align-top" id="view_contact_number"></td>
              </tr>
              <tr>
                <td class="font-semibold text-gray-700 py-0.5 align-top">Vehicle:</td>
                <td class="text-gray-900 py-0.5 align-top" id="view_vehicle"></td>
              </tr>
              <tr>
                <td class="font-semibold text-gray-700 py-0.5 align-top">Departure Date:</td>
                <td class="text-gray-900 py-0.5 align-top" id="view_date"></td>
              </tr>
              <tr>
                <td class="font-semibold text-gray-700 py-0.5 align-top">Departure Time:</td>
                <td class="text-gray-900 py-0.5 align-top" id="view_departure_time"></td>
              </tr>
              <tr>
                <td class="font-semibold text-gray-700 py-0.5 align-top">Purpose:</td>
                <td class="text-gray-900 py-0.5 align-top whitespace-pre-wrap" id="view_purpose"></td>
              </tr>
              <tr>
                <td class="font-semibold text-gray-700 py-0.5 align-top">Destination:</td>
                <td class="text-gray-900 py-0.5 align-top whitespace-pre-wrap" id="view_destination"></td>
              </tr>
              <tr>
                <td class="font-semibold text-gray-700 py-0.5 align-top">Status:</td>
                <td class="text-gray-900 py-0.5 align-top">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800" id="view_status"></span>
                </td>
              </tr>
              <tr id="rejected-by-row" style="display: none;">
                <td class="font-semibold text-gray-700 py-0.5 align-top">Rejected By:</td>
                <td class="text-gray-900 py-0.5 align-top">
                  <span class="text-sm text-gray-900 font-medium" id="view_rejected_by"></span>
                </td>
              </tr>
              <tr id="rejection-reason-row" style="display: none;">
                <td class="font-semibold text-gray-700 py-0.5 align-top">Rejection Reason:</td>
                <td class="text-gray-900 py-0.5 align-top">
                  <span class="text-sm text-gray-700" id="view_rejection_reason"></span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Employee Information Section -->
        <div class="mb-3">
          <h4 class="font-bold text-gray-900 border-b pb-1.5 mb-2 text-base">Employee Information</h4>
          <table class="w-full">
            <tbody>
              <tr>
                <td class="font-semibold text-gray-700 py-0.5 align-top w-1/3">Head of the Party:</td>
                <td class="text-gray-900 py-0.5 align-top" id="view_employee_name"></td>
              </tr>
              <tr>
                <td class="font-semibold text-gray-700 py-0.5 align-top">Contact Number:</td>
                <td class="text-gray-900 py-0.5 align-top" id="view_employee_contact"></td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Travel Orders Section -->
        <div>
          <h4 class="font-bold text-gray-900 border-b pb-1.5 mb-2 text-base">Travel Order(s)</h4>
          <div id="view_travel_orders_list" class="space-y-2"></div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="px-6 py-3 border-t border-gray-200 flex justify-end bg-gradient-to-r from-gray-50 to-white gap-2">
      <button type="button" class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-all duration-200 text-xs font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5" onclick="viewItineraryExcel()">
        <i class="fas fa-file-excel mr-1"></i> View Excel
      </button>
      <button type="button" class="px-4 py-1.5 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-all duration-200 text-xs font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5" onclick="closeItineraryModal()">
        Close
      </button>
    </div>
  </div>
</div>

<script>
// Function to populate and show the view itinerary modal
window.showViewItineraryModal = function(itineraryData) {
    // Store the itinerary ID for PDF generation
    window.currentViewItineraryId = itineraryData.id || '';
    
    // Display all travel orders in table format with +X more functionality
    const travelOrdersList = document.getElementById('view_travel_orders_list');
    const travelOrders = itineraryData.travel_orders || [];
    
    if (travelOrders.length > 0) {
        // Use the first travel order's employee for the employee section
        const firstEmployee = travelOrders[0].employee || {};
        document.getElementById('view_employee_name').textContent = firstEmployee.full_name || 'N/A';
        document.getElementById('view_employee_contact').textContent = firstEmployee.contact_num || '-';
        
        // Build table HTML
        let travelOrdersHtml = `
            <div class="overflow-hidden rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-1.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">No.</th>
                            <th class="px-3 py-1.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Employee</th>
                            <th class="px-3 py-1.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Contact</th>
                            <th class="px-3 py-1.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Purpose</th>
                            <th class="px-3 py-1.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Destination</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-150">
        `;
        
        // Show first travel order, hide others with "more" button
        const maxVisible = 1;
        const hiddenCount = travelOrders.length - maxVisible;
        
        travelOrders.forEach((to, index) => {
            const employee = to.employee || {};
            const isHidden = index >= maxVisible;
            travelOrdersHtml += `
                <tr class="${isHidden ? 'hidden' : ''} travel-order-row hover:bg-gray-50">
                    <td class="px-3 py-1.5 text-xs text-gray-800 font-medium">${index + 1}</td>
                    <td class="px-3 py-1.5 text-xs text-gray-800">${employee.full_name || 'N/A'}</td>
                    <td class="px-3 py-1.5 text-xs text-gray-800">${employee.contact_num || '-'}</td>
                    <td class="px-3 py-1.5 text-xs text-gray-800">${to.purpose || 'N/A'}</td>
                    <td class="px-3 py-1.5 text-xs text-gray-800">${to.destination || 'N/A'}</td>
                </tr>
            `;
        });
        
        travelOrdersHtml += `
                    </tbody>
                </table>
            </div>
        `;
        
        // Add "View X more" button if there are hidden travel orders
        if (hiddenCount > 0) {
            travelOrdersHtml += `
                <div class="mt-2 text-center">
                    <button type="button" onclick="toggleAllTravelOrders(this)" class="text-sm text-[#1e6031] hover:text-[#2d7a3e] font-medium transition-colors duration-200">
                        <i class="fas fa-chevron-down mr-1"></i> View ${hiddenCount} more travel order${hiddenCount > 1 ? 's' : ''}
                    </button>
                </div>
            `;
        }
        
        travelOrdersList.innerHTML = travelOrdersHtml;
    } else if (itineraryData.travel_order || itineraryData.travelOrder) {
        // Legacy: single travel order
        const travelOrder = itineraryData.travel_order || itineraryData.travelOrder;
        if (travelOrder && travelOrder.employee) {
            const employee = travelOrder.employee;
            document.getElementById('view_employee_name').textContent = employee.full_name || 'N/A';
            document.getElementById('view_employee_contact').textContent = employee.contact_num || '-';
            
            // Show single travel order in table format
            travelOrdersList.innerHTML = `
                <div class="overflow-hidden rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-1.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">No.</th>
                                <th class="px-3 py-1.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Employee</th>
                                <th class="px-3 py-1.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Contact</th>
                                <th class="px-3 py-1.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Purpose</th>
                                <th class="px-3 py-1.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Destination</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-150">
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-1.5 text-xs text-gray-800 font-medium">1</td>
                                <td class="px-3 py-1.5 text-xs text-gray-800">${employee.full_name || 'N/A'}</td>
                                <td class="px-3 py-1.5 text-xs text-gray-800">${employee.contact_num || '-'}</td>
                                <td class="px-3 py-1.5 text-xs text-gray-800">${travelOrder.purpose || 'N/A'}</td>
                                <td class="px-3 py-1.5 text-xs text-gray-800">${travelOrder.destination || 'N/A'}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            `;
        } else {
            document.getElementById('view_employee_name').textContent = 'N/A';
            document.getElementById('view_employee_contact').textContent = '-';
            travelOrdersList.innerHTML = '<div class="text-gray-500 italic text-sm">No travel order information available</div>';
        }
    } else if (itineraryData.travel_order_id) {
        // If travel order exists but not loaded, use fallback
        console.log('Travel order ID exists but employee data not loaded:', itineraryData.travel_order_id);
        document.getElementById('view_employee_name').textContent = 'N/A';
        document.getElementById('view_employee_contact').textContent = '-';
        travelOrdersList.innerHTML = '<div class="text-gray-500 italic text-sm">Travel Order #' + itineraryData.travel_order_id + '</div>';
    } else {
        document.getElementById('view_employee_name').textContent = 'N/A';
        document.getElementById('view_employee_contact').textContent = '-';
        travelOrdersList.innerHTML = '<div class="text-gray-500 italic text-sm">No travel order information available</div>';
    }
    
    // Format driver name
    document.getElementById('view_driver').textContent = itineraryData.driver ? 
        `${itineraryData.driver.first_name || ''} ${itineraryData.driver.last_name || ''}`.trim() || 'N/A' : 'N/A';
    
    // Format contact number - try driver's contact_num, then user's contact_num, or '-'
    let contactNumber = '-';
    if (itineraryData.driver) {
        contactNumber = itineraryData.driver.contact_num || (itineraryData.driver.user?.contact_num || '-');
    }
    document.getElementById('view_contact_number').textContent = contactNumber;
    
    // Format vehicle
    document.getElementById('view_vehicle').textContent = itineraryData.vehicle ? 
        `${itineraryData.vehicle.plate_number || ''} - ${itineraryData.vehicle.model || ''}`.trim().replace(/-\s*$/, '') || 'N/A' : 'N/A';
    
    // Format purpose and destination
    document.getElementById('view_purpose').textContent = itineraryData.purpose || '';
    document.getElementById('view_destination').textContent = itineraryData.destination || '';
    
    // Format departure time
    document.getElementById('view_departure_time').textContent = itineraryData.departure_time ? formatTime(itineraryData.departure_time) : '';
    
    // Format status with badge - Dynamic based on approval workflow
    const itinerary = itineraryData;
    const statusElement = document.getElementById('view_status');
    
    let statusText = '';
    let statusClass = 'bg-green-100 text-green-800';
    
    // Check current URL to determine context
    const urlPath = window.location.pathname;
    const urlParams = new URLSearchParams(window.location.search);
    const currentTab = urlParams.get('tab');
    const isInApprovalPage = urlPath.includes('/itinerary/approvals/') || urlPath.includes('/motorpool/approvals/itineraries/');
    const isInApprovedTab = isInApprovalPage && currentTab === 'approved';
    const isInPendingTab = isInApprovalPage && (currentTab === 'pending' || !currentTab);
    
    // Check approval status in order
    if (itinerary.status === 'Cancelled') {
        // Cancelled/Rejected
        statusText = 'Cancelled';
        statusClass = 'bg-red-100 text-red-800';
    } else if (isInApprovedTab) {
        // Viewing from approved tab - always show "Approved"
        statusText = 'Approved';
        statusClass = 'bg-green-100 text-green-800';
    } else if (itinerary.vp_approved || itinerary.status === 'Approved') {
        // Both approved or status is Approved
        statusText = 'Approved';
        statusClass = 'bg-green-100 text-green-800';
    } else if (isInPendingTab && !itinerary.unit_head_approved) {
        // In pending tab and unit head hasn't approved yet - show "Pending"
        statusText = 'Pending';
        statusClass = 'bg-yellow-100 text-yellow-800';
    } else if (isInPendingTab && itinerary.unit_head_approved && !itinerary.vp_approved) {
        // In pending tab, unit head approved but VP hasn't yet - show "Pending"
        statusText = 'Pending';
        statusClass = 'bg-yellow-100 text-yellow-800';
    } else if (itinerary.unit_head_approved && !itinerary.vp_approved) {
        // Unit head approved, waiting for VP (not in pending tab)
        statusText = 'Waiting for VPAD Approval';
        statusClass = 'bg-yellow-100 text-yellow-800';
    } else if (!itinerary.unit_head_approved) {
        // Unit head hasn't approved yet (not in pending tab)
        statusText = 'Waiting for Unit Head Approval';
        statusClass = 'bg-yellow-100 text-yellow-800';
    } else {
        // Fallback to actual status
        statusText = itinerary.status || 'Pending';
        if (statusText.toLowerCase() === 'approved' || statusText.toLowerCase() === 'completed') {
            statusClass = 'bg-green-100 text-green-800';
        } else if (statusText.toLowerCase() === 'pending') {
            statusClass = 'bg-yellow-100 text-yellow-800';
        } else if (statusText.toLowerCase() === 'cancelled') {
            statusClass = 'bg-red-100 text-red-800';
        }
    }
    
    statusElement.textContent = statusText;
    statusElement.className = `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusClass}`;
    
    // Show/hide rejection reason and rejected by rows
    const rejectionReasonRow = document.getElementById('rejection-reason-row');
    const rejectedByRow = document.getElementById('rejected-by-row');
    
    if (itinerary.status === 'Cancelled' || itinerary.rejection_reason) {
        // Show rejection details
        rejectionReasonRow.style.display = '';
        rejectedByRow.style.display = '';
        
        document.getElementById('view_rejection_reason').textContent = itinerary.rejection_reason || 'No reason provided';
        
        if (itinerary.rejected_by) {
            document.getElementById('view_rejected_by').textContent = itinerary.rejected_by.full_name || 'Unknown';
        } else {
            document.getElementById('view_rejected_by').textContent = 'Unknown';
        }
    } else {
        // Hide rejection details
        rejectionReasonRow.style.display = 'none';
        rejectedByRow.style.display = 'none';
    }
    
    // Smart date formatting
    const dateFrom = itineraryData.date_from ? new Date(itineraryData.date_from) : null;
    const dateTo = itineraryData.date_to ? new Date(itineraryData.date_to) : null;
    
    let formattedDate = '';
    
    if (dateFrom && dateTo) {
        const fromMonth = dateFrom.toLocaleString('en-US', { month: 'long' });
        const fromDay = dateFrom.getDate();
        const fromYear = dateFrom.getFullYear();
        
        const toMonth = dateTo.toLocaleString('en-US', { month: 'long' });
        const toDay = dateTo.getDate();
        const toYear = dateTo.getFullYear();
        
        if (dateFrom.getTime() === dateTo.getTime()) {
            // Same date: January 16, 2026
            formattedDate = `${fromMonth} ${fromDay}, ${fromYear}`;
        } else if (fromMonth === toMonth && fromYear === toYear) {
            // Same month: January 20-21, 2026
            formattedDate = `${fromMonth} ${fromDay}-${toDay}, ${fromYear}`;
        } else if (fromYear === toYear) {
            // Same year: January 31 - February 1, 2026
            formattedDate = `${fromMonth} ${fromDay} - ${toMonth} ${toDay}, ${fromYear}`;
        } else {
            // Different years: December 31, 2026 - January 1, 2027
            formattedDate = `${fromMonth} ${fromDay}, ${fromYear} - ${toMonth} ${toDay}, ${toYear}`;
        }
    } else if (dateFrom) {
        // Only date_from available
        const fromMonth = dateFrom.toLocaleString('en-US', { month: 'long' });
        const fromDay = dateFrom.getDate();
        const fromYear = dateFrom.getFullYear();
        formattedDate = `${fromMonth} ${fromDay}, ${fromYear}`;
    }
    
    document.getElementById('view_date').textContent = formattedDate;
    
    // Show the modal with animation
    const modal = document.getElementById('viewItineraryModal');
    const modalContent = document.getElementById('viewItineraryModalContent');
    
    modal.classList.remove('hidden');
    
    // Trigger reflow
    void modal.offsetWidth;
    
    // Animate in
    modalContent.classList.remove('scale-95', 'opacity-0');
    modalContent.classList.add('scale-100', 'opacity-100');
    
    // Prevent body scroll
    document.body.style.overflow = 'hidden';
};

// Function to close the modal
function closeItineraryModal() {
    const modal = document.getElementById('viewItineraryModal');
    const modalContent = document.getElementById('viewItineraryModalContent');
    
    // Animate out
    modalContent.classList.remove('scale-100', 'opacity-100');
    modalContent.classList.add('scale-95', 'opacity-0');
    
    // Wait for animation to complete before hiding
    setTimeout(() => {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }, 200);
}

// Function to view itinerary Excel
function viewItineraryExcel() {
    // Get the itinerary ID from the modal's data attribute or use a global variable
    const itineraryId = window.currentViewItineraryId;
    
    if (!itineraryId) {
        alert('No itinerary selected.');
        return;
    }
    
    // Show confirmation dialog WITHOUT closing the modal first
    Swal.fire({
        title: 'Download Excel File?',
        text: 'You are about to download the Excel file. Do you want to proceed?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#16a34a',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, proceed!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // User confirmed - close modal and open Excel in new tab
            closeItineraryModal();
            setTimeout(() => {
                window.open(`/itinerary/${itineraryId}/excel`, '_blank');
            }, 200);
        }
        // If cancelled, do nothing - modal stays open
    });
}

// Helper function to format time
function formatTime(timeString) {
    if (!timeString) return '';
    
    const [hours, minutes] = timeString.split(':');
    let hour = parseInt(hours);
    const minute = minutes;
    const ampm = hour >= 12 ? 'PM' : 'AM';
    
    hour = hour % 12;
    hour = hour ? hour : 12; // Convert 0 to 12
    
    return `${hour}:${minute} ${ampm}`;
}

// Toggle function to show/hide additional travel orders
window.toggleAllTravelOrders = function(button) {
    const container = button.closest('#view_travel_orders_list');
    const allRows = container.querySelectorAll('.travel-order-row');
    const isExpanded = button.getAttribute('data-expanded') === 'true';
    const totalOrders = allRows.length;
    const maxVisibleInitially = 1;
    const hiddenCount = totalOrders - maxVisibleInitially;
    
    if (isExpanded) {
        // Collapse back - hide all except the first row
        allRows.forEach((row, index) => {
            if (index >= maxVisibleInitially) {
                row.classList.add('hidden');
            }
        });
        
        button.innerHTML = `<i class="fas fa-chevron-down mr-1"></i> View ${hiddenCount} more travel order${hiddenCount > 1 ? 's' : ''}`;
        button.setAttribute('data-expanded', 'false');
    } else {
        // Expand - show all rows
        allRows.forEach(row => {
            row.classList.remove('hidden');
        });
        
        button.innerHTML = '<i class="fas fa-chevron-up mr-1"></i> Show less';
        button.setAttribute('data-expanded', 'true');
    }
};

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('viewItineraryModal');
    const modalContent = document.getElementById('viewItineraryModalContent');
    if (event.target === modal && !modalContent.contains(event.target)) {
        closeItineraryModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeItineraryModal();
    }
});
</script>