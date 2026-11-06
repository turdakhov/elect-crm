<?php

use App\Http\Controllers\Api\Plan3dController;
use App\Http\Middleware\Plan3dToken;
use Illuminate\Support\Facades\Route;

// 3D-план (модельер разводки) живёт на своём сервере и ходит сюда с токеном.
Route::prefix('plan3d')->middleware(Plan3dToken::class)->group(function () {
    Route::get('projects', [Plan3dController::class, 'projects'])->name('api.plan3d.projects');
    Route::post('projects/{project}/syncs', [Plan3dController::class, 'store'])->name('api.plan3d.syncs.store');
});
