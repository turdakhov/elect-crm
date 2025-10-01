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

require __DIR__ . '/auth.php';
