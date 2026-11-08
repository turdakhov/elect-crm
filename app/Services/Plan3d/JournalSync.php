<?php

namespace App\Services\Plan3d;

use App\Models\Cable;
use App\Models\CableItem;
use App\Models\CableJournalVersion;
use App\Models\Pipe;
use App\Models\PlanSync;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Кабельный журнал из 3D-плана: разница с текущим и принятие новой версии.
 *
 * Строки сопоставляются по тому, что написано на НАКЛЕЙКЕ, — этаж, комната,
 * название. Код (id трассы в 3D-плане) для этого не годится: полная
 * перегенерация разводки раздаёт трассам новые id, и весь журнал показался бы
 * удалённым и добавленным заново. Код помогает только узнать переименование:
 * строка ушла, строка с тем же кодом пришла — это та же линия.
 */
class JournalSync
{
    /** Поля, по которым строка «изменилась». Все они печатаются на наклейке. */
    public const FIELDS = ['floor', 'room', 'name', 'code', 'cable', 'count', 'length', 'pipe', 'pipe_length'];

    public const FIELD_LABELS = [
        'floor' => 'этаж',
        'room' => 'комната',
        'name' => 'название',
        'code' => 'код',
        'cable' => 'кабель',
        'count' => 'кол-во',
        'length' => 'длина, м',
        'pipe' => 'гофра',
        'pipe_length' => 'гофра, м',
    ];

    public static function normalize(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));
        $value = str_replace('ё', 'е', $value);

        return (string) preg_replace('/\s+/u', ' ', $value);
    }

    /** Личность кабеля на объекте: то, что на наклейке. */
    public static function key(array $row): string
    {
        return self::normalize($row['floor'] ?? '').'|'.self::normalize($row['room'] ?? '').'|'.self::normalize($row['name'] ?? '');
    }

    /** Строка в одном виде для обеих сторон: из отправки и из базы. */
    public static function canonical(array $row): array
    {
        return [
            'floor' => trim((string) ($row['floor'] ?? '')),
            'room' => trim((string) ($row['room'] ?? '')),
            'name' => trim((string) ($row['name'] ?? '')),
            'code' => trim((string) ($row['code'] ?? '')),
            'cable' => trim((string) ($row['cable'] ?? '')),
            'count' => max(1, (int) ($row['count'] ?? 1)),
            'length' => round((float) ($row['length'] ?? 0), 2),
            'pipe' => trim((string) ($row['pipe'] ?? '')),
            'pipe_length' => round((float) ($row['pipe_length'] ?? 0), 2),
        ];
    }

    /** Текущий журнал проекта в том же виде, что и отправка. */
    public static function current(Project $project): array
    {
        return CableItem::where('project_id', $project->id)
            ->with(['cable', 'pipe'])
            ->orderBy('floor')->orderBy('room')->orderBy('name')
            ->get()
            ->map(fn (CableItem $i) => self::canonical([
                'floor' => $i->floor,
                'room' => $i->room,
                'name' => $i->name,
                'code' => $i->code,
                'cable' => $i->cable?->name,
                'count' => $i->cable_count,
                'length' => $i->cable_length,
                'pipe' => $i->pipe?->name,
                'pipe_length' => $i->pipe_length,
            ]))
            ->all();
    }

    /**
     * Почему журнал нельзя принять. Наклейка без названия или комнаты — это
     * конец кабеля, который монтажник не узнает; два одинаковых названия в
     * комнате — конец, который он подключит не туда.
     */
    public static function errors(array $rows): array
    {
        $errors = [];
        $seen = [];
        foreach ($rows as $i => $raw) {
            $row = self::canonical($raw);
            $where = 'строка '.($i + 1).($row['code'] !== '' ? ' ('.$row['code'].')' : '');
            if ($row['room'] === '') {
                $errors[] = $where.': нет комнаты';
            }
            if ($row['name'] === '') {
                $errors[] = $where.': нет названия';
            }
            if ($row['cable'] === '') {
                $errors[] = $where.': не указан кабель';
            }
            if ($row['pipe'] === '') {
                $errors[] = $where.': не указана гофра';
            }
            if ($row['room'] === '' || $row['name'] === '') {
                continue;
            }
            $key = self::key($row);
            if (isset($seen[$key])) {
                $errors[] = $where.': «'.$row['name'].'» в «'.$row['room'].'» уже есть (строка '.$seen[$key].')';
            } else {
                $seen[$key] = $i + 1;
            }
        }

        return $errors;
    }

    /**
     * Разница двух журналов.
     *
     * @return array{added: list<array>, removed: list<array>, changed: list<array{before: array, after: array, fields: list<string>}>, unchanged: int}
     */
    public static function compare(array $before, array $after): array
    {
        $old = [];
        foreach ($before as $row) {
            $row = self::canonical($row);
            $old[self::key($row)] = $row;
        }
        $new = [];
        foreach ($after as $row) {
            $row = self::canonical($row);
            $new[self::key($row)] = $row;
        }

        $changed = [];
        $unchanged = 0;
        foreach ($new as $key => $row) {
            if (! isset($old[$key])) {
                continue;
            }
            $fields = self::differences($old[$key], $row);
            if ($fields) {
                $changed[] = ['before' => $old[$key], 'after' => $row, 'fields' => $fields];
            } else {
                $unchanged++;
            }
        }

        $removed = array_diff_key($old, $new);
        $added = array_diff_key($new, $old);

        // Переименование: та же трасса (код), другая наклейка.
        $removedByCode = [];
        foreach ($removed as $key => $row) {
            if ($row['code'] !== '') {
                $removedByCode[$row['code']][] = $key;
            }
        }
        foreach ($added as $key => $row) {
            $candidates = $removedByCode[$row['code']] ?? [];
            if ($row['code'] === '' || count($candidates) !== 1) {
                continue;
            }
            $oldKey = $candidates[0];
            $changed[] = ['before' => $removed[$oldKey], 'after' => $row, 'fields' => self::differences($removed[$oldKey], $row)];
            unset($removed[$oldKey], $added[$key]);
            unset($removedByCode[$row['code']]);
        }

        return [
            'added' => array_values($added),
            'removed' => array_values($removed),
            'changed' => $changed,
            'unchanged' => $unchanged,
        ];
    }

    private static function differences(array $a, array $b): array
    {
        $fields = [];
        foreach (self::FIELDS as $field) {
            $same = in_array($field, ['cable', 'pipe', 'floor', 'room', 'name'], true)
                ? self::normalize((string) $a[$field]) === self::normalize((string) $b[$field])
                : $a[$field] == $b[$field];
            if (! $same) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    public static function diffFor(PlanSync $sync): array
    {
        return self::compare(self::current($sync->project), $sync->journal);
    }

    /**
     * Принять журнал отправки: он становится текущим журналом проекта целиком.
     *
     * Целиком, а не по этажам: журнал — вывод 3D-плана, и строка, которой там
     * больше нет, кабелем на объекте тоже не является. Что уходит, видно в
     * разнице до нажатия.
     */
    public static function accept(PlanSync $sync, User $user): CableJournalVersion
    {
        if (! $sync->hasJournal()) {
            throw new RuntimeException('У отправки щита журнала нет.');
        }
        if (! $sync->isLatest()) {
            throw new RuntimeException('Есть отправка новее — принимать надо её.');
        }
        if ($sync->journal_accepted_at) {
            throw new RuntimeException('Журнал этой отправки уже принят.');
        }
        $errors = self::errors($sync->journal);
        if ($errors) {
            throw new RuntimeException('Журнал не принят: '.implode('; ', array_slice($errors, 0, 5)));
        }

        return DB::transaction(function () use ($sync, $user) {
            $project = $sync->project;
            $rows = array_map(fn ($r) => self::canonical($r), $sync->journal);
            $changes = self::compare(self::current($project), $rows);

            $cables = self::catalog(Cable::class, array_column($rows, 'cable'));
            $pipes = self::catalog(Pipe::class, array_column($rows, 'pipe'));

            CableItem::where('project_id', $project->id)->delete();
            foreach ($rows as $row) {
                CableItem::create([
                    'project_id' => $project->id,
                    'floor' => $row['floor'] !== '' ? $row['floor'] : null,
                    'room' => $row['room'],
                    'name' => $row['name'],
                    'code' => $row['code'] !== '' ? $row['code'] : null,
                    'cable_id' => $cables[self::normalize($row['cable'])],
                    'cable_count' => $row['count'],
                    'pipe_id' => $pipes[self::normalize($row['pipe'])],
                    'cable_length' => $row['length'],
                    'pipe_length' => $row['pipe_length'],
                ]);
            }

            $sync->update(['journal_accepted_at' => now(), 'journal_accepted_by' => $user->id]);

            return CableJournalVersion::create([
                'project_id' => $project->id,
                'number' => (int) CableJournalVersion::where('project_id', $project->id)->max('number') + 1,
                'plan_sync_id' => $sync->id,
                'rows' => $rows,
                'changes' => $changes,
                'accepted_by' => $user->id,
            ]);
        });
    }

    /**
     * Справочник кабелей или гофр, дополненный недостающими именами.
     *
     * Ручной импорт CSV неизвестное имя отвергает: его набирал человек, и это
     * скорее опечатка. Имена из 3D-плана выводятся из модели и не опечатываются
     * — их заводим сами, а в разнице до принятия они видны.
     *
     * @param  class-string<Cable|Pipe>  $model
     * @return array<string, int> нормализованное имя → id
     */
    private static function catalog(string $model, array $names): array
    {
        $known = [];
        foreach ($model::query()->pluck('id', 'name') as $name => $id) {
            $known[self::normalize($name)] = $id;
        }
        foreach (array_unique($names) as $name) {
            $norm = self::normalize($name);
            if (! isset($known[$norm])) {
                $known[$norm] = $model::create(['name' => $name])->id;
            }
        }

        return $known;
    }

    /** Имена кабелей и гофр, которых нет в справочниках: их заведёт принятие. */
    public static function newCatalogNames(PlanSync $sync): array
    {
        $out = [];
        foreach (['cable' => Cable::class, 'pipe' => Pipe::class] as $field => $model) {
            $known = $model::query()->pluck('name')->map(fn ($n) => self::normalize($n))->flip();
            $missing = collect($sync->journal)->pluck($field)->filter()->unique(fn ($n) => self::normalize($n))
                ->reject(fn ($n) => $known->has(self::normalize($n)))->values()->all();
            $out[$field] = $missing;
        }

        return $out;
    }
}
