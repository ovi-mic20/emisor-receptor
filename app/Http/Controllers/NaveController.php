<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\Nave;

class NaveController extends Controller
{
    public function inicio()
    {
        return view('inicio');
    }

    // Página del jugador (emisor): controla su nave
    public function jugador()
    {
        return view('jugador');
    }

    public function campana()
    {
        return view('campana');
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
            'modo' => ['sometimes', 'in:individual,campana'],
            'vidas' => ['sometimes', 'integer', 'min:0', 'max:3'],
            'puntos' => ['sometimes', 'integer', 'min:0'],
            'escudo' => ['sometimes', 'boolean'],
            'disparo' => ['sometimes', 'array'],
            'disparo.id' => ['required_with:disparo', 'string', 'max:100'],
            'disparo.x' => ['required_with:disparo', 'numeric'],
            'disparo.y' => ['required_with:disparo', 'numeric'],
            'disparo.angulo' => ['required_with:disparo', 'numeric'],
            'disparo.viewportWidth' => ['required_with:disparo', 'numeric', 'min:1'],
            'disparo.viewportHeight' => ['required_with:disparo', 'numeric', 'min:1'],
            'disparo.targetId' => ['nullable', 'string', 'max:80'],
            'impacto' => ['sometimes', 'array'],
            'impacto.id' => ['required_with:impacto', 'string', 'max:100'],
            'impacto.atacanteId' => ['required_with:impacto', 'string', 'max:80'],
            'impacto.tipo' => ['required_with:impacto', 'in:rival,meteorito,monstruo,colision'],
            'relacion' => ['sometimes', 'array'],
            'relacion.id' => ['required_with:relacion', 'string', 'max:120'],
            'relacion.emisorId' => ['required_with:relacion', 'string', 'max:80'],
            'relacion.destinatarioId' => ['required_with:relacion', 'string', 'max:80'],
            'relacion.accion' => ['required_with:relacion', 'in:request,accept,reject'],
        ]);

        broadcast(new Nave(
            $data['jugadorId'],
            (float) $data['x'],
            (float) $data['y'],
            (float) $data['viewportWidth'],
            (float) $data['viewportHeight'],
            (int) ($data['vidas'] ?? 3),
            (int) ($data['puntos'] ?? 0),
            (bool) ($data['escudo'] ?? false),
            $data['disparo'] ?? null,
            $data['impacto'] ?? null,
            ($data['modo'] ?? 'individual') === 'campana',
            $data['relacion'] ?? null
        ));

        return response()->json(['ok' => true]);
    }
}
