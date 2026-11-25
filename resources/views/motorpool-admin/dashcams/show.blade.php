@extends('layouts.motorpool-admin')

@section('content')
<style>
    .dc-card { background:#fff; border:1px solid #d1e7d6; border-radius:14px; box-shadow:0 6px 18px -10px rgba(30,96,49,.35); }
    .dc-code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; letter-spacing:.12em; font-weight:800; color:#1e6031; }
    .dc-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 14px; border-radius:8px; font-size:12px; font-weight:600; border:1px solid #1e6031; background:#fff; color:#1e6031; cursor:pointer; text-decoration:none; }
    .dc-btn:hover { background:#1e6031; color:#fff; }
    .dc-btn-primary { background:#1e6031; color:#fff; }
    .dc-btn-primary:hover { background:#174d27; }
    @media print {
        body * { visibility: hidden !important; }
        #dc-print-area, #dc-print-area * { visibility: visible !important; }
        #dc-print-area { position: absolute; left: 0; top: 0; width: 100%; }
    }
</style>

<div class="py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="flex items-center justify-between mb-4">
            <div>
                <a href="{{ route('motorpool.dashcams.index') }}" class="text-sm text-[#1e6031] hover:underline"><i class="fas fa-arrow-left"></i> Back to Dashcams</a>
                <h2 class="font-bold mt-1" style="color:#1e6031; font-size:1.6rem;">Dashcam for {{ $dashcam->vehicle->plate_number ?? 'N/A' }}</h2>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 rounded-md p-3" style="background-color:#DFF0D8; border:1px solid #1e6031;">
                <p class="text-sm font-medium" style="color:#1e6031;">{{ session('success') }}</p>
            </div>
        @endif

        <div class="dc-card p-6" id="dc-print-area">
            <div class="grid md:grid-cols-2 gap-8 items-center">

                {{-- QR (scan this) --}}
                <div class="flex flex-col items-center">
                    <div id="dc-qr" class="p-3 bg-white rounded-lg border border-gray-200"></div>
                    <p class="text-xs text-gray-500 mt-3">Scan with the dashcam app</p>
                    <div class="flex gap-2 mt-3 no-print">
                        <button type="button" id="dc-download" class="dc-btn"><i class="fas fa-download"></i> Download PNG</button>
                        <button type="button" onclick="window.print()" class="dc-btn"><i class="fas fa-print"></i> Print</button>
                    </div>
                </div>

                {{-- CODE (type this) --}}
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-gray-500">Manual code</p>
                    <div class="mt-1 flex items-center gap-3">
                        <span class="dc-code text-2xl" id="dc-code-text">{{ $dashcam->code }}</span>
                        <button type="button" id="dc-copy" class="dc-btn" title="Copy code"><i class="fas fa-copy"></i> Copy</button>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">Type this on the dashcam app if the camera can't scan the QR. Dashes are optional.</p>

                    <dl class="mt-6 space-y-2 text-sm">
                        <div class="flex justify-between border-b border-gray-100 pb-2">
                            <dt class="text-gray-500">Vehicle</dt>
                            <dd class="font-semibold">{{ $dashcam->vehicle->plate_number ?? 'N/A' }} {{ $dashcam->vehicle->model ? '· '.$dashcam->vehicle->model : '' }}</dd>
                        </div>
                        <div class="flex justify-between border-b border-gray-100 pb-2">
                            <dt class="text-gray-500">Paired</dt>
                            <dd class="font-semibold">{{ $dashcam->paired_at ? $dashcam->paired_at->format('M d, Y h:i A') : 'Not yet' }}</dd>
                        </div>
                        <div class="flex justify-between border-b border-gray-100 pb-2">
                            <dt class="text-gray-500">Last seen</dt>
                            <dd class="font-semibold">{{ $dashcam->last_seen_at ? $dashcam->last_seen_at->diffForHumans() : 'Never' }}</dd>
                        </div>
                    </dl>

                    <form method="POST" action="{{ route('motorpool.dashcams.regenerate', $dashcam) }}" class="mt-6"
                          onsubmit="return confirm('Generate a new code? The old QR and code will stop working immediately.');">
                        @csrf
                        <button type="submit" class="dc-btn"><i class="fas fa-rotate"></i> Generate new code</button>
                    </form>
                </div>

            </div>
        </div>

    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const code = @json($dashcam->code);
        const holder = document.getElementById('dc-qr');

        // The QR encodes the SAME code string as the manual entry box.
        new QRCode(holder, {
            text: code,
            width: 240,
            height: 240,
            colorDark: '#000000',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.H
        });

        document.getElementById('dc-download').addEventListener('click', function () {
            const canvas = holder.querySelector('canvas');
            if (!canvas) return;
            const link = document.createElement('a');
            link.href = canvas.toDataURL('image/png');
            link.download = code + '.png';
            link.click();
        });

        document.getElementById('dc-copy').addEventListener('click', function () {
            navigator.clipboard.writeText(code).then(() => {
                this.innerHTML = '<i class="fas fa-check"></i> Copied';
                setTimeout(() => this.innerHTML = '<i class="fas fa-copy"></i> Copy', 1500);
            });
        });
    });
</script>
@endsection
