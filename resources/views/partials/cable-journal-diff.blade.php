{{-- Разница кабельного журнала. $diff — JournalSync::compare(). --}}
@php
    use App\Services\Plan3d\JournalSync;
    $fmt = fn ($v) => is_float($v) ? rtrim(rtrim(number_format($v, 2, ',', ' '), '0'), ',') : (string) $v;
    $label = fn ($r) => trim(($r['floor'] ? $r['floor'].' · ' : '').$r['room'].' · '.$r['name']);
    $line = fn ($r) => $r['cable'].($r['count'] > 1 ? ' ×'.$r['count'] : '').', '.$fmt($r['length']).' м, '.$r['pipe'].' '.$fmt($r['pipe_length']).' м'.($r['code'] !== '' ? ' · '.$r['code'] : '');
@endphp

@if (! $diff['added'] && ! $diff['removed'] && ! $diff['changed'])
    <p class="text-sm text-gray-600 dark:text-gray-400">Журнал не изменился.</p>
@else
    <div class="space-y-4 text-sm">
        @if ($diff['added'])
            <div>
                <h3 class="mb-1 font-semibold text-green-700 dark:text-green-400">Новые — нарезать и напечатать наклейки ({{ count($diff['added']) }})</h3>
                <ul class="space-y-0.5">
                    @foreach ($diff['added'] as $r)
                        <li><span class="text-gray-900 dark:text-white">{{ $label($r) }}</span> — {{ $line($r) }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($diff['changed'])
            <div>
                <h3 class="mb-1 font-semibold text-amber-600">Изменённые — перепечатать наклейки ({{ count($diff['changed']) }})</h3>
                <ul class="space-y-1">
                    @foreach ($diff['changed'] as $c)
                        <li>
                            <span class="text-gray-900 dark:text-white">{{ $label($c['after']) }}</span>:
                            @foreach ($c['fields'] as $field)
                                {{ JournalSync::FIELD_LABELS[$field] }}
                                <span class="text-red-600 line-through">{{ $fmt($c['before'][$field]) ?: '—' }}</span>
                                → <span class="text-green-700 dark:text-green-400">{{ $fmt($c['after'][$field]) ?: '—' }}</span>@if (! $loop->last); @endif
                            @endforeach
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($diff['removed'])
            <div>
                <h3 class="mb-1 font-semibold text-red-600">Пропавшие — кабель больше не нужен ({{ count($diff['removed']) }})</h3>
                <ul class="space-y-0.5">
                    @foreach ($diff['removed'] as $r)
                        <li><span class="text-gray-900 dark:text-white">{{ $label($r) }}</span> — {{ $line($r) }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endif
