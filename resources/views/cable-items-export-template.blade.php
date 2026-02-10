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
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .cable-group {
            margin-bottom: 30px;
        }

        .cable-group-header {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 10px;
            padding: 5px;
            background-color: #f0f0f0;
            border-radius: 3px;
        }

        .cells-grid {
            display: grid;
            grid-template-columns: repeat(14, 1fr);
            gap: 8px;
            margin-bottom: 15px;
        }

        .cell {
            border: 1px solid #333;
            padding: 8px;
            min-height: 100px;
            display: flex;
            flex-direction: column;
            font-size: 10px;
            position: relative;
            background-color: #fff;
        }

        .cell-room {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 4px;
            flex-grow: 1;
        }

        .cell-floor {
            position: absolute;
            top: 4px;
            right: 4px;
            font-size: 8px;
            color: #666;
        }

        .cell-info {
            font-size: 9px;
            line-height: 1.3;
            border-top: 1px solid #ddd;
            padding-top: 4px;
            margin-top: 4px;
        }

        .cell-info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }
    </style>
</head>
<body>
    <div class="project-title">
        {{ $projectName }} - Кабели и гофры
    </div>

    @forelse($groupedByCable as $cableId => $items)
        <div class="cable-group">
            <div class="cable-group-header">
                @if($cableId && $items->first()?->cable)
                    Кабель: {{ $items->first()?->cable->name }}
                @else
                    Без кабеля
                @endif
            </div>
            <div class="cells-grid">
                @foreach($items as $item)
                    <div class="cell">
                        <div class="cell-floor">{{ $item->floor }}</div>
                        <div class="cell-room">{{ $item->room }}</div>
                        <div class="cell-info">
                            @if($item->cable)
                                <div class="cell-info-row">
                                    <span>Кабель:</span>
                                    <strong>{{ $item->cable->name }}</strong>
                                </div>
                            @endif
                            @if($item->cable_length)
                                <div class="cell-info-row">
                                    <span>Длина:</span>
                                    <strong>{{ $item->cable_length }} м</strong>
                                </div>
                            @endif
                            @if($item->pipe && $item->pipe_length)
                                <div class="cell-info-row">
                                    <span>Гофра:</span>
                                    <strong>{{ $item->pipe_length }} м</strong>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <p>Нет данных для экспорта</p>
    @endforelse
</body>
</html>
