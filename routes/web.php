<?php

use App\Http\Controllers\ClientesController;
use App\Http\Controllers\MaestrosController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::get('clientes', [ClientesController::class, 'index'])->name('clientes.index');
    Route::get('clientes/nuevo', [ClientesController::class, 'create'])->name('clientes.create');
    Route::post('clientes', [ClientesController::class, 'store'])->name('clientes.store');
    Route::get('clientes/{cliente}', [ClientesController::class, 'show'])->name('clientes.show');
    Route::get('clientes/{cliente}/editar', [ClientesController::class, 'edit'])->name('clientes.edit');
    Route::patch('clientes/{cliente}', [ClientesController::class, 'update'])->name('clientes.update');
    Route::post('clientes/{cliente}/estado', [ClientesController::class, 'toggle'])->name('clientes.toggle');
    Route::delete('clientes/{cliente}', [ClientesController::class, 'destroy'])->name('clientes.destroy');

    Route::get('maestros', [MaestrosController::class, 'index'])->name('maestros.index');
    Route::post('maestros/{list}/valores', [MaestrosController::class, 'store'])->name('maestros.values.store');
    Route::patch('maestros/valores/{value}', [MaestrosController::class, 'update'])->name('maestros.values.update');
    Route::delete('maestros/valores/{value}', [MaestrosController::class, 'destroy'])->name('maestros.values.destroy');
    Route::post('maestros/valores/{value}/predeterminado', [MaestrosController::class, 'setDefault'])->name('maestros.values.default');
    Route::post('maestros/valores/{value}/estado', [MaestrosController::class, 'toggle'])->name('maestros.values.toggle');
    Route::post('maestros/valores/{value}/posicion', [MaestrosController::class, 'move'])->name('maestros.values.move');
});

require __DIR__.'/settings.php';
