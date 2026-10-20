<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 18px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>Exportacion {{ $resource }}</h1>
    @if ($truncated ?? false)
    <p style="color: #b91c1c; font-size: 11px; margin-bottom: 8px;">
        ⚠ Este PDF muestra los primeros {{ $limit }} registros. Para el conjunto completo, exporte en formato CSV.
    </p>
    @endif
    <table>
        <thead>
            <tr>
                @foreach ($columns as $column)
                    <th>{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    @if ($rowMapper ?? null)
                        @foreach (call_user_func($rowMapper, $row) as $value)
                            <td>{{ $value }}</td>
                        @endforeach
                    @else
                        @foreach ($columns as $column)
                            <td>{{ $row->{$column} }}</td>
                        @endforeach
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
