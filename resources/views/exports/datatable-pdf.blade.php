<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 28px 24px 40px; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 8px; color: #18181b; }
        h1 { font-size: 13px; margin: 0 0 2px; }
        .meta { color: #71717a; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th { background: #f4f4f5; text-align: left; font-weight: bold; }
        th, td { border: 1px solid #d4d4d8; padding: 4px 5px; vertical-align: top; word-wrap: break-word; }
        tbody tr:nth-child(even) td { background: #fafafa; }
        .footer { position: fixed; bottom: -26px; left: 0; right: 0; text-align: right; color: #71717a; }
        .footer .page:before { content: "Halaman " counter(page); }
    </style>
</head>
<body>
    <div class="footer"><span class="page"></span></div>

    <h1>{{ $title }}</h1>
    <div class="meta">
        {{ __('Dicetak') }}: {{ $generatedAt }} &middot; {{ number_format(count($rows), 0, ',', '.') }} {{ __('data') }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 24px;">No</th>
                @foreach ($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    @foreach ($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>