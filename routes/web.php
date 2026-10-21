<?php
use App\Http\Controllers\NaveController;
use Illuminate\Support\Facades\Route;

Route::get('/jugador', [NaveController::class, 'jugador'])->name('jugador');
Route::get('/espectador', [NaveController::class, 'espectador'])->name('espectador');
Route::post('/mover', [NaveController::class, 'mover'])->name('mover');
