<?php

namespace App\Http\Controllers;

use App\Services\CalculadoraPago;
use Illuminate\Http\Request;

// "Mis reportes" del dependiente: todos sus registros (pendientes y liquidados) en un rango de fechas.
class MisReportesController extends Controller
{
    public function index(Request $request, CalculadoraPago $calculadora)
    {
        // Si no elige fechas, se muestra la quincena actual (1 al 15 o 16 al fin de mes).
        $hoy = today();

        if ($hoy->day <= 15) {
            $inicioQuincena = $hoy->copy()->startOfMonth();
            $finQuincena = $hoy->copy()->day(15);
        } else {
            $inicioQuincena = $hoy->copy()->day(16);
            $finQuincena = $hoy->copy()->endOfMonth();
        }

        $request->mergeIfMissing([
            'desde' => $inicioQuincena->toDateString(),
            'hasta' => $finQuincena->toDateString(),
        ]);

        $datos = $request->validate([
            'desde' => ['required', 'date_format:Y-m-d'],
            'hasta' => ['required', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ], [
            'hasta.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
        ], [
            'desde' => 'desde',
            'hasta' => 'hasta',
        ]);

        $registros = $request->user()->registros()
            ->whereDate('fecha', '>=', $datos['desde'])
            ->whereDate('fecha', '<=', $datos['hasta'])
            ->with(['responsable', 'item', 'usuario.valoresHora'])
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get();

        $totales = $calculadora->totales($registros);

        return view('mis-reportes.index', [
            'registros' => $registros,
            'totales' => $totales,
            'calculadora' => $calculadora,
            'desde' => $datos['desde'],
            'hasta' => $datos['hasta'],
        ]);
    }
}
