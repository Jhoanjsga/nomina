<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarValorHoraRequest;
use App\Models\Usuario;

class ValorHoraController extends Controller
{
    // Solo existe "agregar": el historial nunca se edita ni se borra.
    public function store(GuardarValorHoraRequest $request, Usuario $usuario)
    {
        $usuario->valoresHora()->create($request->validated());

        return redirect()->route('usuarios.edit', $usuario)->with('exito', 'Nuevo valor de la hora agregado al historial.');
    }
}
