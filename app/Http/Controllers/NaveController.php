<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\Nave;

class NaveController extends Controller
{
    // Página del jugador (emisor): controla su nave
    public function jugador()
    {
        return view('jugador');
    }

    // Página del espectador (receptor): ve todas las naves
    public function espectador()
    {
        return view('espectador');
    }

    // Recibe el movimiento y lo emite al canal
    public function mover(Request $request)
    {
        $data = $request->validate([
            'jugadorId' => ['required', 'string'],
            'x'         => ['required', 'numeric'],
            'y'         => ['required', 'numeric'],
            'viewportWidth' => ['required', 'numeric', 'min:1'],
            'viewportHeight' => ['required', 'numeric', 'min:1'],
        ]);

        broadcast(new Nave(
            $data['jugadorId'],
            (float) $data['x'],
            (float) $data['y'],
            (float) $data['viewportWidth'],
            (float) $data['viewportHeight']
        ));

        return response()->json(['ok' => true]);
    }
}

