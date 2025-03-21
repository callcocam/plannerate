<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\Plannerate\Http\Controllers;

use App\Http\Controllers\Controller;
use Callcocam\Plannerate\Models\Gondola;
use Illuminate\Http\Request;

class GondolaController extends Controller
{
    public function updateScaleFactor(Gondola $gondola, Request $request)
    {
        $gondola->update(['scale_factor' => $request->scale_factor]);

        return response()->json(['message' => 'Escala atualizada com sucesso.']);
    }
}
