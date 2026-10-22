<?php
use App\Http\Controllers\NaveController;
use Illuminate\Support\Facades\Route;

Route::get('/', [NaveController::class, 'inicio'])->name('inicio');
Route::get('/jugador', [NaveController::class, 'jugador'])->name('jugador');
Route::get('/enemigo', [NaveController::class, 'enemigo'])->name('enemigo');
Route::post('/mover', [NaveController::class, 'mover'])->name('mover');
