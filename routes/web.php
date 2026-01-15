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
    // Project products summary
    Volt::route('projects/{project}/products-summary', 'project-products-summary')->name('projects.products-summary');
    // Project product sets nested under projects
    Volt::route('projects/{project}/product-set', 'project-product-set-index')->name('projects.product-set.index');
    Volt::route('projects/{project}/product-set/create', 'project-product-set-create')->name('projects.product-set.create');
    Volt::route('project-product-set/{projectProductSet}/edit', 'project-product-set-edit')->name('project-product-set.edit');
    Volt::route('project-product-set/{projectProductSet}/items', 'project-product-set-items-index')->name('project-product-set.items.index');
    Volt::route('project-product-set/{projectProductSet}/items/create', 'project-product-set-items-create')->name('project-product-set.items.create');
    Volt::route('project-product-set-items/{projectProductSetItem}/edit', 'project-product-set-items-edit')->name('project-product-set-items.edit');
    // Purchases nested under projects
    Volt::route('projects/{project}/purchases', 'purchases-index')->name('projects.purchases.index');
    Volt::route('projects/{project}/purchases/create', 'purchases-create')->name('projects.purchases.create');
    Volt::route('purchases/{purchase}/edit', 'purchases-edit')->name('purchases.edit');
    Volt::route('purchases/{purchase}/items', 'purchase-items-index')->name('purchases.items.index');
    Volt::route('purchases/{purchase}/items/create', 'purchase-items-create')->name('purchases.items.create');
    Volt::route('purchase-items/{purchaseItem}/edit', 'purchase-items-edit')->name('purchase-items.edit');

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

    Volt::route('file-types', 'file-types-index')->name('file-types.index');
    Volt::route('file-types/create', 'file-types-create')->name('file-types.create');
    Volt::route('file-types/{file_type}/edit', 'file-types-edit')->name('file-types.edit');

    Volt::route('products', 'products-index')->name('products.index');
    Volt::route('products/create', 'products-create')->name('products.create');
    Volt::route('products/{product}/edit', 'products-edit')->name('products.edit');

    Volt::route('cables', 'cables-index')->name('cables.index');
    Volt::route('cables/create', 'cables-create')->name('cables.create');
    Volt::route('cables/{cable}/edit', 'cables-edit')->name('cables.edit');

    Volt::route('pipes', 'pipes-index')->name('pipes.index');
    Volt::route('pipes/create', 'pipes-create')->name('pipes.create');
    Volt::route('pipes/{pipe}/edit', 'pipes-edit')->name('pipes.edit');

    // Cable Items (project-specific)
    Volt::route('projects/{project}/cable-items', 'cable-items-index')->name('projects.cable-items.index');
    Volt::route('projects/{project}/cable-items/create', 'cable-items-create')->name('projects.cable-items.create');
    Volt::route('cable-items/{cableItem}/edit', 'cable-items-edit')->name('cable-items.edit');
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
