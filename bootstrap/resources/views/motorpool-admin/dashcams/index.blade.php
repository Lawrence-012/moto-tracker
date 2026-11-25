@extends('layouts.motorpool-admin')

@section('content')
<style>
    .dc-card { background:#fff; border:1px solid #d1e7d6; border-radius:14px; box-shadow:0 6px 18px -10px rgba(30,96,49,.35); }
    .dc-code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; letter-spacing:.08em; font-weight:700; color:#1e6031; }
    .dc-btn { display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:8px; font-size:12px; font-weight:600; border:1px solid; background:transparent; cursor:pointer; text-decoration:none; }
    .dc-btn-primary { background:#1e6031; color:#fff; border-color:#1e6031; }
    .dc-btn-primary:hover { background:#174d27; }
    .dc-btn-view { color:#1e6031; border-color:#1e6031; }
    .dc-btn-view:hover { background:#1e6031; color:#fff; }
    .dc-btn-warn { color:#b45309; border-color:#f59e0b; }
    .dc-btn-warn:hover { background:#f59e0b; color:#fff; }
    .dc-btn-danger { color:#b91c1c; border-color:#ef4444; }
    .dc-btn-danger:hover { background:#ef4444; color:#fff; }
    .dc-pill { display:inline-flex; align-items:center; gap:6px; padding:2px 10px; border-radius:999px; font-size:11px; font-weight:700; }
    .dc-online { background:#dcfce7; color:#166534; }
    .dc-offline { background:#f3f4f6; color:#6b7280; }
    .dc-pending { background:#fef3c7; color:#92400e; }
</style>

<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="flex flex-wrap justify-between items-end gap-3 mb-4">
            <div>
                <h2 class="font-bold" style="color:#1e6031; font-size:1.75rem;">Dashcams</h2>
                <p class="text-sm text-gray-500">Register one dashcam per vehicle. Each dashcam gets its own QR code and manual code.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 rounded-md p-3" style="background-color:#DFF0D8; border:1px solid #1e6031;">
                <p class="text-sm font-medium" style="color:#1e6031;">{{ session('success') }}</p>
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 rounded-md p-3 bg-red-50 border border-red-300">
                <p class="text-sm font-medium text-red-700">{{ $errors->first() }}</p>
            </div>
        @endif

        {{-- REGISTER --}}
        <div class="dc-card p-5 mb-6">
            <div class="flex flex-wrap items-end gap-4 justify-between">
                <div>
                    <h3 class="font-bold text-sm uppercase tracking-wide" style="color:#1e6031;">Register a new dashcam</h3>
                    <p class="text-xs text-gray-500 mt-1">Pick a vehicle that does not have a dashcam yet. A QR and a code are generated.</p>
                </div>

                @if($availableVehicles->count())
                    <form method="POST" action="{{ route('motorpool.dashcams.store') }}" class="flex flex-wrap items-end gap-3">
                        @csrf
                        <div>
                            <label for="vehicle_id" class="block text-xs font-semibold text-gray-600 mb-1">Vehicle</label>
                            <select id="vehicle_id" name="vehicle_id" required class="rounded-md border-gray-300 text-sm min-w-[260px]">
                                <option value="">Select vehicle...</option>
                                @foreach($availableVehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}">{{ $vehicle->plate_number }} &mdash; {{ $vehicle->model }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="dc-btn dc-btn-primary">
                            <i class="fas fa-qrcode"></i> Generate QR &amp; Code
                        </button>
                    </form>
                @else
                    <span class="text-xs text-gray-500">Every vehicle already has a dashcam.</span>
                @endif
            </div>
        </div>

        {{-- LIST --}}
        <div class="dc-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead style="background-color:#1e6031; color:#fff;">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider">Vehicle</th>
                            <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider">Dashcam Code</th>
                            <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider">Status</th>
                            <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider">Last Seen</th>
                            <th class="px-4 py-2.5 text-center text-xs font-bold uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($dashcams as $dashcam)
                            <tr class="hover:bg-[#f0f8f0]">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-semibold text-sm">{{ $dashcam->vehicle->plate_number ?? 'N/A' }}</div>
                                    <div class="text-xs text-gray-400">{{ $dashcam->vehicle->model ?? '' }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="dc-code text-sm">{{ $dashcam->code }}</span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($dashcam->is_online)
                                        <span class="dc-pill dc-online"><i class="fas fa-circle text-[6px]"></i> Online</span>
                                    @elseif($dashcam->paired_at)
                                        <span class="dc-pill dc-offline">Offline</span>
                                    @else
                                        <span class="dc-pill dc-pending">Not paired yet</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500">
                                    {{ $dashcam->last_seen_at ? $dashcam->last_seen_at->diffForHumans() : '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex justify-center gap-2">
                                        <a href="{{ route('motorpool.dashcams.show', $dashcam) }}" class="dc-btn dc-btn-view">
                                            <i class="fas fa-qrcode"></i> QR &amp; Code
                                        </a>
                                        <form method="POST" action="{{ route('motorpool.dashcams.regenerate', $dashcam) }}"
                                              onsubmit="return confirm('Generate a new code? The old code will stop working immediately.');">
                                            @csrf
                                            <button type="submit" class="dc-btn dc-btn-warn"><i class="fas fa-rotate"></i> New code</button>
                                        </form>
                                        <form method="POST" action="{{ route('motorpool.dashcams.destroy', $dashcam) }}"
                                              onsubmit="return confirm('Remove this dashcam from {{ $dashcam->vehicle->plate_number ?? 'this vehicle' }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dc-btn dc-btn-danger"><i class="fas fa-trash"></i> Remove</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">No dashcams registered yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection
