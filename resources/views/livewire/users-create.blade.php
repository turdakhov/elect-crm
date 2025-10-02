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

        User::create([
            'name' => $this->name,
            'phone' => $this->phone,
            'description' => $this->description,
            'role' => $this->role,
            'email' => $email,
            'password' => $password,
        ]);

        session()->flash('message', 'Пользователь успешно добавлен! Email: ' . $email . ', Пароль: ' . $password);

        $this->redirect(route('users.index'));
    }

    public function with(): array
    {
        return [
            'roles' => UserRoleEnum::cases(),
        ];
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">

        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">

            <div class="relative overflow-x-auto">
                <div>
                    <form wire:submit.prevent="submit" class="space-y-6 p-6">
                        <label for="roles" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Роль пользователя</label>
                        <select wire:model='role' id="roles" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            <option value="">Не выбрана</option>
                            @foreach ($roles as $role)
                            <option value="{{ $role->name }}">{{ $role->value }}</option>
                            @endforeach
                        </select>
                        @error('role')
                        <div role="alert" aria-live="polite" aria-atomic="true" class="mt-3 text-sm font-medium text-red-500 dark:text-red-400">
                            <svg class="shrink-0 [:where(&amp;)]:size-5 inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"></path>
                            </svg>
                            {{ $message }}
                        </div>
                        @enderror

                        <flux:input wire:model='name' label="ФИО" />
                        <flux:input wire:model='phone' label="Телефон" />
                        <flux:input wire:model='email' label="Email (необязательное поле)" />
                        <flux:textarea wire:model='description' label="Описание" />
                        <flux:button variant="primary" color="green" type="submit" icon="plus">Создать</flux:button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>