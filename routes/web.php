<?php

use App\Http\Controllers\PartidaController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'inicio')->name('home');
Route::view('/inicio', 'inicio')->name('inicio');
Route::view('/jugador', 'piedra-papel-tijera', ['modo' => 'solo'])->name('jugador');
Route::view('/campana', 'piedra-papel-tijera', ['modo' => 'online'])->name('campana');

Route::prefix('partidas')->name('partidas.')->group(function (): void {
    Route::post('/buscar', [PartidaController::class, 'buscar'])->middleware('throttle:60,1')->name('buscar');
    Route::delete('/espera/{jugadorId}', [PartidaController::class, 'cancelarEspera'])->name('cancelar-espera');
    Route::get('/{partidaId}', [PartidaController::class, 'estado'])->name('estado');
    Route::post('/{partidaId}/jugadas', [PartidaController::class, 'jugar'])->middleware('throttle:60,1')->name('jugadas');
});
