<?php

use App\Models\{Cable, CableItem, Pipe, Project};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    private const COLUMN_COUNT = 9;

    public Project $project;
    public $file;
    public int $importedCount = 0;

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    public function import(): void
    {
        $this->resetValidation();

        $this->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ], [
            'file.required' => 'Выберите CSV файл.',
            'file.mimes' => 'Файл должен быть в формате CSV.',
            'file.max' => 'Размер файла не должен превышать 10 МБ.',
        ]);

        $path = $this->file->getRealPath();
        $handle = $path ? fopen($path, 'r') : false;

        if ($handle === false) {
            $this->addError('file', 'Не удалось открыть файл.');

            return;
        }

        $headerLine = fgets($handle);

        if ($headerLine === false) {
            fclose($handle);
            $this->addError('file', 'В файле не найдена строка заголовков.');

            return;
        }

        $headerLine = preg_replace('/^\xEF\xBB\xBF/', '', $headerLine);
        $delimiter = substr_count($headerLine, ';') >= substr_count($headerLine, ',') ? ';' : ',';

        if (count(str_getcsv($headerLine, $delimiter)) < self::COLUMN_COUNT) {
            fclose($handle);
            $this->addError('file', 'В файле должно быть '.self::COLUMN_COUNT.' столбцов: этаж, комната, название, код, кабель, кол-во, длина кабеля, гофра, длина гофры.');

            return;
        }

        $cablesByName = $this->catalogByName(Cable::query()->pluck('id', 'name'));
        $pipesByName = $this->catalogByName(Pipe::query()->pluck('id', 'name'));

        $entries = [];
        $errors = [];
        $missingCables = [];
        $missingPipes = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNumber++;

            if (! $this->rowHasData($row)) {
                continue;
            }

            [$floor, $room, $name, $code, $cableName, $countRaw, $cableLengthRaw, $pipeName, $pipeLengthRaw] = array_map(
                fn ($value) => trim((string) $value),
                array_pad(array_slice($row, 0, self::COLUMN_COUNT), self::COLUMN_COUNT, null)
            );

            if ($floor === '' || $room === '' || $name === '') {
                $errors[] = "Строка {$rowNumber}: заполните этаж, комнату и название.";

                continue;
            }

            $cableId = $cablesByName->get($this->normalizeName($cableName));
            $pipeId = $pipesByName->get($this->normalizeName($pipeName));

            if ($cableName === '') {
                $errors[] = "Строка {$rowNumber}: не указан кабель.";
            } elseif (! $cableId) {
                $missingCables[$cableName] = true;
            }

            if ($pipeName === '') {
                $errors[] = "Строка {$rowNumber}: не указана гофра.";
            } elseif (! $pipeId) {
                $missingPipes[$pipeName] = true;
            }

            $cableCount = $countRaw === '' ? 1 : filter_var($countRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);

            if ($cableCount === false) {
                $errors[] = "Строка {$rowNumber}: некорректное количество кабелей.";
            }

            $cableLength = $this->parseDecimal($cableLengthRaw);
            $pipeLength = $pipeLengthRaw === '' ? 0.0 : $this->parseDecimal($pipeLengthRaw);

            if ($cableLength === null) {
                $errors[] = "Строка {$rowNumber}: некорректная длина кабеля.";
            }

            if ($pipeLength === null) {
                $errors[] = "Строка {$rowNumber}: некорректная длина гофры.";
            }

            if (! $cableId || ! $pipeId || $cableCount === false || $cableLength === null || $pipeLength === null) {
                continue;
            }

            $entries[] = [
                'project_id' => $this->project->id,
                'floor' => $floor,
                'room' => $room,
                'name' => $name,
                'code' => $code !== '' ? $code : null,
                'cable_id' => $cableId,
                'cable_count' => $cableCount,
                'pipe_id' => $pipeId,
                'cable_length' => $cableLength,
                'pipe_length' => $pipeLength,
            ];
        }

        fclose($handle);

        if ($missingCables) {
            array_unshift($errors, 'Кабели не найдены в справочнике: '.implode(', ', array_keys($missingCables)).'.');
        }

        if ($missingPipes) {
            array_unshift($errors, 'Гофры не найдены в справочнике: '.implode(', ', array_keys($missingPipes)).'.');
        }

        if ($errors) {
            $this->addError('file', implode(' ', $errors));

            return;
        }

        if (! $entries) {
            $this->addError('file', 'В файле нет данных для импорта.');

            return;
        }

        DB::transaction(function () use ($entries): void {
            foreach ($entries as $entry) {
                CableItem::create($entry);
            }
        });

        $this->importedCount = count($entries);
        $this->reset('file');

        session()->flash('message', "Импортировано позиций: {$this->importedCount}.");
    }

    /**
     * @param  Collection<string, int>  $catalog
     * @return Collection<string, int>
     */
    protected function catalogByName(Collection $catalog): Collection
    {
        return $catalog->mapWithKeys(fn (int $id, string $name) => [$this->normalizeName($name) => $id]);
    }

    protected function normalizeName(string $name): string
    {
        $name = mb_strtolower(trim($name));
        $name = str_replace('ё', 'е', $name);

        return (string) preg_replace('/\s+/u', ' ', $name);
    }

    protected function parseDecimal(string $value): ?float
    {
        $normalized = str_replace(',', '.', (string) preg_replace('/\s+/u', '', $value));

        if ($normalized === '' || ! is_numeric($normalized) || (float) $normalized < 0) {
            return null;
        }

        return (float) $normalized;
    }

    protected function rowHasData(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <div class="relative overflow-x-auto">
                <div class="space-y-6 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold">Импорт кабелей из CSV</h1>
                            <p class="text-gray-600 dark:text-gray-400">{{ $project->name }}</p>
                        </div>
                        <flux:button variant="filled" icon="arrow-left" :href="route('projects.cable-items.index', $project)">Назад</flux:button>
                    </div>

                    <flux:callout icon="information-circle">
                        <flux:callout.heading>Формат файла</flux:callout.heading>
                        <div class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                            <div>Разделитель: точка с запятой (;) или запятая (,), кодировка: UTF-8. Первая строка — заголовки.</div>
                            <div>Столбцы по порядку: этаж, комната, название, код (необязательно), кабель, кол-во кабелей в гофре, длина кабеля (м), гофра, длина гофры (м).</div>
                            <div>Кабель и гофра должны совпадать с названиями в справочниках (регистр и ё/е не важны).</div>
                            <div>Пустое кол-во считается как 1. Если в файле есть ошибки, ничего не импортируется.</div>
                        </div>
                    </flux:callout>

                    @if (session()->has('message'))
                    <flux:callout icon="check-circle">
                        <flux:callout.heading>{{ session('message') }}</flux:callout.heading>
                    </flux:callout>
                    @endif

                    <form wire:submit="import" class="space-y-6">
                        <div>
                            <label for="file" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">CSV файл</label>
                            <input
                                id="file"
                                type="file"
                                accept=".csv,text/csv"
                                wire:model="file"
                                class="bg-gray-50 border {{ $errors->has('file') ? 'border-red-500' : 'border-gray-300' }} text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            />
                            @error('file')
                            <div role="alert" aria-live="polite" aria-atomic="true" class="mt-3 text-sm font-medium text-red-500 dark:text-red-400">
                                {{ $message }}
                            </div>
                            @enderror
                            <div class="mt-2 text-xs text-gray-500 dark:text-gray-400" wire:loading wire:target="file">Загрузка файла...</div>
                        </div>

                        <div class="flex gap-2">
                            <flux:button variant="primary" color="blue" icon="arrow-up-tray" type="submit" wire:loading.attr="disabled" wire:target="import,file">Импортировать</flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
