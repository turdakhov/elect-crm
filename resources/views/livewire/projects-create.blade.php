<?php

use App\Enums\ProjectStatusEnum;
use App\Models\Complex;
use App\Models\Project;
use App\Models\User;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new class extends Component {
    public $name;
    public $client_id;
    public $foreman_id;
    public $designer_id;
    public $supervisor_id;
    public $square;
    public $price_per_sqm;
    public $price_per_sqm_rough;
    public $price_per_sqm_fine;
    public $total_price;
    public $complex_id;
    public $address;
    public $description;
    public $start_date;
    public $end_date;
    public $status;
    public $selected;

    public $showModal = false;

    public function with(): array
    {
        return [
            'clients' => User::clients()->get(),
            'foremen' => User::foremen()->get(),
            'designers' => User::designers()->get(),
            'supervisors' => User::supervisors()->get(),
            'complexes' => Complex::all(),
            'statuses' => ProjectStatusEnum::cases(),
        ];
    }


    public function submit()
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:projects,name',
            'client_id' => 'nullable|exists:users,id',
            'foreman_id' => 'nullable|exists:users,id',
            'designer_id' => 'nullable|exists:users,id',
            'supervisor_id' => 'nullable|exists:users,id',
            'square' => 'nullable|numeric|min:1',
            'price_per_sqm' => 'nullable|numeric|',
            'price_per_sqm_rough' => 'nullable|numeric',
            'price_per_sqm_fine' => 'nullable|numeric',
            'total_price' => 'nullable|numeric',
            'complex_id' => 'required|exists:complexes,id',
            'address' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|string|max:255',
        ]);

        Project::create([
            'name' => $this->name,
            'client_id' => $this->client_id,
            'foreman_id' => $this->foreman_id,
            'designer_id' => $this->designer_id,
            'supervisor_id' => $this->supervisor_id,
            'square' => $this->square,
            'price_per_sqm' => $this->price_per_sqm,
            'price_per_sqm_rough' => $this->price_per_sqm_rough,
            'price_per_sqm_fine' => $this->price_per_sqm_fine,
            'total_price' => $this->total_price,
            'complex_id' => $this->complex_id,
            'address' => $this->address,
            'description' => $this->description,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => $this->status,
        ]);

        session()->flash('message', 'Проект успешно добавлен!');

        $this->redirect(route('projects.index'));
    }

    public function show($role)
    {
        $this->showModal = true;
        $this->selected = $role;
    }

    #[On('hide')]
    public function hide($role = '', $userId = null)
    {
        $this->showModal = false;
        if (!$role) {
            return;
        }

        $role = strtolower($role);
        $this->userId = $userId;
    }
}; ?>

<div>
    @if ($showModal)
    <div wire:click="hide" class="fixed z-30 w-full h-full inset-0 bg-slate-800 opacity-80"></div>

    <div class="fixed z-40 inset-0 w-1/2 left-1/4 top-1/8">
        <livewire:users-create :stay="true" :selected="$selected" />
    </div>
    @endif

    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <div class="relative overflow-x-auto">
                <div>
                    <form wire:submit.prevent="submit" class="space-y-6 p-6">
                        <flux:input wire:model='name' label="Имя проекта" required />
                        <x-forms.select name="complex_id" cname="name" label="Комплекс" :collection="$complexes" with_empty />
                        <flux:input wire:model='address' label="Адрес" />
                        <flux:input wire:model='square' label="Площадь (м²)" type="number" step="0.1" />

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-forms.select-user role="Client" name="client_id" label="Клиент" :collection="$clients" with_empty />
                            <x-forms.select-user role="Foreman" name="foreman_id" label="Прораб" :collection="$foremen" with_empty />
                            <x-forms.select-user role="Designer" name="designer_id" label="Дизайнер" :collection="$designers" with_empty />
                            <x-forms.select-user role="Supervisor" name="supervisor_id" label="Технадзор" :collection="$supervisors" with_empty />
                            <flux:input wire:model='price_per_sqm_rough' label="Цена за м² черновая" type="number" step="1" />
                            <flux:input wire:model='price_per_sqm_fine' label="Цена за м² чистовая" type="number" step="1" />
                            <flux:input wire:model='price_per_sqm' label="Цена за м²" type="number" step="1" />
                            <flux:input wire:model='total_price' label="Общая цена" type="number" step="1" />
                        </div>
                        <flux:textarea wire:model='description' label="Описание" />
                        <flux:input wire:model='start_date' label="Дата начала" type="date" />
                        <flux:input wire:model='end_date' label="Дата окончания" type="date" />
                        <x-forms.select-enum name="status" label="Статус" :enum="$statuses" with_empty />
                        <flux:button variant="primary" color="green" type="submit" icon="plus">Создать</flux:button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>