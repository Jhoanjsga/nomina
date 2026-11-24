<?php

namespace App\Http\Controllers;

use App\Models\Liquidacion;
use App\Models\RegistroHora;
use App\Models\Usuario;
use App\Services\CalculadoraPago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

// Liquidar los registros pendientes de un dependiente en un rango de fechas.
// Primero se muestra una página de confirmación y solo al confirmar se guarda.
class LiquidacionController extends Controller
{
    public function confirmar(Request $request, Usuario $usuario, CalculadoraPago $calculadora)
    {
        abort_unless($usuario->esDependiente(), 404);

        [$desde, $hasta] = $this->rango($request);
        $registros = $this->pendientes($usuario, $desde, $hasta);

        if ($registros->isEmpty()) {
            return redirect()->route('usuarios.show', $usuario)
                ->with('error', 'No hay registros pendientes para liquidar en ese rango.');
        }

        try {
            $totales = $calculadora->totales($registros);
        } catch (RuntimeException $e) {
            return redirect()->route('usuarios.show', $usuario)->with('error', 'No se puede liquidar: '.$e->getMessage());
        }

        $resumen = $calculadora->resumenPorResponsable($registros);

        return view('liquidaciones.confirmar', compact(
            'usuario', 'registros', 'totales', 'resumen', 'calculadora', 'desde', 'hasta'
        ));
    }

    public function store(Request $request, Usuario $usuario, CalculadoraPago $calculadora)
    {
        abort_unless($usuario->esDependiente(), 404);

        [$desde, $hasta] = $this->rango($request);
        $registros = $this->pendientes($usuario, $desde, $hasta);

        // Si ya se liquidaron (por ejemplo, al pulsar Confirmar dos veces) no queda nada pendiente.
        if ($registros->isEmpty()) {
            return redirect()->route('usuarios.show', $usuario)
                ->with('error', 'No hay registros pendientes para liquidar en ese rango.');
        }

        $totales = $calculadora->totales($registros);

        // Se crea la liquidación y se marcan los registros juntos: o se guarda todo o nada.
        DB::transaction(function () use ($usuario, $registros, $totales) {
            $liquidacion = Liquidacion::create([
                'usuario_id' => $usuario->id,
                'fecha_finalizacion' => today(),
                'total_horas' => $totales['horas'],
                'total_pagado' => $totales['valor'],
            ]);

            RegistroHora::whereIn('id', $registros->pluck('id'))->update(['liquidacion_id' => $liquidacion->id]);
        });

        return redirect()->route('usuarios.show', $usuario)->with('exito', 'Liquidación realizada correctamente.');
    }

    // El rango es obligatorio, pero puede ser de cualquier tamaño.
    private function rango(Request $request): array
    {
        $datos = $request->validate([
            'desde' => ['required', 'date_format:Y-m-d'],
            'hasta' => ['required', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ], [
            'hasta.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
        ]);

        return [$datos['desde'], $datos['hasta']];
    }

    private function pendientes(Usuario $usuario, string $desde, string $hasta)
    {
        return $usuario->registros()
            ->whereNull('liquidacion_id')
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->with(['responsable', 'item', 'usuario.valoresHora'])
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get();
    }
}
