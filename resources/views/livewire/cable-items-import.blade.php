<?php

use App\Models\{Cable, CableItem, Pipe, Project};
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public Project $project;
    public $file;
    public ?int $pipe_id = null;
    public int $importedCount = 0;

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    public function with(): array
    {
        return [
            'pipes' => Pipe::query()->orderBy('name')->get(),
        ];
    }

    public function import(): void
    {
        $this->resetValidation();

        $this->validate([
            'pipe_id' => 'required|exists:pipes,id',
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ], [
            'pipe_id.required' => 'Выберите гофру.',
            'pipe_id.exists' => 'Выбранная гофра не найдена.',
            'file.required' => 'Выберите CSV файл.',
            'file.mimes' => 'Файл должен быть в формате CSV.',
            'file.max' => 'Размер файла не должен превышать 10 МБ.',
        ]);

        $path = $this->file->getRealPath();

        if (! $path) {
            $this->addError('file', 'Не удалось прочитать файл.');

            return;
        }

        $handle = fopen($path, 'r');

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
        $semicolonCount = substr_count($headerLine, ';');
        $commaCount = substr_count($headerLine, ',');

        if ($semicolonCount === 0 && $commaCount === 0) {
            fclose($handle);
            $this->addError('file', 'Не удалось определить разделитель CSV.');

            return;
        }

        $delimiter = $semicolonCount >= $commaCount ? ';' : ',';
        $header = str_getcsv($headerLine, $delimiter);

        if (! is_array($header) || count($header) < 5) {
            fclose($handle);
            $this->addError('file', 'В файле не найдены заголовки с типами кабеля.');

            return;
        }

        $cablesByName = Cable::query()->pluck('id', 'name');
        $cableColumns = [];
        $missing = [];
        $headerCount = count($header);

        for ($i = 4; $i < $headerCount; $i++) {
            $cableName = trim((string) ($header[$i] ?? ''));

            if ($cableName === '') {
                continue;
            }

            $cableId = $cablesByName->get($cableName);

            if (! $cableId) {
                $missing[] = $cableName;
                continue;
            }

            $cableColumns[$i] = $cableId;
        }

        if ($missing) {
            fclose($handle);
            $this->addError('file', 'Типы кабеля не найдены: ' . implode(', ', $missing));

            return;
        }

        if (! $cableColumns) {
            fclose($handle);
            $this->addError('file', 'В файле нет заголовков с типами кабеля.');

            return;
        }

        $entries = [];
        $errors = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNumber++;

            if (! $this->rowHasData($row)) {
                continue;
            }

            $row = array_pad($row, $headerCount, null);

            $floor = trim((string) ($row[0] ?? ''));
            $room = trim((string) ($row[1] ?? ''));
            $name = trim((string) ($row[2] ?? ''));
            $pipeLengthRaw = trim((string) ($row[3] ?? ''));
            $pipeLength = $this->parseDecimal($pipeLengthRaw);

            if ($floor === '' || $room === '' || $name === '') {
                $errors[] = "Строка {$rowNumber}: заполните этаж, комнату и название.";

                continue;
            }

            if ($pipeLengthRaw !== '' && $pipeLength === null) {
                $errors[] = "Строка {$rowNumber}: некорректная длина гофры.";

                continue;
            }

            foreach ($cableColumns as $index => $cableId) {
                $cableLengthRaw = trim((string) ($row[$index] ?? ''));

                if ($cableLengthRaw === '') {
                    continue;
                }

                $cableLength = $this->parseDecimal($cableLengthRaw);

                if ($cableLength === null) {
                    $columnNumber = $index + 1;
                    $errors[] = "Строка {$rowNumber}: некорректная длина кабеля в колонке {$columnNumber}.";

                    continue;
                }

                $entries[] = [
                    'project_id' => $this->project->id,
                    'floor' => $floor,
                    'room' => $room,
                    'name' => $name,
                    'cable_id' => $cableId,
                    'pipe_id' => $this->pipe_id,
                    'cable_length' => $cableLength,
                    'pipe_length' => $pipeLength ?? 0,
                ];
            }
        }

        fclose($handle);

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

    protected function parseDecimal(?string $value): ?float
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $normalized = preg_replace('/\s+/', '', $value);
        $normalized = str_replace(',', '.', $normalized ?? '');

        if ($normalized === '' || ! is_numeric($normalized)) {
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
                            <div>Разделитель: точка с запятой (;) или запятая (,), кодировка: UTF-8.</div>
                            <div>Столбцы A-D: этаж, комната, название, длина гофры.</div>
                            <div>Начиная с 5-го столбца: названия кабелей (как в справочнике кабелей).</div>
                            <div>Импорт создаст строки только по заполненным длинам кабелей.</div>
                        </div>
                    </flux:callout>

                    @if (session()->has('message'))
                    <flux:callout icon="check-circle">
                        <flux:callout.heading>{{ session('message') }}</flux:callout.heading>
                    </flux:callout>
                    @endif

                    <form wire:submit="import" class="space-y-6">
                        <flux:field>
                            <flux:label>Гофра</flux:label>
                            <flux:select wire:model="pipe_id">
                                <option value="">Не выбрано</option>
                                @foreach($pipes as $pipe)
                                    <option value="{{ $pipe->id }}">{{ $pipe->name }}</option>
                                @endforeach
                            </flux:select>
                            <flux:error name="pipe_id" />
                        </flux:field>

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
                            <flux:button variant="primary" color="blue" icon="arrow-up-tray" type="submit">Импортировать</flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
