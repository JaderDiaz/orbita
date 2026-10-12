<?php

use App\Http\Controllers\MaestrosController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::get('maestros', [MaestrosController::class, 'index'])->name('maestros.index');
    Route::post('maestros/{list}/valores', [MaestrosController::class, 'store'])->name('maestros.values.store');
    Route::patch('maestros/valores/{value}', [MaestrosController::class, 'update'])->name('maestros.values.update');
    Route::delete('maestros/valores/{value}', [MaestrosController::class, 'destroy'])->name('maestros.values.destroy');
    Route::post('maestros/valores/{value}/predeterminado', [MaestrosController::class, 'setDefault'])->name('maestros.values.default');
    Route::post('maestros/valores/{value}/estado', [MaestrosController::class, 'toggle'])->name('maestros.values.toggle');
    Route::post('maestros/valores/{value}/posicion', [MaestrosController::class, 'move'])->name('maestros.values.move');
});

require __DIR__.'/settings.php';
