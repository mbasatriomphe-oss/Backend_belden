<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapport Ventes</title>
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #444; padding: 6px; font-size: 12px; }
        th { background: #eee; }
    </style>
</head>
<body>
    <div style="display:flex;justify-content:space-between;align-items:center;">
        <div>
            <h2>{{ config('app.name', 'Belden') }}</h2>
            <div>Généré le: {{ date('Y-m-d H:i') }}</div>
        </div>
        <div>
            <!-- logo placeholder: place a logo file at public/logo.png to replace -->
            <img src="{{ asset('logo.png') }}" alt="logo" style="height:50px;object-fit:contain" onerror="this.style.display='none'" />
        </div>
    </div>

    @php
        $count = $ventes->count();
        $keys = $count ? array_keys((array) $ventes->first()) : [];
        $amountField = null;
        foreach (['montant_total','total','prix_total','amount','montant'] as $k) {
            if (in_array($k, $keys)) { $amountField = $k; break; }
        }
        $totalAmount = $amountField ? $ventes->sum($amountField) : null;
    @endphp

    <h1>Rapport - Ventes</h1>

    <div style="margin:8px 0">
        <strong>Nombre de ventes:</strong> {{ $count }}
        @if($totalAmount !== null)
            &nbsp;|&nbsp; <strong>Total:</strong> {{ number_format($totalAmount, 2) }}
        @endif
    </div>

    <table>
        <thead>
        @if(count($ventes) > 0)
            @php $first = (array) $ventes->first(); @endphp
            <tr>
                @foreach(array_keys($first) as $col)
                    <th>{{ $col }}</th>
                @endforeach
            </tr>
        @else
            <tr><th>Aucune donnée</th></tr>
        @endif
        </thead>
        <tbody>
        @foreach($ventes as $v)
            @php $row = (array) $v; @endphp
            <tr>
                @foreach($row as $cell)
                    <td>{{ $cell }}</td>
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
