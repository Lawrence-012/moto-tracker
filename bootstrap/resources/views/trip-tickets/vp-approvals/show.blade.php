@extends('layouts.employee')

@section('title', 'Trip Ticket Details')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 border-b border-gray-200 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Trip Ticket Details
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                Detailed information about the trip ticket.
            </p>
        </div>
        <div class="px-4 py-5 sm:p-0">
            <dl class="sm:divide-y sm:divide-gray-200">
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">
                        Status
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                            {{ $tripTicket->status === 'Pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $tripTicket->status === 'Approved' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $tripTicket->status === 'Completed' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $tripTicket->status === 'Cancelled' ? 'bg-red-100 text-red-800' : '' }}
                            {{ $tripTicket->status === 'Archived' ? 'bg-gray-100 text-gray-800' : '' }}">
                            {{ $tripTicket->status }}
                        </span>
                    </dd>
                </div>
                
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">
                        Travel Order
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        {{ optional($tripTicket->itinerary->travelOrder)->employee->first_name }} 
                        {{ optional($tripTicket->itinerary->travelOrder)->employee->last_name }}
                        - {{ optional($tripTicket->itinerary->travelOrder)->destination }}
                    </dd>
                </div>
                
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">
                        Itinerary Details
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        <p><strong>Destination:</strong> {{ optional($tripTicket->itinerary)->destination }}</p>
                        <p><strong>Purpose:</strong> {{ optional($tripTicket->itinerary)->purpose }}</p>
                        <p><strong>Date:</strong> {{ optional($tripTicket->itinerary->date_from)->format('M d, Y') }} - {{ optional($tripTicket->itinerary->date_to)->format('M d, Y') }}</p>
                        <p><strong>Departure Time:</strong> {{ optional($tripTicket->itinerary)->departure_time }}</p>
                    </dd>
                </div>
                
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">
                        Vehicle
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        {{ optional($tripTicket->itinerary->vehicle)->plate_number }} - {{ optional($tripTicket->itinerary->vehicle)->model }}
                    </dd>
                </div>
                
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">
                        Driver
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        {{ optional($tripTicket->itinerary->driver)->first_name }} {{ optional($tripTicket->itinerary->driver)->last_name }}
                    </dd>
                </div>
                
                <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">
                        Head of Party
                    </dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        {{ $tripTicket->head_of_party }}
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
            </dl>
        </div>
    </div>
    
    <div class="mt-6 flex justify-end space-x-3">
        <a href="{{ route('vp.trip-tickets.approvals.index', ['tab' => request()->get('tab', 'pending')]) }}" 
           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#1e6031]">
            Back to Approvals
        </a>
        
        <button type="button" onclick="openPdfInModal()" 
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <i class="fas fa-file-pdf mr-2"></i> View PDF
        </button>
        
        @if($tripTicket->status === 'Pending')
            <a href="{{ route('vp.trip-tickets.approvals.approve', $tripTicket->id) }}" 
               onclick="event.preventDefault(); if(confirm('Are you sure you want to approve this trip ticket?')) document.getElementById('approve-form-{{ $tripTicket->id }}').submit();"
               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                Approve
            </a>
            
            <a href="{{ route('vp.trip-tickets.approvals.reject', $tripTicket->id) }}" 
               onclick="event.preventDefault(); if(confirm('Are you sure you want to reject this trip ticket?')) document.getElementById('reject-form-{{ $tripTicket->id }}').submit();"
               class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                Reject
            </a>
        @endif
    </div>
    
    <!-- PDF Modal -->
    <div id="pdfModal" class="fixed inset-0 z-50 hidden overflow-y-auto h-full w-full flex items-center justify-center p-4 bg-transparent">
        <div class="relative w-full max-w-6xl h-[85vh] rounded-lg overflow-hidden shadow-2xl bg-white">
            <!-- Close Button -->
            <button id="closeModal" class="absolute top-3 right-3 z-10 text-red-500 hover:text-red-700 bg-white rounded-full p-2 shadow-md hover:bg-red-50 transition">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <iframe id="pdfFrame" src="" class="w-full h-full border-0" frameborder="0"></iframe>
        </div>
    </div>
    
    <script>
    // Function to open PDF in modal
    function openPdfInModal() {
        const pdfUrl = '{{ route("trip-tickets.pdf", $tripTicket->id) }}';
        const modal = document.getElementById('pdfModal');
        const pdfFrame = document.getElementById('pdfFrame');
        
        // Set iframe src to PDF URL
        pdfFrame.src = pdfUrl;
        
        // Show modal
        modal.classList.remove('hidden');
        
        // Prevent body scroll when modal is open
        document.body.style.overflow = 'hidden';
    }

    // Function to close PDF modal
    function closePdfModal() {
        const modal = document.getElementById('pdfModal');
        const pdfFrame = document.getElementById('pdfFrame');
        
        modal.classList.add('hidden');
        pdfFrame.src = ''; // Clear iframe to stop PDF loading
        document.body.style.overflow = 'auto';
    }

    // Connect close button for PDF modal
    document.getElementById('closeModal').addEventListener('click', closePdfModal);

    // Close PDF modal when clicking outside
    document.addEventListener('click', function(e) {
        const modal = document.getElementById('pdfModal');
        if (e.target === modal) {
            closePdfModal();
        }
    });

    // Close PDF modal with ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closePdfModal();
        }
    });
    </script>
    
    <!-- Hidden forms for approve/reject actions -->
    <form id="approve-form-{{ $tripTicket->id }}" action="{{ route('vp.trip-tickets.approvals.approve', $tripTicket->id) }}" method="POST" style="display: none;">
        @csrf
        @method('PUT')
    </form>
    <form id="reject-form-{{ $tripTicket->id }}" action="{{ route('vp.trip-tickets.approvals.reject', $tripTicket->id) }}" method="POST" style="display: none;">
        @csrf
        @method('PUT')
    </form>
</div>
@endsection