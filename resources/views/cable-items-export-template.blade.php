<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="utf-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "DejaVu Sans", sans-serif;
            width: 100%;
            height: 100%;
            padding: 20px;
        }

        .project-title {
            text-align: center;
            font-size: 40px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .cable-group {
            margin-bottom: 30px;
        }

        .cable-group-header {
            font-size: 45px;
            font-weight: bold;
            margin-bottom: 10px;
            padding: 5px;
            background-color: #fff;
            border-radius: 3px;
        }

        .cells-grid {
            font-size: 0;
            margin-bottom: 15px;
        }

        .cells-row {
            font-size: 0;
            white-space: nowrap;
        }

        .cell {
            border: 1px solid #333;
            padding: 4px 4px 0 4px;
            min-height: auto;
            display: inline-block;
            vertical-align: top;
            width: 6.8%;
            margin: 0;
            font-size: 10px;
            position: relative;
            background-color: #fff;
        }

        .cell-room {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 0;
            text-align: center;
            text-transform: uppercase;
            line-height: 1;
        }

        .cell-floor {
            font-size: 10px;
            text-align: right;
            line-height: 1;
        }

        .cell-name {
            font-size: 14px;
            margin-bottom: 0;
            text-align: center;
            text-transform: uppercase;
            line-height: 1;
        }

        .cell-code {
            text-transform: none;
        }

        .cell-info {
            font-size: 0;
            text-align: center;
            line-height: 1;
            border-top: 1px solid #ddd;
            padding-top: 6px;
            margin-bottom: -4px;
            padding-bottom: 0;
        }

        .cell-info-row {
            display: inline-block;
            width: 31%;
            margin: 0;
            text-align: center;
            word-wrap: break-word;
            font-size: 20px;
            line-height: 1;
            font-weight: bold;
        }

        .cell-length {
            font-size: 24px;
            font-weight: bold;
        }

        .cell-count {
            font-size: 14px;
            font-weight: bold;
        }

        .cell-bottom {
            font-size: 0;
            text-align: center;
            margin-top: 2px;
        }

        .cell-bottom-item {
            display: inline-block;
            width: 48%;
            font-size: 9px;
            text-align: center;
            word-wrap: break-word;
            line-height: 1;
        }
    </style>
</head>

<body>
    <div class="project-title">
        Кабели проекта "{{ $projectName }}" - {{ $floorLabel }}
    </div>

    @forelse($groupedByCable as $cableId => $items)
    <div class="cable-group">
        <div class="cable-group-header">
            Кабель: {{ $items->first()->cable->name }}
        </div>
        <div class="cells-grid">
            @foreach($items->chunk(14) as $row)
            @for ($repeat = 0; $repeat < 2; $repeat++)
                <div class="cells-row">
                @foreach($row as $item)
                <div class="cell">
                    <div class="cell-room">{{ $item->room }}</div>
                    <table width="100%" cellspacing="0" cellpadding="0" style="font-size: 10px; line-height: 1;">
                        <tr>
                            <td align="left">{{ $item->floor }}</td>
                            <td align="right">{{ $item->pipe?->name ?? '' }}</td>
                        </tr>
                    </table>
                    <div class="cell-name">{{ Str::limit($item->name, 24, '') }}@if($item->code) <span class="cell-code">{{ $item->code }}</span>@endif</div>
                    <div class="cell-info">
                        @if($item->cable)
                        <div class="cell-info-row">
                            {{ $item->cable->name }}
                        </div>
                        @endif
                        <div class="cell-info-row">
                            <span class="cell-length">{{ number_format((float)($item->cable_length ?? 0), 1, '.', '') }}</span>@if($item->cable_count > 1)<span class="cell-count">x{{ $item->cable_count }}</span>@endif
                        </div>
                        @if($item->pipe)
                        <div class="cell-info-row">
                            *{{ number_format((float)($item->pipe_length ?? 0), 1, '.', '') }}*
                        </div>
                        @endif
                    </div>
                    <div class="cell-bottom">
                        <div class="cell-bottom-item">{{ Str::limit($item->name, 20, '') }}</div>
                        <div class="cell-bottom-item">{{ Str::limit($item->name, 20, '') }}</div>
                    </div>
                </div>
                @endforeach
        </div>
        @endfor
        @endforeach
    </div>
    </div>
    @empty
    <p>Нет данных для экспорта</p>
    @endforelse
</body>

</html>