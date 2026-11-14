<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarRegistroHoraRequest;
use App\Models\Item;
use App\Models\RegistroHora;
use App\Models\Responsable;
use App\Services\CalendarioLaboral;

// Pantalla "Mis horas activas" del dependiente.
// Actividad 12: formulario para registrar horas.
// Actividad 15 agregará la tabla de pendientes y los totales en esta misma pantalla.
class MisRegistrosController extends Controller
{
    public function index(CalendarioLaboral $calendario)
    {
        // Fechas que puede elegir: día hábil anterior, hoy (si es hábil) y siguiente día hábil.
        // Se muestran como lista para evitar errores; el servidor las valida de todas formas.
        $fechasPermitidas = $calendario->fechasPermitidasDependiente(today());

        // Solo se ofrecen responsables e ítems activos. Savio aparece primero.
        $responsables = Responsable::activos()->orderByRaw("tipo = 'savio' desc")->orderBy('nombre')->get();
        $items = Item::activos()->orderBy('nombre')->get();

        return view('mis-registros.index', compact('responsables', 'items', 'fechasPermitidas'));
    }

    public function store(GuardarRegistroHoraRequest $request)
    {
        // El dueño del registro es siempre quien tiene la sesión: nunca se toma del formulario.
        // liquidacion_id queda vacío, es decir, el registro nace pendiente.
        RegistroHora::create([
            ...$request->validated(),
            'usuario_id' => $request->user()->id,
        ]);

        return redirect()->route('mis-registros.index')->with('exito', 'Horas registradas correctamente.');
    }
}
