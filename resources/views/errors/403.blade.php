<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access Denied - MPIS</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f3f4f6; margin: 0; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: #fff; border-radius: 12px; padding: 2.5rem; max-width: 480px; text-align: center; box-shadow: 0 4px 16px rgba(0,0,0,.08); }
        h1 { color: #b91c1c; margin: 0 0 .5rem; font-size: 1.75rem; }
        p { color: #374151; margin: .5rem 0 1.5rem; }
        a { display: inline-block; padding: .6rem 1.2rem; background: #1f2937; color: #fff; border-radius: 8px; text-decoration: none; }
    </style>
</head>
<body>
    <div class="card">
        <h1>403 - Access Denied</h1>
        <p>{{ $exception->getMessage() ?: 'You do not have permission to view this page.' }}</p>
        <a href="{{ url('/') }}">Go back</a>
    </div>
</body>
</html>
