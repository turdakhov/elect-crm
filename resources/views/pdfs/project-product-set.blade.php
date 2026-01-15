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
            font-size: 12px;
            color: #666;
            margin-top: 3px;
            font-style: italic;
        }

        .product-image {
            max-width: 100px;
            max-height: 100px;
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

        .total {
            font-weight: bold;
            background-color: #f0f0f0;
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

    @if ($items->count() > 0)
    <table>
        <thead>
            <tr>
                <th style="width: 6%;" class="center">№</th>
                <th style="width: 12%;" class="center">Фото</th>
                <th style="width: 44%;">Товар</th>
                <th style="width: 12%;" class="center">Кол-во</th>
                <th style="width: 8%;" class="center">Ед.изм.</th>
                <th style="width: 18%;" class="center">Комментарий</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $index => $item)
            <tr>
                <td class="center">{{ $index + 1 }}</td>
                <td class="center image-cell">
                    @if ($item->product->imageBase64)
                    <img src="{{ $item->product->imageBase64 }}" alt="{{ $item->product->name }}" class="product-image">
                    @else
                    —
                    @endif
                </td>
                <td class="product-cell">
                    <div>{{ $item->product->name }}</div>
                    @if ($item->product->description)
                    <div class="description">{{ $item->product->description }}</div>
                    @endif
                </td>
                <td class="center">{{ $item->quantity }}</td>
                <td class="center">{{ $item->product->unit ?? '—' }}</td>
                <td>{{ $item->comment ?? '—' }}</td>
            </tr>
            @endforeach
            <tr class="total">
                <td></td>
                <td class="image-cell"></td>
                <td><strong>Итого: {{ $items->count() }} товаров</strong></td>
                <td></td>
                <td></td>
                <td></td>
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