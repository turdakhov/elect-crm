<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">

    <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">

        <div class="relative overflow-x-auto">
            <div>
                <form wire:submit.prevent="submit" class="space-y-6 p-6">
                    <flux:input wire:model='name' label="Название ЖК" />
                    <flux:input wire:model='address' label="Адрес" />
                    <flux:textarea wire:model='description' label="Описание" />
                    <flux:button variant="primary" color="green" type="submit" icon="plus">Создать</flux:button>
                </form>
            </div>

        </div>
    </div>
</div>
