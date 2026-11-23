<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Смета</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            font-family: "DejaVu Sans", sans-serif;
            color: #333;
        }

        body {
            color: #333;
            margin: 20px;
            line-height: 1.6;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .header {
            margin-bottom: 30px;
            width: 100%;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }

        .header-table td {
            border: none;
            padding: 0;
            vertical-align: top;
        }

        .header-info {
            width: 70%;
        }

        .header-logo {
            width: 30%;
            text-align: right;
            padding-left: 20px;
        }

        .header-logo img {
            max-width: 255px;
            height: auto;
        }

        .project-info {
            color: #666;
            margin-bottom: 10px;
        }

        .comment {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f5f5f5;
            border-radius: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            background-color: #f0f0f0;
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
            font-weight: bold;
        }

        td {
            padding: 10px;
            border: 1px solid #ddd;
        }

        td.image-cell {
            padding: 2px;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .center {
            text-align: center;
        }

        .product-cell {
            width: 50%;
        }

        .description {
            font-size: 10px;
            color: #777;
            margin-top: 1px;
        }

        .product-image {
            max-width: 48px;
            max-height: 48px;
            width: auto;
            height: auto;
            object-fit: contain;
            border: 1px solid #ddd;
            border-radius: 3px;
            display: block;
        }

        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        .total td {
            font-weight: bold;
            background-color: #f0f0f0;
            font-size: 13px;
        }

        th, td {
            font-size: 11px;
        }

        td {
            padding: 5px 6px;
        }

        th {
            padding: 6px;
        }

        .num {
            text-align: right;
            white-space: nowrap;
        }

        .neg {
            color: #c0392b;
        }

        .muted {
            color: #999;
            font-weight: normal;
        }

        .warn {
            color: #b9770e;
        }

        tr.section td {
            background-color: #e9ecef;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
            color: #555;
        }
    </style>
</head>

<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="header-info">
                    <h1>Смета по электрике: {{ $productSet->name }}</h1>
                    <div class="project-info">Проект: <strong>{{ $productSet->project->name }}</strong></div>
                    <div class="project-info">Дата: <strong>{{ now()->format('d.m.Y') }}</strong></div>

                    @if ($productSet->comment)
                    <div class="comment">
                        <strong>Комментарий:</strong><br>
                        {{ $productSet->comment }}
                    </div>
                    @endif
                </td>
                <td class="header-logo">
                    <img src="{{ public_path('images/logo.png') }}" alt="Логотип">
                </td>
            </tr>
        </table>
    </div>

    @php
        // Сводка «докупить» шлёт плоский список без разделов — он идёт одним разделом без заголовка.
        $sections ??= collect([['title' => null, 'items' => $items, 'total' => null]]);
        $priceOf = fn ($item) => $item->product ? (float) $item->product->approximate_price : 0.0;
        $amountOf = fn ($item) => round($item->quantity * $priceOf($item), 2);
        foreach ($sections as $i => $s) {
            if ($s['total'] === null) {
                $s['total'] = round($s['items']->sum($amountOf), 2);
                $sections[$i] = $s;
            }
        }
        $total ??= round($sections->sum('total'), 2);
        $unpriced ??= $sections->flatMap(fn ($s) => $s['items'])->filter(fn ($item) => ! $priceOf($item))->count();
        $qty = fn ($v) => rtrim(rtrim(number_format($v, 2, ',', ' '), '0'), ',');
        // ₸ в DejaVu Sans есть не во всех сборках dompdf — «тг» печатается всегда.
        $money = fn ($v) => number_format($v, fmod((float) $v, 1) == 0.0 ? 0 : 2, ',', ' ') . ' тг';
        $n = 0;
    @endphp
    @if ($sections->sum(fn ($s) => $s['items']->count()) > 0)
    <table>
        <thead>
            <tr>
                <th style="width: 5%;" class="center">№</th>
                <th style="width: 9%;" class="center">Фото</th>
                <th style="width: 42%;">Товар</th>
                <th style="width: 10%;" class="num">Кол-во</th>
                <th style="width: 7%;" class="center">Ед.</th>
                <th style="width: 12%;" class="num">Цена</th>
                <th style="width: 15%;" class="num">Сумма</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sections as $section)
            @if ($section['title'] && $sections->count() > 1)
            <tr class="section">
                <td colspan="6">{{ $section['title'] }}</td>
                <td class="num">{{ $money($section['total']) }}</td>
            </tr>
            @endif
            @foreach ($section['items'] as $item)
            @php $n++; $price = $priceOf($item); @endphp
            <tr>
                <td class="center muted">{{ $n }}</td>
                <td class="center image-cell">
                    @if ($item->product && ($item->product->imageBase64 ?? null))
                    <img src="{{ $item->product->imageBase64 }}" alt="{{ $item->product->name }}" class="product-image">
                    @endif
                </td>
                <td class="product-cell">
                    @if ($item->product)
                    <div>{{ $item->product->name }}</div>
                    @else
                    <div class="warn">без товара</div>
                    @endif
                    @if ($item->comment)
                    <div class="description">{{ $item->comment }}</div>
                    @endif
                </td>
                <td class="num {{ $item->quantity < 0 ? 'neg' : '' }}">{{ $qty($item->quantity) }}</td>
                <td class="center">{{ $item->product?->unit ?? '—' }}</td>
                <td class="num">{{ $price ? $money($price) : '—' }}</td>
                <td class="num {{ $item->quantity < 0 ? 'neg' : '' }}">{{ $price ? $money($amountOf($item)) : 'нет цены' }}</td>
            </tr>
            @endforeach
            @endforeach
            <tr class="total">
                <td colspan="6" class="num">
                    Итого: {{ $n }} поз.
                    @if ($unpriced)
                    <span class="muted">(без цены, не вошли в сумму: {{ $unpriced }})</span>
                    @endif
                </td>
                <td class="num">{{ $money($total) }}</td>
            </tr>
        </tbody>
    </table>
    @else
    <p>В смете нет товаров.</p>
    @endif

    <div class="footer">
        <p>Документ создан: {{ now()->format('d.m.Y H:i') }}</p>
    </div>
</body>

</html>