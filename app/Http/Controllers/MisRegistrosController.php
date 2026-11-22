<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarRegistroHoraRequest;
use App\Models\Item;
use App\Models\RegistroHora;
use App\Models\Responsable;
use App\Services\CalendarioLaboral;

// Pantalla "Agregar registro" del dependiente. Sus pendientes se ven en Inicio.
class MisRegistrosController extends Controller
{
    public function create(CalendarioLaboral $calendario)
    {
        // Fechas que puede elegir: día hábil anterior, hoy (si es hábil) y siguiente día hábil.
        // Se muestran como lista para evitar errores; el servidor las valida de todas formas.
        $fechasPermitidas = $calendario->fechasPermitidasDependiente(today());

        // Solo se ofrecen responsables e ítems activos. Savio aparece primero.
        $responsables = Responsable::activos()->orderByRaw("tipo = 'savio' desc")->orderBy('nombre')->get();
        $items = Item::activos()->orderBy('nombre')->get();

        return view('mis-registros.create', compact('responsables', 'items', 'fechasPermitidas'));
    }

    public function store(GuardarRegistroHoraRequest $request)
    {
        // El dueño del registro es siempre quien tiene la sesión: nunca se toma del formulario.
        // liquidacion_id queda vacío, es decir, el registro nace pendiente.
        RegistroHora::create([
            ...$request->validated(),
            'usuario_id' => $request->user()->id,
        ]);

        return redirect()->route('inicio')->with('exito', 'Horas registradas correctamente.');
    }
}
