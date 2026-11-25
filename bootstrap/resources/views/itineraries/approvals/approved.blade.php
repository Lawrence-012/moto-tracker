@extends('layouts.employee')

@section('header')
    <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Approved Itineraries') }}
        </h2>
        <a href="{{ auth()->user()->employee->is_vp ? route('vp.travel-orders.index') : (auth()->user()->employee->is_head ? route('unithead.travel-orders.index') : route('employee.dashboard')) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
            Back to Dashboard
        </a>
    </div>
@endsection

@section('content')
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if(session('success'))
                        <div class="mb-6 rounded-md bg-green-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-green-800">
                                        {{ session('success') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if($itineraries->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr>
                                        <th class="font-bold text-gray-900 py-1.5 border-b text-left px-4">No.</th>
                                        <th class="font-bold text-gray-900 py-1.5 border-b text-left px-4">Travel Order</th>
                                        <th class="font-bold text-gray-900 py-1.5 border-b text-left px-4">Driver</th>
                                        <th class="font-bold text-gray-900 py-1.5 border-b text-left px-4">Vehicle</th>
                                        <th class="font-bold text-gray-900 py-1.5 border-b text-left px-4">Destination</th>
                                        <th class="font-bold text-gray-900 py-1.5 border-b text-left px-4">Date Range</th>
                                        <th class="font-bold text-gray-900 py-1.5 border-b text-left px-4">Purpose</th>
                                        <th class="font-bold text-gray-900 py-1.5 border-b text-center px-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($itineraries as $index => $itinerary)
                                        <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }} hover:bg-[#f0f8f0]">
                                            <td class="px-4 py-3 text-xs text-[#004d00] font-medium">{{ $itineraries instanceof \Illuminate\Pagination\LengthAwarePaginator ? $itineraries->firstItem() + $loop->index : $loop->iteration }}</td>
                                            <td class="px-4 py-3 text-xs text-[#004d00] font-medium">
                                                @if($itinerary->travelOrder)
                                                    #{{ $itinerary->travelOrder->id }} - {{ $itinerary->travelOrder->employee?->full_name ?? 'N/A' }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-xs text-[#006400]">
                                                @if($itinerary->driver)
                                                    {{ $itinerary->driver->full_name }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-xs text-[#006400]">
                                                @if($itinerary->vehicle)
                                                    {{ $itinerary->vehicle->plate_number }} - {{ $itinerary->vehicle->model }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-xs text-[#006400]">{{ $itinerary->destination }}</td>
                                            <td class="px-4 py-3 text-xs text-[#006400]">
                                                @php
                                                    $dateFrom = $itinerary->date_from instanceof \DateTimeInterface 
                                                        ? \Carbon\Carbon::instance($itinerary->date_from) 
                                                        : (\Carbon\Carbon::parse($itinerary->date_from) ?? null);
                                                    $dateTo = $itinerary->date_to instanceof \DateTimeInterface 
                                                        ? \Carbon\Carbon::instance($itinerary->date_to) 
                                                        : (\Carbon\Carbon::parse($itinerary->date_to) ?? null);
                                                    
                                                    if ($dateFrom && $dateTo) {
                                                        if ($dateFrom->isSameDay($dateTo)) {
                                                            echo $dateFrom->format('M j, Y');
                                                        } elseif ($dateFrom->month == $dateTo->month && $dateFrom->year == $dateTo->year) {
                                                            echo $dateFrom->format('M j') . '-' . $dateTo->format('j, Y');
                                                        } elseif ($dateFrom->year == $dateTo->year) {
                                                            echo $dateFrom->format('M j') . ' - ' . $dateTo->format('M j, Y');
                                                        } else {
                                                            echo $dateFrom->format('M j, Y') . ' - ' . $dateTo->format('M j, Y');
                                                        }
                                                    } else {
                                                        echo 'N/A';
                                                    }
                                                @endphp
                                            </td>
                                            <td class="px-4 py-3 text-xs text-gray-600 max-w-xs break-words">
                                                <div class="text-[#004d00] font-medium">{{ Str::limit($itinerary->purpose, 50) }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-xs text-center">
                                                <button type="button" 
                                                        onclick="loadAndShowItinerary({{ $itinerary->id }})" 
                                                        class="btn view-btn border inline-flex items-center justify-center"
                                                        title="View Details">
                                                    <svg class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    View
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        @if($itineraries instanceof \Illuminate\Pagination\LengthAwarePaginator)
                        <div class="flex justify-between items-center mt-4">
                            <div class="text-sm text-gray-600">
                                Showing {{ $itineraries->firstItem() ?? 0 }} to {{ $itineraries->lastItem() ?? 0 }} of {{ $itineraries->total() }} results
                            </div>
                            <div class="flex items-center space-x-2">
                                @if($itineraries->lastPage() > 1)
                                    <nav>
                                        <ul class="pagination">
                                            {{-- Previous Button --}}
                                            @if($itineraries->onFirstPage())
                                                <li class="page-item disabled">
                                                    <span class="page-link">Prev</span>
                                                </li>
                                            @else
                                                <li class="page-item">
                                                    <a href="{{ $itineraries->previousPageUrl() }}" class="page-link">Prev</a>
                                                </li>
                                            @endif
                                            
                                            {{-- Current Page --}}
                                            <li class="page-item active">
                                                <span class="page-link">{{ $itineraries->currentPage() }}</span>
                                            </li>
                                            
                                            {{-- Next Button --}}
                                            @if($itineraries->hasMorePages())
                                                <li class="page-item">
                                                    <a href="{{ $itineraries->nextPageUrl() }}" class="page-link">Next</a>
                                                </li>
                                            @else
                                                <li class="page-item disabled">
                                                    <span class="page-link">Next</span>
                                                </li>
                                            @endif
                                        </ul>
                                    </nav>
                                @endif
                            </div>
                        </div>
                        @endif
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No approved itineraries</h3>
                            <p class="mt-1 text-sm text-gray-500">There are currently no fully approved itineraries.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>


@endsection