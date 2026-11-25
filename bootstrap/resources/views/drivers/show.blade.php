@extends('layouts.motorpool-admin')

@section('content')
    <!-- Back Button Row -->
    <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center">
            <a href="{{ route('drivers.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-[#1e6031] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#164f2a] focus:bg-[#164f2a] active:bg-[#103c1e] focus:outline-none focus:ring-2 focus:ring-[#1e6031] focus:ring-offset-2 transition ease-in-out duration-150">
                <i class="fas fa-arrow-left mr-2"></i> Back
            </a>
        </div>
    </div>
    

    
    <!-- Driver Information -->
    <div class="max-w-7xl mx-auto py-2 px-4 sm:px-6 lg:px-8">
        <div class="card w-100 shadow-sm border-[#1e6031]" style="border-radius: 12px;">
            <div class="card-header d-flex align-items-center" style="background-color: #1e6031; color: white; font-weight: 600; border-radius: 12px 12px 0 0;">
                <i class="fas fa-user me-2"></i>
                <h5 class="mb-0">Driver Information</h5>
            </div>
            <div class="card-body p-3" style="font-size: 0.9rem; line-height: 1.5;">
                <div class="row">
                    <div class="col-6">
                        <div class="mb-2">
                            <strong>Name:</strong> {{ $driver->full_name2 }}
                        </div>
                        <div class="mb-2">
                            <strong>Email:</strong> {{ $driver->user->email ?? 'N/A' }}
                        </div>
                        <div class="mb-2">
                            <strong>Contact Number:</strong> {{ $driver->user->contact_num ?? 'N/A' }}
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-2">
                            <strong>Address:</strong> {{ $driver->address }}
                        </div>
                        <div class="mb-2">
                            <strong>Position:</strong> {{ $driver->position }}
                        </div>
                        <div class="mb-2">
                            <strong>Official Station:</strong> {{ $driver->official_station }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Completed Trips -->
    <div class="max-w-7xl mx-auto py-2 px-4 sm:px-6 lg:px-8 mt-4">
        <!-- Header with title, search, and PDF button -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="text-lg font-semibold mb-0" style="color: #1e6031;">
                {{ $driver->full_name2 }} - Travel History
            </h3>
            <div class="d-flex align-items-center gap-2">
                <!-- Search Bar -->
                <div class="d-flex">
                    <input type="text" id="tripSearch" placeholder="Search history..." 
                           style="height: 32px; width: 200px; font-size: 0.85rem; border: 1px solid #1e6031; border-radius: 0.375rem 0 0 0.375rem; padding: 0 12px;">
                    <button type="button" id="searchButton"
                            style="background-color: #1e6031; color: #ffffff; border: 1px solid #1e6031; height: 32px; width: 40px; border-radius: 0 0.375rem 0.375rem 0; cursor:pointer;">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            
                <!-- Generate PDF Button -->
                <button type="button" id="generateDriverTripsPDFBtn" class="btn btn-sm d-inline-flex align-items-center justify-content-center" style="background-color: #dc3545; border: 1px solid #dc3545; border-radius: 0.375rem; color: white; height: 32px; padding: 0 12px; font-size: 0.80rem;">
                    <i class="fas fa-file-pdf me-1" style="font-size: 0.8rem;"></i> Generate PDF
                </button>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200" id="tripsTable">
                <thead style="background-color: #1e6031; color: white;">
                    <tr>
                        <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">No.</th>
                        <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Vehicle</th>
                        
                        <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Head of Party</th>
                        <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Destination</th>
                        <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Departure Time</th>
                        <th style="padding: 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;">Distance (km)</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @if($travelHistory->count() > 0)
                        @foreach($travelHistory as $index => $history)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-2 whitespace-nowrap text-xs text-gray-900">{{ $travelHistory->firstItem() + $index }}</td>
                            <td class="px-6 py-2 whitespace-nowrap">
                                @if($history->vehicle)
                                    <div class="text-xs text-gray-900 font-medium">{{ $history->vehicle->model }}</div>
                                    <div class="text-xs text-gray-500">Plate Number: {{ $history->vehicle->plate_number }}</div>
                                @else
                                    <span class="text-gray-400 text-xs">No vehicle assigned</span>
                                @endif
                            </td>
                            <td class="px-6 py-2 text-xs text-gray-900">{{ $history->head_of_party ?? 'N/A' }}</td>
                            <td class="px-6 py-2 text-xs text-gray-900">{{ $history->destination ?? 'N/A' }}</td>
                            <td class="px-6 py-2 whitespace-nowrap">
                                <div class="text-xs text-gray-900">
                                    {{ $history->departure_date?->format('M d, Y') ?? 'N/A' }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $history->departure_time ? \Carbon\Carbon::parse($history->departure_time)->format('h:i A') : 'N/A' }}
                                </div>
                            </td>
                            <td class="px-6 py-2 whitespace-nowrap text-xs text-gray-900">
                                {{ $history->distance_km ? number_format($history->distance_km) : 'N/A' }}
                            </td>
                        </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="fas fa-route text-gray-300 text-3xl mb-2"></i>
                                    <h4 class="text-base font-medium text-gray-900 mb-1">No Completed Travel History</h4>
                                    <p class="text-gray-500 text-sm">This driver has no completed trips yet.</p>
                                </div>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        @if($travelHistory->count() > 0)
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted" style="font-size: 0.875rem;">
                    Showing {{ $travelHistory->firstItem() }} to {{ $travelHistory->lastItem() }} of {{ $travelHistory->total() }} records
                </div>
                <div class="d-flex align-items-center gap-2">
                    <nav>
                        <ul class="pagination mb-0">
                            <li class="page-item {{ $travelHistory->currentPage() <= 1 ? 'disabled' : '' }}">
                                <a class="page-link {{ $travelHistory->currentPage() <= 1 ? 'disabled-link' : '' }}" 
                                   href="{{ $travelHistory->previousPageUrl() }}" 
                                   {{ $travelHistory->currentPage() <= 1 ? 'aria-disabled="true"' : '' }}>
                                    Prev
                                </a>
                            </li>
                            <li class="page-item active">
                                <span class="page-link">{{ $travelHistory->currentPage() }}</span>
                            </li>
                            <li class="page-item {{ $travelHistory->currentPage() >= $travelHistory->lastPage() ? 'disabled' : '' }}">
                                <a class="page-link {{ $travelHistory->currentPage() >= $travelHistory->lastPage() ? 'disabled-link' : '' }}" 
                                   href="{{ $travelHistory->nextPageUrl() }}" 
                                   {{ $travelHistory->currentPage() >= $travelHistory->lastPage() ? 'aria-disabled="true"' : '' }}>
                                    Next
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        @endif
    </div>
    
<!-- JS Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
@endsection

<!-- Hidden data container for PDF generation -->
<div id="js-driver-travel-history-data" 
     data-driver="{{ json_encode(['id' => $driver->id, 'full_name2' => $driver->full_name2], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}" 
     data-travel-history="{{ json_encode($travelHistory->items(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}"></div>

<style>
/* Pagination styling to match travel-orders */
.pagination {
    display: flex;
    justify-content: flex-end;
    list-style: none;
}

.pagination .page-link {
    color: #1e6031;
    border-color: #1e6031;
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    display: block;
    text-decoration: none;
    background-color: #fff;
    border: 1px solid #1e6031;
    border-radius: 0.25rem;
}

.pagination .page-link:hover {
    background-color: #1e6031;
    border-color: #1e6031;
    color: white;
}

.pagination .active .page-link {
    background-color: #1e6031;
    border-color: #1e6031;
    color: white;
}

.page-item:first-child .page-link,
.page-item:last-child .page-link {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background-color: #1e6031;
    border-color: #1e6031;
    color: white;
    border-radius: 0.25rem;
}

.page-item:first-child .page-link:hover,
.page-item:last-child .page-link:hover {
    background-color: #164f2a;
    border-color: #164f2a;
}

.page-item.disabled .page-link {
    background-color: #e9ecef;
    border-color: #dee2e6;
    color: #6c757d;
}

.page-item {
    margin: 0 2px;
}

.page-item.active .page-link {
    background-color: #1e6031;
    border-color: #1e6031;
    color: white;
}

.page-item.disabled .page-link {
    background-color: #e9ecef;
    border-color: #dee2e6;
    color: #6c757d;
    cursor: not-allowed;
}

.disabled-link {
    color: #6c757d !important;
    pointer-events: none;
    cursor: not-allowed;
}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('tripSearch');
    const searchButton = document.getElementById('searchButton');
    const table = document.getElementById('tripsTable');
    
    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            // Skip the "No data" row
            if (row.querySelector('td[colspan="6"]')) return;
            
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    if (searchInput && searchButton) {
        searchButton.addEventListener('click', filterTable);
        searchInput.addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                filterTable();
            }
        });
    }
    
    // PDF Generation
    const pdfButton = document.getElementById('generateDriverTripsPDFBtn');
    if (pdfButton) {
        pdfButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            try {
                const { jsPDF } = window.jspdf;
                if (!jsPDF) {
                    alert('PDF library not loaded! Please refresh the page.');
                    return;
                }
                
                const doc = new jsPDF();
                const pageWidth = doc.internal.pageSize.getWidth();
                const pageHeight = doc.internal.pageSize.getHeight();

                const driverDataElement = document.getElementById('js-driver-travel-history-data');
                if (!driverDataElement) {
                    alert('Driver data not found!');
                    return;
                }
                
                // Debug: Check raw data
                const driverDataRaw = driverDataElement.dataset.driver;
                const travelHistoryRaw = driverDataElement.dataset.travelHistory;
                
                console.log('Raw driver data:', driverDataRaw);
                console.log('Raw travel history data:', travelHistoryRaw);
                
                let driverData, travelHistory;
                try {
                    driverData = JSON.parse(driverDataRaw);
                    travelHistory = JSON.parse(travelHistoryRaw);
                    
                    console.log('Parsed driver data:', driverData);
                    console.log('Parsed travel history records:', travelHistory.length);
                } catch (jsonError) {
                    console.error('JSON Parse Error:', jsonError);
                    console.error('Problematic data:', travelHistoryRaw.substring(0, 500));
                    alert('Error parsing travel data. Please check browser console for details.');
                    throw jsonError;
                }
                
                const marginTop = 12;
                const logoSize = 30;
                const logoX = pageWidth / 2 - 70;

                const logo = new Image();
                logo.src = "{{ asset('assets/images/clsu-logo.png') }}";
                logo.crossOrigin = "anonymous";

                const generatePDFFromLogo = function() {
                    // Header
                    doc.addImage(logo, "PNG", logoX, marginTop, logoSize, logoSize);
                    doc.setFont("helvetica", "normal").setFontSize(10);
                    doc.text("Republic of the Philippines", pageWidth / 2, marginTop + 2, { align: "center" });
                    doc.setFont("helvetica", "bold").setFontSize(14);
                    doc.text("CENTRAL LUZON STATE UNIVERSITY", pageWidth / 2, marginTop + 8, { align: "center" });
                    doc.setFont("helvetica", "normal").setFontSize(10);
                    doc.text("Science City of Muñoz, Nueva Ecija", pageWidth / 2, marginTop + 14, { align: "center" });
                    doc.setFont("helvetica", "bold").setFontSize(11);
                    doc.text("TRANSPORTATION SERVICES", pageWidth / 2, marginTop + 20, { align: "center" });

                    // Line under header
                    doc.setDrawColor(0, 77, 0);
                    doc.setLineWidth(0.5);
                    doc.line(15, marginTop + 33, pageWidth - 15, marginTop + 33);

                    // Title
                    doc.setFontSize(12);
                    doc.setFont("helvetica", "bold");
                    doc.text(`${driverData.full_name2} - TRAVEL HISTORY`, pageWidth / 2, marginTop + 42, { align: 'center' });

                    // Prepare table data
                    let tableData = [];
                    if (travelHistory && travelHistory.length > 0) {
                        tableData = travelHistory.map((record, index) => {
                            // Format date range from date_from to date_to
                            let dateRangeStr = '';
                            if (record.departure_date) {
                                const fromDate = new Date(record.departure_date);
                                const toDate = record.arrival_date ? new Date(record.arrival_date) : null;
                                
                                if (toDate && fromDate.getTime() !== toDate.getTime()) {
                                    // Different dates - show range
                                    const fromMonth = fromDate.toLocaleDateString('en-US', { month: 'short' });
                                    const fromDay = fromDate.getDate();
                                    const fromYear = fromDate.getFullYear();
                                    
                                    const toMonth = toDate.toLocaleDateString('en-US', { month: 'short' });
                                    const toDay = toDate.getDate();
                                    const toYear = toDate.getFullYear();
                                    
                                    if (fromMonth === toMonth && fromYear === toYear) {
                                        // Same month and year: Jan 1-2, 2026
                                        dateRangeStr = `${fromMonth} ${fromDay}-${toDay}, ${fromYear}`;
                                    } else if (fromYear === toYear) {
                                        // Same year, different months: Jan 31 - Feb 1, 2026
                                        dateRangeStr = `${fromMonth} ${fromDay} - ${toMonth} ${toDay}, ${fromYear}`;
                                    } else {
                                        // Different years: Dec 31, 2026 - Jan 1, 2027
                                        dateRangeStr = `${fromMonth} ${fromDay}, ${fromYear} - ${toMonth} ${toDay}, ${toYear}`;
                                    }
                                } else {
                                    // Same date or no arrival date - show only from date
                                    dateRangeStr = fromDate.toLocaleDateString('en-US', { 
                                        year: 'numeric', 
                                        month: 'short', 
                                        day: 'numeric' 
                                    });
                                }
                            }
                            
                            // Format departure time
                            let departureTimeStr = 'N/A';
                            if (record.departure_time) {
                                try {
                                    // Handle both string and Carbon object formats
                                    let timeString = record.departure_time;
                                    
                                    // If it's a Carbon object or has datetime format, extract just the time
                                    if (typeof timeString === 'string' && timeString.includes(' ')) {
                                        // Format might be "2026-03-04 08:00:00" - extract time part
                                        const parts = timeString.split(' ');
                                        timeString = parts[1] || timeString;
                                    }
                                    
                                    // Parse time string directly (format: HH:mm:ss or HH:mm)
                                    const timeParts = timeString.split(':');
                                    if (timeParts.length >= 2) {
                                        const hours = parseInt(timeParts[0]);
                                        const minutes = timeParts[1];
                                        
                                        // Format as 12-hour format with AM/PM
                                        const period = hours >= 12 ? 'PM' : 'AM';
                                        const displayHours = hours % 12 || 12; // Convert 0 to 12 for 12 AM
                                        departureTimeStr = `${displayHours}:${minutes} ${period}`;
                                    } else {
                                        departureTimeStr = timeString;
                                    }
                                } catch (e) {
                                    console.error('Error parsing time:', e);
                                    departureTimeStr = record.departure_time;
                                }
                            }
                            
                            // Get vehicle info
                            let vehicleInfo = 'N/A';
                            if (record.vehicle) {
                                vehicleInfo = `${record.vehicle.model}\nPlate No.: ${record.vehicle.plate_number}`;
                            }
                            
                            return [
                                index + 1,
                                {
                                    content: vehicleInfo,
                                    styles: { fontSize: 8 }
                                },
                                record.head_of_party || 'N/A',
                                record.destination || 'N/A',
                                dateRangeStr,
                                departureTimeStr,
                                record.distance_km ? parseFloat(record.distance_km).toFixed(1) : 'N/A'
                            ];
                        });
                    } else {
                        // Add a row indicating no data - single merged cell with centered message
                        tableData = [[{
                            content: 'NO TRAVEL HISTORY FOR THIS DRIVER',
                            colSpan: 7,
                            styles: { 
                                halign: 'center', 
                                fontStyle: 'bold',
                                fontSize: 10
                            }
                        }]];
                    }

                    // Add table matching vehicles list PDF design exactly
                    doc.autoTable({
                        head: [['No.', 'Vehicle', 'Head of the Party', 'Destination', 'Date', 'Time', 'Distance (km)']],
                        body: tableData,
                        startY: marginTop + 50,
                        theme: 'grid',
                        margin: { left: 15, right: 15 },
                        headStyles: { 
                            fillColor: [0, 128, 0], 
                            textColor: 255, 
                            fontStyle: 'bold', 
                            halign: 'center',
                            fontSize: 8
                        },
                        bodyStyles: { 
                            halign: 'center', 
                            valign: 'middle' 
                        },
                        styles: { 
                            fontSize: 9, 
                            cellPadding: 2,
                            overflow: 'linebreak',
                            halign: 'center'
                        },
                        columnStyles: {
                            0: { cellWidth: 14 },   // No.
                            1: { cellWidth: 35 },   // Vehicle
                            2: { cellWidth: 28 },   // Head of Party
                            3: { cellWidth: 35 },   // Destination
                            4: { cellWidth: 26 },   // Date
                            5: { cellWidth: 24 },   // Time
                            6: { cellWidth: 24 }    // Distance
                        },
                        didDrawPage: function (data) {
                            const pageCount = doc.internal.getNumberOfPages();
                            const pageNumber = doc.internal.getCurrentPageInfo().pageNumber;
                            doc.setFontSize(8);
                            doc.text(`Page ${pageNumber} of ${pageCount}`, pageWidth / 2, pageHeight - 8, { align: 'center' });
                        }
                    });

                    // Footer date
                    const date = new Date();
                    doc.setFontSize(9);
                    doc.text(
                        `Generated on: ${date.toLocaleDateString()} ${date.toLocaleTimeString()}`,
                        pageWidth - 14,
                        pageHeight - 10,
                        { align: 'right' }
                    );

                    // Generate PDF blob and show preview
                    const pdfBlob = doc.output('blob');
                    const pdfUrl = URL.createObjectURL(pdfBlob);
                    
                    // Create modal HTML
                    let modalHtml = `
                        <div id="pdfPreviewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                            <div class="bg-white rounded-2xl w-full max-w-6xl h-full flex flex-col shadow-lg">
                                <iframe src="${pdfUrl}" class="flex-1 w-full h-full border-none rounded-b-2xl"></iframe>
                            </div>
                        </div>
                    `;
                    
                    // Add modal to body
                    document.body.insertAdjacentHTML('beforeend', modalHtml);
                    
                    // Close modal when clicking outside
                    document.getElementById('pdfPreviewModal').addEventListener('click', function(e) {
                        if (e.target === this) {
                            const modal = document.getElementById('pdfPreviewModal');
                            if (modal) {
                                modal.remove();
                                // Revoke the object URL to free memory
                                URL.revokeObjectURL(pdfUrl);
                            }
                        }
                    });
                };

                const generatePDFFallback = function() {
                    console.warn('Logo failed to load, generating PDF without logo');
                    
                    // Header without logo
                    doc.setFont("helvetica", "normal").setFontSize(10);
                    doc.text("Republic of the Philippines", pageWidth / 2, marginTop + 2, { align: "center" });
                    doc.setFont("helvetica", "bold").setFontSize(14);
                    doc.text("CENTRAL LUZON STATE UNIVERSITY", pageWidth / 2, marginTop + 8, { align: "center" });
                    doc.setFont("helvetica", "normal").setFontSize(10);
                    doc.text("Science City of Muñoz, Nueva Ecija", pageWidth / 2, marginTop + 14, { align: "center" });
                    doc.setFont("helvetica", "bold").setFontSize(11);
                    doc.text("TRANSPORTATION SERVICES", pageWidth / 2, marginTop + 20, { align: "center" });

                    // Line under header
                    doc.setDrawColor(0, 77, 0);
                    doc.setLineWidth(0.5);
                    doc.line(15, marginTop + 33, pageWidth - 15, marginTop + 33);

                    // Title
                    doc.setFontSize(12);
                    doc.setFont("helvetica", "bold");
                    doc.text(`${driverData.full_name2} - TRAVEL HISTORY`, pageWidth / 2, marginTop + 42, { align: 'center' });

                    // Prepare table data (same as above)
                    let tableData = [];
                    if (travelHistory && travelHistory.length > 0) {
                        tableData = travelHistory.map((record, index) => {
                            // ... same logic as generatePDFFromLogo
                            let dateRangeStr = '';
                            if (record.departure_date) {
                                const fromDate = new Date(record.departure_date);
                                const toDate = record.arrival_date ? new Date(record.arrival_date) : null;
                                
                                if (toDate && fromDate.getTime() !== toDate.getTime()) {
                                    const fromMonth = fromDate.toLocaleDateString('en-US', { month: 'short' });
                                    const fromDay = fromDate.getDate();
                                    const fromYear = fromDate.getFullYear();
                                    
                                    const toMonth = toDate.toLocaleDateString('en-US', { month: 'short' });
                                    const toDay = toDate.getDate();
                                    const toYear = toDate.getFullYear();
                                    
                                    if (fromMonth === toMonth && fromYear === toYear) {
                                        dateRangeStr = `${fromMonth} ${fromDay}-${toDay}, ${fromYear}`;
                                    } else if (fromYear === toYear) {
                                        dateRangeStr = `${fromMonth} ${fromDay} - ${toMonth} ${toDay}, ${fromYear}`;
                                    } else {
                                        dateRangeStr = `${fromMonth} ${fromDay}, ${fromYear} - ${toMonth} ${toDay}, ${toYear}`;
                                    }
                                } else {
                                    dateRangeStr = fromDate.toLocaleDateString('en-US', { 
                                        year: 'numeric', 
                                        month: 'short', 
                                        day: 'numeric' 
                                    });
                                }
                            }
                            
                            let departureTimeStr = 'N/A';
                            if (record.departure_time) {
                                try {
                                    let timeString = record.departure_time;
                                    if (typeof timeString === 'string' && timeString.includes(' ')) {
                                        const parts = timeString.split(' ');
                                        timeString = parts[1] || timeString;
                                    }
                                    
                                    const timeParts = timeString.split(':');
                                    if (timeParts.length >= 2) {
                                        const hours = parseInt(timeParts[0]);
                                        const minutes = timeParts[1];
                                        
                                        const period = hours >= 12 ? 'PM' : 'AM';
                                        const displayHours = hours % 12 || 12;
                                        departureTimeStr = `${displayHours}:${minutes} ${period}`;
                                    } else {
                                        departureTimeStr = timeString;
                                    }
                                } catch (e) {
                                    console.error('Error parsing time:', e);
                                    departureTimeStr = record.departure_time;
                                }
                            }
                            
                            let vehicleInfo = 'N/A';
                            if (record.vehicle) {
                                vehicleInfo = `${record.vehicle.model}\nPlate No.: ${record.vehicle.plate_number}`;
                            }
                            
                            return [
                                index + 1,
                                {
                                    content: vehicleInfo,
                                    styles: { fontSize: 8 }
                                },
                                record.head_of_party || 'N/A',
                                record.destination || 'N/A',
                                dateRangeStr,
                                departureTimeStr,
                                record.distance_km ? parseFloat(record.distance_km).toFixed(1) : 'N/A'
                            ];
                        });
                    } else {
                        tableData = [[{
                            content: 'NO TRAVEL HISTORY FOR THIS DRIVER',
                            colSpan: 7,
                            styles: { 
                                halign: 'center', 
                                fontStyle: 'bold',
                                fontSize: 10
                            }
                        }]];
                    }

                    // Add table
                    doc.autoTable({
                        head: [['No.', 'Vehicle', 'Head of the Party', 'Destination', 'Date', 'Time', 'Distance (km)']],
                        body: tableData,
                        startY: marginTop + 50,
                        theme: 'grid',
                        margin: { left: 15, right: 15 },
                        headStyles: { 
                            fillColor: [0, 128, 0], 
                            textColor: 255, 
                            fontStyle: 'bold', 
                            halign: 'center',
                            fontSize: 8
                        },
                        bodyStyles: { 
                            halign: 'center', 
                            valign: 'middle' 
                        },
                        styles: { 
                            fontSize: 9, 
                            cellPadding: 2,
                            overflow: 'linebreak',
                            halign: 'center'
                        },
                        columnStyles: {
                            0: { cellWidth: 14 },
                            1: { cellWidth: 35 },
                            2: { cellWidth: 28 },
                            3: { cellWidth: 35 },
                            4: { cellWidth: 26 },
                            5: { cellWidth: 24 },
                            6: { cellWidth: 24 }
                        },
                        didDrawPage: function (data) {
                            const pageCount = doc.internal.getNumberOfPages();
                            const pageNumber = doc.internal.getCurrentPageInfo().pageNumber;
                            doc.setFontSize(8);
                            doc.text(`Page ${pageNumber} of ${pageCount}`, pageWidth / 2, pageHeight - 8, { align: 'center' });
                        }
                    });

                    // Footer date
                    const date = new Date();
                    doc.setFontSize(9);
                    doc.text(
                        `Generated on: ${date.toLocaleDateString()} ${date.toLocaleTimeString()}`,
                        pageWidth - 14,
                        pageHeight - 10,
                        { align: 'right' }
                    );

                    // Generate PDF blob and show preview
                    const pdfBlob = doc.output('blob');
                    const pdfUrl = URL.createObjectURL(pdfBlob);
                    
                    // Create modal HTML
                    let modalHtml = `
                        <div id="pdfPreviewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
                            <div class="bg-white rounded-2xl w-full max-w-6xl h-full flex flex-col shadow-lg">
                                <iframe src="${pdfUrl}" class="flex-1 w-full h-full border-none rounded-b-2xl"></iframe>
                            </div>
                        </div>
                    `;
                    
                    document.body.insertAdjacentHTML('beforeend', modalHtml);
                    
                    document.getElementById('pdfPreviewModal').addEventListener('click', function(e) {
                        if (e.target === this) {
                            const modal = document.getElementById('pdfPreviewModal');
                            if (modal) {
                                modal.remove();
                                URL.revokeObjectURL(pdfUrl);
                            }
                        }
                    });
                };

                // Load logo and generate PDF
                if (logo.complete && logo.naturalHeight !== 0) {
                    generatePDFFromLogo();
                } else {
                    logo.onload = generatePDFFromLogo;
                    logo.onerror = generatePDFFallback;
                }
                
            } catch (error) {
                console.error('PDF generation error:', error);
                alert('Error generating PDF. Please try again.');
            }
        });
    }
});
</script>