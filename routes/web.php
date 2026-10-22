<?php
use App\Http\Controllers\NaveController;
use Illuminate\Support\Facades\Route;

Route::get('/inicio', [NaveController::class, 'inicio'])->name('inicio');
Route::get('/jugador', [NaveController::class, 'jugador'])->name('jugador');
Route::get('/campana', [NaveController::class, 'campana'])->name('campana');
Route::post('/mover', [NaveController::class, 'mover'])->name('mover');
