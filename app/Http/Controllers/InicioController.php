<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Services\CalculadoraPago;

class InicioController extends Controller
{
    public function index(CalculadoraPago $calculadora)
    {
        $usuario = auth()->user();

        if ($usuario->esDependiente()) {
            return $this->inicioDependiente($usuario, $calculadora);
        }

        // Todos los dependientes, activos e inactivos, con sus registros pendientes.
        $dependientes = Usuario::where('rol', 'dependiente')
            ->with(['registros' => fn ($consulta) => $consulta->whereNull('liquidacion_id'), 'registros.usuario.valoresHora'])
            ->orderBy('nombre')
            ->get();

        $tarjetas = [];
        foreach ($dependientes as $dependiente) {
            $totales = $calculadora->totales($dependiente->registros);

            $tarjetas[] = [
                'usuario' => $dependiente,
                'horas' => $totales['horas'],
                'valor' => $totales['valor'],
            ];
        }

        return view('inicio', compact('tarjetas'));
    }

    // El dependiente ve sus registros pendientes (aún no liquidados) con los totales.
    private function inicioDependiente(Usuario $usuario, CalculadoraPago $calculadora)
    {
        $pendientes = $usuario->registros()
            ->whereNull('liquidacion_id')
            ->with(['responsable', 'item', 'usuario.valoresHora'])
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get();

        $totales = $calculadora->totales($pendientes);
        $resumen = $calculadora->resumenPorResponsable($pendientes);

        return view('inicio', compact('pendientes', 'totales', 'resumen', 'calculadora'));
    }
}
