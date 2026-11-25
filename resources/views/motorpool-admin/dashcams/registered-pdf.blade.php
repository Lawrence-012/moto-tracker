<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { color: #1e6031; font-size: 16px; margin: 0 0 4px 0; }
        .sub { color: #6b7280; font-size: 10px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #1e6031; color: #ffffff; text-align: left; padding: 7px; font-size: 10px; text-transform: uppercase; }
        td { border-bottom: 1px solid #e5e7eb; padding: 7px; vertical-align: middle; }
        .code { font-family: DejaVu Sans Mono, monospace; font-weight: bold; font-size: 12px; }
        .muted { color: #6b7280; }
        .qr { width: 90px; height: 90px; }
    </style>
</head>
<body>
    <h1>Registered Dashcams</h1>
    <div class="sub">Total: {{ $registered->count() }} &middot; Generated {{ now()->format('M d, Y h:i A') }}</div>

    <table>
        <thead>
            <tr>
                <th style="width: 35%;">Vehicle</th>
                <th style="width: 30%;">Dashcam Code</th>
                <th style="width: 35%;">QR</th>
            </tr>
        </thead>
        <tbody>
            @forelse($registered as $dashcam)
                <tr>
                    <td>
                        <strong>{{ $dashcam->vehicle->plate_number }}</strong><br>
                        <span class="muted">{{ $dashcam->vehicle->model }}</span>
                    </td>
                    <td class="code">{{ $dashcam->code }}</td>
                    <td>
                        <img class="qr" src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&amp;data={{ urlencode($dashcam->code) }}" alt="QR {{ $dashcam->code }}">
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align: center; padding: 14px;" class="muted">No registered dashcams.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
