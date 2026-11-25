@extends('layouts.motorpool-admin')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Back Button and View Maintenance Button -->
        <div class="mb-6 flex justify-between items-center">
            <a href="{{ route('vehicles.index') }}" class="inline-flex items-center px-4 py-2 bg-[#1e6031] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-[#164f2a] focus:bg-[#164f2a] active:bg-[#103c1e] focus:outline-none focus:ring-2 focus:ring-[#1e6031] focus:ring-offset-2 transition ease-in-out duration-150">
                <i class="fas fa-arrow-left mr-2"></i> Back
            </a>
            <a href="{{ route('vehicles.maintenance', $vehicle) }}" 
               class="inline-flex items-center px-4 py-2 bg-[#1e6031] border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-wider hover:bg-[#164f2a] focus:bg-[#164f2a] active:bg-[#103c1e] focus:outline-none focus:ring-2 focus:ring-[#1e6031] focus:ring-offset-2 transition ease-in-out duration-150"
               style="position: relative;">
                <i class="fas fa-wrench mr-2"></i> View Maintenance
                @if($vehicle->hasActiveMaintenanceIssues())
                    <span class="badge-notification" 
                          title="{{ $vehicle->getActiveMaintenanceIssuesCount() }} Active Maintenance Issue(s)"
                          style="position: absolute; top: -10px; left: -10px; background-color: #dc2626; color: white; border-radius: 9999px; padding: 3px 7px; font-size: 0.75rem; min-width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-weight: 600; box-shadow: 0 2px 4px rgba(0,0,0,0.2); z-index: 10;">
                        {{ $vehicle->getActiveMaintenanceIssuesCount() }}
                    </span>
                @endif
                @if($vehicle->needsOilChange() && !$vehicle->isOilChangeNotificationDismissed())
                    <span class="badge-notification" 
                          title="Needs oil change - exceeded 5000 km since last service"
                          style="position: absolute; top: -10px; right: -10px; background: linear-gradient(135deg, #fb923c 0%, #ea580c 100%); color: white; border-radius: 9999px; padding: 3px 8px; font-size: 0.7rem; min-width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; box-shadow: 0 2px 4px rgba(0,0,0,0.2); z-index: 10; border: 2px solid #fff;">
                        🛢️
                    </span>
                @endif
            </a>
        </div>

        <div class="row g-3">
    <!-- Vehicle Image -->
    <div class="col-md-4 d-flex align-items-start">
        <div class="card shadow-sm w-100 border-[#1e6031]" style="height: 200px;">
            @php
                // Check if the image exists in the new public directory
                $imagePath = $vehicle->picture && file_exists(public_path('vehicles/images/' . $vehicle->picture))
                    ? asset('vehicles/images/' . $vehicle->picture)
                    : asset('vehicles/images/vehicle_default.png');
            @endphp
            <img 
                src="{{ $imagePath }}" 
                class="card-img-top img-fluid" 
                alt="{{ $vehicle->model ?? 'Vehicle Image' }}"
                style="object-fit: cover; height: 100%; width: 100%;"
                onerror="this.src='{{ asset('storage/vehicles/images/vehicle_default.png') }}'">
        </div>
    </div>

    <!-- Vehicle Info -->
    <div class="col-md-8 d-flex align-items-start">
        <div class="card w-100 shadow-sm border-[#1e6031]" style="height: 200px; border-radius: 12px;">
            <div class="card-header d-flex align-items-center" style="background-color: #1e6031; color: white; font-weight: 600; border-radius: 12px 12px 0 0;">
                <i class="fas fa-car me-2"></i>
                <h5 class="mb-0">Vehicle Information</h5>
            </div>
            <div class="card-body p-3" style="font-size: 0.9rem; line-height: 1.5;">
                <div class="row">
                    <div class="col-6 mb-2">
                        <strong>Model:</strong> {{ $vehicle->model }}
                    </div>
                    <div class="col-6 mb-2">
                        <strong>Plate Number:</strong> {{ $vehicle->plate_number }}
                    </div>
                    <div class="col-6 mb-2">
                        <strong>Fuel Type:</strong> {{ $vehicle->fuel_type ?? 'N/A' }}
                    </div>
                    <div class="col-6 mb-2">
                        <strong>Seating Capacity:</strong> {{ $vehicle->seating_capacity ?? 'N/A' }}
                    </div>
                    <div class="col-6 mb-2">
                        <strong>Mileage:</strong> {{ number_format($vehicle->mileage) }} km
                    </div>
                    <div class="col-6 mb-2">
                        <strong>Type:</strong> {{ $vehicle->type ?? 'N/A' }}
                    </div>
                    <div class="col-6 mb-2">
                        <strong>Status:</strong>
                        <span class="badge 
                            @if($vehicle->status === 'Available') bg-success
                            @elseif($vehicle->status === 'Not Available') bg-danger
                            @elseif($vehicle->status === 'Active') bg-primary
                            @elseif($vehicle->status === 'Under Maintenance') bg-warning text-dark
                            @endif">
                            {{ $vehicle->status }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
                        </div>
                    </div>

        <!-- Travel History Section -->
        <div class="mt-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-semibold" style="color: #1e6031;">
                        {{ $vehicle->model }} {{ $vehicle->plate_number }} - Travel History
                    </h3>
                    <div class="flex items-center gap-2">
                        <!-- Search Bar -->
                        <div class="flex">
                            <input type="text" placeholder="Search history..." 
                                   style="height: 32px; width: 200px; font-size: 0.85rem; border: 1px solid #1e6031; border-radius: 0.375rem 0 0 0.375rem; padding: 0 12px;">
                            <button type="button" 
                                    style="background-color: #1e6031; color: #ffffff; border: 1px solid #1e6031; height: 32px; width: 40px; border-radius: 0 0.375rem 0.375rem 0; cursor:pointer;">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        
                        <!-- Generate PDF Button -->
                        <button type="button" id="generateVehicleTravelHistoryPDFBtn" class="inline-flex items-center justify-center bg-red-600 border border-red-600 rounded text-xs text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150" style="height: 32px; padding: 0 12px; font-size: 0.80rem;">
                            <i class="fas fa-file-pdf mr-1 text-xs"></i> Generate PDF
                        </button>
                    </div>
                </div>

                <!-- Travel History Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead style="background-color: #1e6031; color: white;">
                            <tr>
                                <th style="padding: 8px 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.70rem; letter-spacing: 0.05em;">No.</th>
                                <th style="padding: 8px 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.70rem; letter-spacing: 0.05em;">Driver</th>
                                <th style="padding: 8px 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.70rem; letter-spacing: 0.05em;">Head of Party</th>
                                <th style="padding: 8px 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.70rem; letter-spacing: 0.05em;">Destination</th>
                                <th style="padding: 8px 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.70rem; letter-spacing: 0.05em;">Departure Time</th>
                                <th style="padding: 8px 10px; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 0.70rem; letter-spacing: 0.05em;">Distance (km)</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @if($travelHistory->isEmpty())
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-car text-gray-300 text-3xl mb-2"></i>
                                            <h4 class="text-base font-medium text-gray-900 mb-1">No Completed Travel History</h4>
                                            <p class="text-gray-500 text-sm">This vehicle has no recorded travel history yet.</p>
                                        </div>
                                    </td>
                                </tr>
                            @else
                                @foreach($travelHistory as $index => $history)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-2 whitespace-nowrap text-xs text-gray-900">{{ $travelHistory->firstItem() + $index }}</td>
                                        <td class="px-6 py-2 whitespace-nowrap">
                                            <div class="text-xs text-gray-900 font-medium">
                                                {{ $history->driver->full_name2 ?? ($history->driver->first_name ?? '') . ' ' . ($history->driver->last_name ?? 'N/A') }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ $history->driver->email ?? 'N/A' }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-2 text-xs text-gray-900">{{ $history->head_of_party }}</td>
                                        <td class="px-6 py-2 text-xs text-gray-900">{{ $history->destination }}</td>
                                        <td class="px-6 py-2 whitespace-nowrap">
                                            <div class="text-xs text-gray-900">
                                                {{ $history->departure_date->format('M d, Y') }}
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
                            @endif
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if(!$travelHistory->isEmpty())
                    <div class="flex justify-between items-center mt-4">
                        <div class="text-sm text-gray-600">
                            Showing {{ $travelHistory->firstItem() }} to {{ $travelHistory->lastItem() }} of {{ $travelHistory->total() }} records
                        </div>
                        <div class="flex items-center space-x-2">
                            <nav>
                                <ul class="pagination">
                                    <li class="page-item {{ $travelHistory->currentPage() <= 1 ? 'disabled' : '' }}">
                                        <a class="page-link {{ $travelHistory->currentPage() <= 1 ? 'disabled-link' : '' }}" href="{{ $travelHistory->previousPageUrl() }}" {{ $travelHistory->currentPage() <= 1 ? 'aria-disabled="true"' : '' }}>Prev</a>
                                    </li>
                                    <li class="page-item active">
                                        <span class="page-link">{{ $travelHistory->currentPage() }}</span>
                                    </li>
                                    <li class="page-item {{ $travelHistory->currentPage() >= $travelHistory->lastPage() ? 'disabled' : '' }}">
                                        <a class="page-link {{ $travelHistory->currentPage() >= $travelHistory->lastPage() ? 'disabled-link' : '' }}" href="{{ $travelHistory->nextPageUrl() }}" {{ $travelHistory->currentPage() >= $travelHistory->lastPage() ? 'aria-disabled="true"' : '' }}>Next</a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

<style>
/* Pagination Styles */
.pagination {
    display: flex;
    padding-left: 0;
    list-style: none;
    border-radius: 0.375rem;
}

.page-item {
    margin: 0 2px;
}

.page-link {
    position: relative;
    display: block !important;
    padding: 0.25rem 0.5rem !important;
    font-size: 0.875rem !important;
    color: #1e6031;
    text-decoration: none;
    background-color: #fff;
    border: 1px solid #1e6031;
    border-radius: 0.25rem;
    transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
    line-height: 1.2;
    min-height: auto;
    font-weight: normal !important;
}

.page-link:hover {
    color: #fff;
    background-color: #1e6031;
    border-color: #1e6031;
}

.page-item.active .page-link {
    color: #fff;
    background-color: #1e6031;
    border-color: #1e6031;
}

.page-item.disabled .page-link {
    color: #6c757d;
    pointer-events: none;
    background-color: #fff;
    border-color: #dee2e6;
}

.disabled-link {
    color: #6c757d !important;
    pointer-events: none;
    cursor: not-allowed;
}
</style>

<!-- Hidden data container for PDF generation -->
<div id="js-vehicle-travel-history-data" 
     data-vehicle="{{ json_encode(['id' => $vehicle->id, 'model' => $vehicle->model, 'plate_number' => $vehicle->plate_number], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}" 
     data-travel-history="{{ json_encode($travelHistory->items(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btn = document.getElementById('generateVehicleTravelHistoryPDFBtn');
        if (!btn) return;

        btn.addEventListener('click', function (e) {
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

                const vehicleDataElement = document.getElementById('js-vehicle-travel-history-data');
                if (!vehicleDataElement) {
                    alert('Vehicle data not found!');
                    return;
                }
                
                // Debug: Check raw data
                const vehicleDataRaw = vehicleDataElement.dataset.vehicle;
                const travelHistoryRaw = vehicleDataElement.dataset.travelHistory;
                
                console.log('Raw vehicle data:', vehicleDataRaw);
                console.log('Raw travel history data:', travelHistoryRaw);
                
                let vehicleData, travelHistory;
                try {
                    vehicleData = JSON.parse(vehicleDataRaw);
                    travelHistory = JSON.parse(travelHistoryRaw);
                    
                    console.log('Parsed vehicle data:', vehicleData);
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
                    doc.text(`${vehicleData.model} ${vehicleData.plate_number} - TRAVEL HISTORY`, pageWidth / 2, marginTop + 42, { align: 'center' });

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
                                    // Parse time string directly (format: HH:mm:ss or HH:mm)
                                    const timeParts = record.departure_time.split(':');
                                    if (timeParts.length >= 2) {
                                        const hours = parseInt(timeParts[0]);
                                        const minutes = timeParts[1];
                                        
                                        // Format as 12-hour format with AM/PM
                                        const period = hours >= 12 ? 'PM' : 'AM';
                                        const displayHours = hours % 12 || 12; // Convert 0 to 12 for 12 AM
                                        departureTimeStr = `${displayHours}:${minutes} ${period}`;
                                    } else {
                                        departureTimeStr = record.departure_time;
                                    }
                                } catch (e) {
                                    console.error('Error parsing time:', e);
                                    departureTimeStr = record.departure_time;
                                }
                            }
                            
                            const driverName = record.driver?.full_name2 || 
                                              `${record.driver?.first_name || ''} ${record.driver?.last_name || ''}`.trim() || 
                                              'N/A';
                            
                            return [
                                index + 1,
                                {
                                    content: driverName,
                                    styles: { fontStyle: 'bold' }
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
                            content: 'NO TRAVEL HISTORY FOR THIS VEHICLE',
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
                        head: [['No.', 'Driver', 'Head of the Party', 'Destination', 'Date', 'Time', 'Distance (km)']],
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
                            1: { cellWidth: 28 },   // Driver
                            2: { cellWidth: 28 },   // Head of Party
                            3: { cellWidth: 38 },   // Destination
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

                    // Footer date - matching vehicles list PDF
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
                    doc.text(`${vehicleData.model} ${vehicleData.plate_number} - TRAVEL HISTORY`, pageWidth / 2, marginTop + 42, { align: 'center' });

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
                                    // Parse time string directly (format: HH:mm:ss or HH:mm)
                                    const timeParts = record.departure_time.split(':');
                                    if (timeParts.length >= 2) {
                                        const hours = parseInt(timeParts[0]);
                                        const minutes = timeParts[1];
                                        
                                        // Format as 12-hour format with AM/PM
                                        const period = hours >= 12 ? 'PM' : 'AM';
                                        const displayHours = hours % 12 || 12; // Convert 0 to 12 for 12 AM
                                        departureTimeStr = `${displayHours}:${minutes} ${period}`;
                                    } else {
                                        departureTimeStr = record.departure_time;
                                    }
                                } catch (e) {
                                    console.error('Error parsing time:', e);
                                    departureTimeStr = record.departure_time;
                                }
                            }
                            
                            const driverName = `${record.driver?.first_name || ''} ${record.driver?.last_name || ''}`.trim() || 'N/A';
                            
                            return [
                                index + 1,
                                {
                                    content: driverName,
                                    styles: { fontStyle: 'bold' }
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
                            content: 'NO TRAVEL HISTORY FOR THIS VEHICLE',
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
                        head: [['No.', 'Driver', 'Head of the Party', 'Destination', 'Date', 'Time', 'Distance (km)']],
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
                            1: { cellWidth: 28 },   // Driver
                            2: { cellWidth: 28 },   // Head of Party
                            3: { cellWidth: 38 },   // Destination
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

                    // Footer date - matching vehicles list PDF
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

                // Set up image loading
                logo.onload = generatePDFFromLogo;
                logo.onerror = generatePDFFallback;
                
            } catch (error) {
                console.error('Error generating PDF:', error);
                alert('Error generating PDF: ' + error.message);
            }
        });
    });
</script>