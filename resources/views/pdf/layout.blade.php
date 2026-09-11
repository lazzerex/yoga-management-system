<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <title>{{ $documentTitle }}</title>
    <style>
        /* DejaVu ships with dompdf and carries the Vietnamese diacritics; a webfont would
           not load here, so the interface font is deliberately not reused. */
        * { font-family: 'DejaVu Sans', sans-serif; }

        @page { margin: 28mm 16mm 22mm; }

        body { margin: 0; font-size: 10px; color: #2b3a4a; }

        .head { border-bottom: 1.5px solid #2f8368; padding-bottom: 10px; margin-bottom: 18px; }
        .centre { font-size: 9px; letter-spacing: 1px; text-transform: uppercase; color: #55637a; }
        .doc-title { font-size: 19px; font-weight: bold; color: #1f5c4a; margin: 4px 0 2px; }
        .doc-sub { font-size: 10px; color: #55637a; }

        .meta { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .meta td { padding: 2px 0; vertical-align: top; }
        .meta .label { color: #7c8798; width: 28%; }

        h2 { font-size: 11px; text-transform: uppercase; letter-spacing: 0.8px; color: #55637a;
             border-bottom: 1px solid #e4e9f0; padding-bottom: 4px; margin: 18px 0 8px; }

        table.grid { width: 100%; border-collapse: collapse; }
        table.grid th { background: #f2f7f5; color: #1f5c4a; font-size: 9px; text-transform: uppercase;
                        letter-spacing: 0.5px; text-align: left; padding: 6px 8px; border-bottom: 1px solid #d3e5dd; }
        table.grid td { padding: 6px 8px; border-bottom: 1px solid #eef1f5; }
        table.grid tr:nth-child(even) td { background: #fafbfc; }

        .num { text-align: right; }
        .muted { color: #7c8798; }
        .strong { font-weight: bold; color: #16202b; }
        .empty { padding: 10px 0; color: #7c8798; font-style: italic; }

        .tag { display: inline-block; padding: 2px 7px; border-radius: 9px; font-size: 9px;
               border: 1px solid #d3dbe6; background: #f8fafb; }
        .tag-paid { border-color: #bcd9cb; background: #eef5f2; color: #1f5c4a; }
        .tag-due { border-color: #ecdca3; background: #fdf5e6; color: #8a6414; }
        .tag-void { border-color: #e6c5c9; background: #fceef0; color: #8f3038; }

        .foot { position: fixed; bottom: -14mm; left: 0; right: 0;
                font-size: 8px; color: #9aa4b2; border-top: 1px solid #e4e9f0; padding-top: 5px; }
    </style>
</head>
<body>
    <div class="head">
        <div class="centre">{{ $centreName }}</div>
        <div class="doc-title">{{ $documentTitle }}</div>
        @isset($documentSubtitle)
            <div class="doc-sub">{{ $documentSubtitle }}</div>
        @endisset
    </div>

    @yield('content')

    <div class="foot">
        {{ $centreName }} · {{ __('pdf.generatedOn', ['date' => $generatedAt]) }}
    </div>
</body>
</html>
