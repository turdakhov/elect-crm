<?php

use App\Models\FileType;
use Livewire\Volt\Component;

new class extends Component {
    public function with(): array
    {
        return [
            'fileTypes' => FileType::all(),
        ];
    }

    public function delete(FileType $fileType)
    {
        $fileType->delete();
        session()->flash('message', 'Тип файла успешно удален!');

        // TODO: Добавить проверку на существование файлов такого типа
    }
}; ?>

<div>
    <div>
        <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
            <flux:button variant="primary" color="green" icon="list-bullet" :href="route('file-types.create')">Добавить тип файла
            </flux:button>
            @if (session()->has('message'))
            <flux:callout icon="bell-alert">
                <flux:callout.heading>{{ session('message') }}</flux:callout.heading>
            </flux:callout>
            @endif
            <div
                class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">

                <div class="relative overflow-x-auto">
                    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-6 py-3">
                                    ID
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Наименование
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Описание
                                </th>
                                <th scope="col" class="px-6 py-3 justify-end flex">
                                    Управление
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($fileTypes as $fileType)
                            <tr wire:key='{{ $fileType->id }}'
                                class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200">
                                <td class="px-6 py-4">
                                    {{ $fileType->id }}
                                </td>
                                <th scope="row"
                                    class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ $fileType->name }}
                                </th>
                                <td class="px-6 py-4">
                                    {{ $fileType->description }}
                                </td>
                                <td class="px-6 py-4 flex gap-2 justify-end">
                                    <flux:button size="xs" color="blue" icon="pencil" :href="route('file-types.edit', $fileType)">
                                        Редактировать
                                    </flux:button>
                                    <flux:button size="xs" color="red" icon="trash" wire:click="delete({{ $fileType->id }})" confirm="Вы уверены, что хотите удалить этот тип файла?">
                                        Удалить
                                    </flux:button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>