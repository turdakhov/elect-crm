<?php

use App\Livewire\ComplexesCreate;
use App\Livewire\ComplexesIndex;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::group(['middleware' => ['auth', 'verified']], function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::get('complexes', ComplexesIndex::class)->name('complexes.index');
    Route::get('complexes/create', ComplexesCreate::class)->name('complexes.create');
    Volt::route('complexes/{complex}/edit', 'complexes-edit')->name('complexes.edit');
    Volt::route('projects', 'projects-index')->name('projects.index');
    Volt::route('projects/create', 'projects-create')->name('projects.create');
    Volt::route('projects/{project}/edit', 'projects-edit')->name('projects.edit');
    // Purchases nested under projects
    Volt::route('projects/{project}/purchases', 'purchases-index')->name('projects.purchases.index');
    Volt::route('projects/{project}/purchases/create', 'purchases-create')->name('projects.purchases.create');
    Volt::route('purchases/{purchase}/edit', 'purchases-edit')->name('purchases.edit');

    Volt::route('finances', 'finances-index')->name('finances.index');

    Volt::route('expense-types', 'expense-types-index')->name('expense-types.index');
    Volt::route('expense-types/create', 'expense-types-create')->name('expense-types.create');
    Volt::route('expense-types/{expenseType}/edit', 'expense-types-edit')->name('expense-types.edit');

    Volt::route('projects/{project}/expenses', 'expenses-index')->name('projects.expenses.index');
    Volt::route('projects/{project}/expenses/create', 'expenses-create')->name('projects.expenses.create');
    Volt::route('expenses', 'expenses-index')->name('expenses.index');
    Volt::route('expenses/create', 'expenses-create')->name('expenses.create');
    Volt::route('expenses/{expense}/edit', 'expenses-edit')->name('expenses.edit');

    Volt::route('projects/{project}/incomes', 'incomes-index')->name('projects.incomes.index');
    Volt::route('projects/{project}/incomes/create', 'incomes-create')->name('projects.incomes.create');
    Volt::route('incomes', 'incomes-index')->name('incomes.index');
    Volt::route('incomes/create', 'incomes-create')->name('incomes.create');
    Volt::route('incomes/{income}/edit', 'incomes-edit')->name('incomes.edit');

    Volt::route('users', 'users-index')->name('users.index');
    Volt::route('users/create', 'users-create')->name('users.create');
    Volt::route('users/{user}/edit', 'users-edit')->name('users.edit');
    Volt::route('expense-types', 'expense-types-index')->name('expense-types.index');
    Volt::route('expense-types/create', 'expense-types-create')->name('expense-types.create');
    Volt::route('expense-types/{expense_type}/edit', 'expense-types-edit')->name('expense-types.edit');
    Volt::route('users/create', 'users-create')->name('users.create');
    Volt::route('users/{user}/edit', 'users-edit')->name('users.edit');

    Volt::route('file-types', 'file-types-index')->name('file-types.index');
    Volt::route('file-types/create', 'file-types-create')->name('file-types.create');
    Volt::route('file-types/{file_type}/edit', 'file-types-edit')->name('file-types.edit');

    Volt::route('products', 'products-index')->name('products.index');
    Volt::route('products/create', 'products-create')->name('products.create');
    Volt::route('products/{product}/edit', 'products-edit')->name('products.edit');
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});

require __DIR__.'/auth.php';
