<?php

use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component {
    public $name;
    public $phone;
    public $description;
    public $role;
    public $email;
    public $stay;
    public $selected;

    public function mount()
    {
        $this->role = $this->selected;
    }

    public function submit()
    {
        $this->validate([
            'role' => ['required', Rule::in(array_column(UserRoleEnum::cases(), 'name'))],
            'name' => 'required|string|max:255|unique:users,name',
            'email' => 'nullable|string|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        if (!$this->email)
            $email = Str::slug($this->name) . '@elect.kz';
        else
            $email = $this->email;

        $password = Str::random(8);

        $user = User::create([
            'name' => $this->name,
            'phone' => $this->phone,
            'description' => $this->description,
            'role' => $this->role,
            'email' => $email,
            'password' => $password,
        ]);

        session()->flash('message', 'Пользователь успешно добавлен! Email: ' . $email . ', Пароль: ' . $password);

        if (!$this->stay)
            $this->redirect(route('users.index'));
        else
            $this->dispatch('hide', $this->role, $user->id);
    }

    public function with(): array
    {
        return [
            'roles' => UserRoleEnum::cases(),
        ];
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl bg-white">

        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">

            <div class="relative overflow-x-auto">
                <div>
                    <div class="space-y-6 p-6">
                        <x-forms.select-enum name="role" label="Роль" :enum="$roles" :selected="$selected" with_empty />
                        <flux:input wire:model='name' label="ФИО" />
                        <flux:input wire:model='phone' label="Телефон" />
                        <flux:input wire:model='email' label="Email (необязательное поле)" />
                        <flux:textarea wire:model='description' label="Описание" />
                        <flux:button wire:click="submit" variant="primary" color="green" type="button" icon="plus" command="close" commandfor="dialog">Создать</flux:button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>