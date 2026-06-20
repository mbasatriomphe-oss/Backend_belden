<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapport Stock</title>
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
            <img src="{{ asset('logo.png') }}" alt="logo" style="height:50px;object-fit:contain" onerror="this.style.display='none'" />
        </div>
    </div>

    @php
        $totalValue = 0;
        foreach($produits as $p) {
            $row = (array) $p;
            $qty = $row['stock_actuel'] ?? $row['quantite'] ?? $row['qte'] ?? 0;
            $price = $row['prix_unitaire'] ?? $row['prix'] ?? $row['cost'] ?? 0;
            $totalValue += (float)$qty * (float)$price;
        }
    @endphp

    <h1>Rapport - Stock</h1>
    <div style="margin:8px 0"><strong>Valeur totale stock:</strong> {{ number_format($totalValue, 2) }}</div>

    <table>
        <thead>
        @if(count($produits) > 0)
            @php $first = (array) $produits->first(); @endphp
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
        @foreach($produits as $p)
            @php $row = (array) $p; @endphp
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
