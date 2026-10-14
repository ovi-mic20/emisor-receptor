<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\RemoteStyleUpdate;

class RemoteStyleController extends Controller
{
    public function emisor()
    {
        return view('emisor');
    }
    public function receptor()
    {
        return view('receptor');
    }

    public function emitir(Request $request)
    {
        $data = $request->validate([
            'newText' => ['required', 'string'],
        ]);

        broadcast(new RemoteStyleUpdate($data['newText']));

        return back()->with('ok', 'Mensaje emitido correctamente.');
    }
}
