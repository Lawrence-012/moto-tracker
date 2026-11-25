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

        <div class="mb-4">
            <h2 class="font-bold" style="color:#1e6031; font-size:1.75rem;">Dashcams</h2>
            <p class="text-sm text-gray-500">Each dashcam app shows its own QR code. Scan the QR (or type the code) and link it to one vehicle.</p>
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
            <h3 class="font-bold text-sm uppercase tracking-wide mb-1" style="color:#1e6031;">Register a dashcam to a vehicle</h3>
            <p class="text-xs text-gray-500 mb-4">Scan the QR shown on the dashcam, or type the code. Pick the vehicle it is installed in.</p>

            @if($availableVehicles->count())
                <form method="POST" action="{{ route('motorpool.dashcams.register') }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div>
                        <label for="dc-code" class="block text-xs font-semibold text-gray-600 mb-1">Dashcam code</label>
                        <div class="flex gap-2">
                            <input id="dc-code" name="code" value="{{ old('code') }}" placeholder="DC-XXXX-XXXX" required
                                   class="rounded-md border-gray-300 text-sm dc-code uppercase min-w-[200px]">
                            <button type="button" id="scan-open" class="dc-btn dc-btn-view"><i class="fas fa-qrcode"></i> Scan QR</button>
                        </div>
                    </div>
                    <div>
                        <label for="vehicle_id" class="block text-xs font-semibold text-gray-600 mb-1">Vehicle</label>
                        <select id="vehicle_id" name="vehicle_id" required class="rounded-md border-gray-300 text-sm min-w-[260px]">
                            <option value="">Select vehicle...</option>
                            @foreach($availableVehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" @selected(old('vehicle_id') == $vehicle->id)>{{ $vehicle->plate_number }} &mdash; {{ $vehicle->model }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="odometer_km" class="block text-xs font-semibold text-gray-600 mb-1">Starting odometer (km)</label>
                        <input id="odometer_km" name="odometer_km" type="number" min="0" value="{{ old('odometer_km') }}"
                               placeholder="Blank = vehicle mileage" class="rounded-md border-gray-300 text-sm w-44">
                    </div>
                    <button type="submit" class="dc-btn dc-btn-primary"><i class="fas fa-link"></i> Register</button>
                </form>
            @else
                <span class="text-xs text-gray-500">Every vehicle already has a dashcam, or there are no vehicles yet.</span>
            @endif
        </div>

        {{-- PENDING DEVICES --}}
        <div class="dc-card overflow-hidden mb-6">
            <div class="px-5 py-3 border-b border-gray-200">
                <h3 class="font-bold text-sm uppercase tracking-wide" style="color:#1e6031;">Waiting for registration ({{ $pending->count() }})</h3>
                <p class="text-xs text-gray-500">Devices that checked in but are not linked to a vehicle yet.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead style="background-color:#1e6031; color:#fff;">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider">Code</th>
                            <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider">First check-in</th>
                            <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider">Last seen</th>
                            <th class="px-4 py-2.5 text-center text-xs font-bold uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($pending as $device)
                            <tr class="hover:bg-[#f0f8f0]">
                                <td class="px-4 py-3 whitespace-nowrap"><span class="dc-code text-sm">{{ $device->code }}</span></td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500">{{ $device->first_seen_at?->diffForHumans() ?? '—' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500">{{ $device->last_seen_at?->diffForHumans() ?? '—' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-center">
                                    <button type="button" class="dc-btn dc-btn-view" data-use-code="{{ $device->code }}">
                                        <i class="fas fa-arrow-up"></i> Use this code
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">No pending dashcams. Open the dashcam app to add one.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- REGISTERED --}}
        <div class="dc-card overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-200 flex items-center justify-between">
                <h3 class="font-bold text-sm uppercase tracking-wide" style="color:#1e6031;">Registered dashcams ({{ $registered->count() }})</h3>
                <a href="{{ route('motorpool.dashcams.print') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-md text-xs font-semibold text-white bg-red-600 hover:bg-red-700 shadow-sm transition">
                    <i class="fas fa-file-pdf"></i> Generate PDF
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead style="background-color:#1e6031; color:#fff;">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider">Vehicle</th>
                            <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider">Dashcam Code</th>
                            <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider">Status</th>
                            <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider">Odometer</th>
                            <th class="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider">Last Seen &amp; Location</th>
                            <th class="px-4 py-2.5 text-center text-xs font-bold uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($registered as $dashcam)
                            <tr class="hover:bg-[#f0f8f0]">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-semibold text-sm">{{ $dashcam->vehicle->plate_number ?? 'N/A' }}</div>
                                    <div class="text-xs text-gray-400">{{ $dashcam->vehicle->model ?? '' }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap"><span class="dc-code text-sm">{{ $dashcam->code }}</span></td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if($dashcam->is_online)
                                        <span class="dc-pill dc-online"><i class="fas fa-circle text-[6px]"></i> Online</span>
                                    @else
                                        <span class="dc-pill dc-offline">Offline</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-semibold text-sm">{{ number_format($dashcam->currentOdometerKm()) }} km</div>
                                    <div class="text-xs text-gray-400">+{{ number_format($dashcam->distance_km, 1) }} km measured</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500">
                                    <div>{{ $dashcam->last_seen_at ? $dashcam->last_seen_at->diffForHumans() : '—' }}</div>
                                    @if($dashcam->last_latitude !== null)
                                        <a href="{{ route('trip-tickets.ongoing-map', ['dashcam' => $dashcam->id]) }}"
                                           class="text-[#1e6031] underline">
                                            <i class="fas fa-location-dot"></i> View on map
                                        </a>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex justify-center gap-2">
                                        <form method="POST" action="{{ route('motorpool.dashcams.unregister', $dashcam) }}"
                                              onsubmit="return confirm('Unlink this dashcam from {{ $dashcam->vehicle->plate_number ?? 'this vehicle' }}? It will go back to waiting for registration.');">
                                            @csrf
                                            <button type="submit" class="dc-btn dc-btn-warn"><i class="fas fa-unlink"></i> Unlink</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">No dashcams registered yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

{{-- QR scanner modal --}}
<div id="scan-modal" class="hidden fixed inset-0 z-[70] flex items-center justify-center bg-black/60 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-4">
        <div class="flex items-center justify-between mb-3">
            <h4 class="font-bold text-sm" style="color:#1e6031;">Scan the dashcam QR</h4>
            <button type="button" id="scan-close" class="text-gray-500 hover:text-gray-800"><i class="fas fa-xmark"></i></button>
        </div>
        <div id="qr-reader" class="w-full rounded-lg overflow-hidden"></div>
        <p class="text-xs text-gray-500 mt-3">Camera works on https or localhost. If it does not open, type the code instead.</p>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const codeInput = document.getElementById('dc-code');
    const modal = document.getElementById('scan-modal');
    let scanner = null;

    // "Use this code" on a pending device fills the code box
    document.querySelectorAll('[data-use-code]').forEach(btn => {
        btn.addEventListener('click', () => {
            codeInput.value = btn.dataset.useCode;
            codeInput.focus();
        });
    });

    function closeScanner() {
        modal.classList.add('hidden');
        if (scanner) {
            const s = scanner;
            scanner = null;
            s.stop().catch(() => {}).finally(() => s.clear());
        }
    }

    document.getElementById('scan-open').addEventListener('click', async () => {
        modal.classList.remove('hidden');
        scanner = new Html5Qrcode('qr-reader');
        try {
            await scanner.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: 240 },
                (text) => {
                    codeInput.value = text.trim();
                    closeScanner();
                },
                () => {}
            );
        } catch (e) {
            closeScanner();
            alert('Hindi mabuksan ang camera. Gamitin ang https o i-type ang code.');
        }
    });

    document.getElementById('scan-close').addEventListener('click', closeScanner);
});
</script>
@endsection
