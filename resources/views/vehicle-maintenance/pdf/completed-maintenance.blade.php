<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Completed Maintenance Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            margin: 0;
            padding: 0;
        }
        .header {
            margin-bottom: 10px;
            position: relative;
           padding-top: 12mm;
        }
        .logo {
            position: absolute;
            left: 50%;
            margin-left: -70px;
            top: 0;
            width: 30mm;
            height: 30mm;
        }
        .header-content {
            text-align: center;
           padding-top: 2mm;
        }
        .header-content h2 {
            margin: 2px 0;
            font-size: 10px;
            font-weight: normal;
        }
        .header-content h3 {
            margin: 2px 0;
            font-size: 14px;
            font-weight: bold;
        }
        .header-content h4 {
            margin: 2px 0;
            font-size: 10px;
            font-weight: normal;
        }
        .header-content h5 {
            margin: 6px 0 0 0;
            font-size: 11px;
            font-weight: bold;
            color: #004d00;
        }
        .line {
            border-top: 2px solid #004d00;
            margin: 8px 0 10px 0;
        }
        .title {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            margin: 15px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #008000;
            color: white;
            font-weight: bold;
            text-align: center;
            padding: 6px 4px;
            font-size: 9px;
            border: 1px solid #006600;
        }
        td {
            padding: 5px 4px;
            font-size: 9px;
            border: 1px solid #ddd;
            text-align: center;
            vertical-align: middle;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: right;
            font-size: 9px;
            padding-right: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('assets/images/clsu-logo.png') }}" alt="Logo" class="logo" />
        <div class="header-content">
            <h2>Republic of the Philippines</h2>
            <h3>CENTRAL LUZON STATE UNIVERSITY</h3>
            <h4>Science City of Muñoz, Nueva Ecija</h4>
            <h5>TRANSPORTATION SERVICES</h5>
        </div>
    </div>
    <div class="line"></div>
    
    <div class="title">VEHICLE MAINTENANCE HISTORY REPORT</div>
    
    @if($records->isEmpty())
        <p style="text-align: center; font-size: 12px; font-weight: bold; margin-top: 20px;">No Completed Maintenance Records Found</p>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width: 15px;">No.</th>
                    <th style="width: 40px;">Date Started</th>
                    <th style="width: 40px;">Date Completed</th>
                    <th style="width: 50px;">Maintenance Type</th>
                    <th style="width: 60px;">Make or Type</th>
                    <th style="width: 40px;">Mechanics Assigned</th>
                    <th style="width: 40px;">Conforme</th>
                </tr>
            </thead>
            <tbody>
                @foreach($records as $index => $record)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($record->date_started)->format('M d, Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($record->date_completed)->format('M d, Y') }}</td>
                    <td>{{ $record->maintenance_type ?? 'N/A' }}</td>
                    <td>{{ $record->make_or_type ?? 'N/A' }}</td>
                    <td>{{ $record->mechanics_assigned ?? 'N/A' }}</td>
                    <td>{{ $record->conforme ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    
    <div class="footer">
        Generated on: {{ date('m/d/Y h:i A') }}
    </div>
</body>
</html>
