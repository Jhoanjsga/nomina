<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarRegistroHoraRequest;
use App\Models\Item;
use App\Models\RegistroHora;
use App\Models\Responsable;
use App\Services\CalculadoraPago;
use App\Services\CalendarioLaboral;
use Illuminate\Http\Request;

// Pantalla "Mis horas activas" del dependiente: formulario para registrar
// y sus registros pendientes (aún no liquidados) con los totales.
class MisRegistrosController extends Controller
{
    public function index(Request $request, CalendarioLaboral $calendario, CalculadoraPago $calculadora)
    {
        // Fechas que puede elegir: día hábil anterior, hoy (si es hábil) y siguiente día hábil.
        // Se muestran como lista para evitar errores; el servidor las valida de todas formas.
        $fechasPermitidas = $calendario->fechasPermitidasDependiente(today());

        // Solo se ofrecen responsables e ítems activos. Savio aparece primero.
        $responsables = Responsable::activos()->orderByRaw("tipo = 'savio' desc")->orderBy('nombre')->get();
        $items = Item::activos()->orderBy('nombre')->get();

        $pendientes = $request->user()->registros()
            ->whereNull('liquidacion_id')
            ->with(['responsable', 'item', 'usuario.valoresHora'])
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get();

        $totales = $calculadora->totales($pendientes);
        $resumen = $calculadora->resumenPorResponsable($pendientes);

        return view('mis-registros.index', compact(
            'responsables', 'items', 'fechasPermitidas', 'pendientes', 'totales', 'resumen', 'calculadora'
        ));
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
