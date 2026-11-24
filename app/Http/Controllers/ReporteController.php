<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\RegistroHora;
use App\Models\Usuario;
use App\Services\CalculadoraPago;
use Illuminate\Http\Request;

// Reportes del administrador: registros filtrados por fechas, dependiente, ítem y estado,
// con el resumen por responsable (lo que debe devolver cada abogado). Se exportan a Excel (CSV) o se imprimen en PDF.
class ReporteController extends Controller
{
    public function index(Request $request, CalculadoraPago $calculadora)
    {
        [$registros, $filtros] = $this->filtrar($request);

        $totales = $calculadora->totales($registros);
        $resumen = $calculadora->resumenPorResponsable($registros);

        $dependientes = Usuario::where('rol', 'dependiente')->orderBy('nombre')->get();
        $items = Item::orderBy('nombre')->get();

        return view('reportes.index', compact(
            'registros', 'filtros', 'totales', 'resumen', 'calculadora', 'dependientes', 'items'
        ));
    }

    public function imprimir(Request $request, CalculadoraPago $calculadora)
    {
        [$registros, $filtros] = $this->filtrar($request);

        $totales = $calculadora->totales($registros);
        $resumen = $calculadora->resumenPorResponsable($registros);

        $dependiente = $filtros['usuario_id'] ? Usuario::find($filtros['usuario_id']) : null;
        $item = $filtros['item_id'] ? Item::find($filtros['item_id']) : null;

        return view('reportes.imprimir', compact(
            'registros', 'filtros', 'totales', 'resumen', 'calculadora', 'dependiente', 'item'
        ));
    }

    public function excel(Request $request, CalculadoraPago $calculadora)
    {
        [$registros, $filtros] = $this->filtrar($request);

        $totales = $calculadora->totales($registros);
        $resumen = $calculadora->resumenPorResponsable($registros);

        $nombreArchivo = 'reporte-'.$filtros['desde'].'-a-'.$filtros['hasta'].'.csv';

        return response()->streamDownload(function () use ($registros, $totales, $resumen, $calculadora) {
            $archivo = fopen('php://output', 'w');

            // BOM para que Excel lea bien las tildes. Excel en español separa con punto y coma.
            fwrite($archivo, "\xEF\xBB\xBF");
            fputcsv($archivo, ['Fecha', 'Dependiente', 'Hora inicio', 'Hora fin', 'Responsable', 'Ítem', 'Radicado', 'Cliente', 'Observación', 'Estado', 'Horas', 'Valor'], ';');

            foreach ($registros as $registro) {
                fputcsv($archivo, [
                    $registro->fecha->format('d/m/Y'),
                    $registro->usuario->nombre,
                    substr($registro->hora_inicio, 0, 5),
                    substr($registro->hora_fin, 0, 5),
                    $registro->responsable->nombre,
                    $registro->item->nombre,
                    $registro->radicado,
                    $registro->cliente,
                    $registro->observacion,
                    $registro->liquidacion_id ? 'Liquidado' : 'Pendiente',
                    number_format($calculadora->horas($registro), 2, ',', ''),
                    number_format($calculadora->valor($registro), 2, ',', ''),
                ], ';');
            }

            fputcsv($archivo, ['Total', '', '', '', '', '', '', '', '', '',
                number_format($totales['horas'], 2, ',', ''),
                number_format($totales['valor'], 2, ',', ''),
            ], ';');

            fputcsv($archivo, [], ';');
            fputcsv($archivo, ['Resumen por responsable', 'Horas', 'Valor'], ';');

            foreach ($resumen as $fila) {
                fputcsv($archivo, [
                    $fila['responsable'],
                    number_format($fila['horas'], 2, ',', ''),
                    number_format($fila['valor'], 2, ',', ''),
                ], ';');
            }

            fclose($archivo);
        }, $nombreArchivo, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // Si no se eligen fechas, se usa la quincena actual (1 al 15 o 16 al fin de mes).
    private function filtrar(Request $request): array
    {
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
            'usuario_id' => ['nullable', 'exists:usuarios,id'],
            'item_id' => ['nullable', 'exists:items,id'],
            'estado' => ['nullable', 'in:todos,pendientes,liquidados'],
        ], [
            'hasta.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
        ]);

        $filtros = [
            'desde' => $datos['desde'],
            'hasta' => $datos['hasta'],
            'usuario_id' => $datos['usuario_id'] ?? null,
            'item_id' => $datos['item_id'] ?? null,
            'estado' => $datos['estado'] ?? 'todos',
        ];

        $registros = RegistroHora::whereDate('fecha', '>=', $filtros['desde'])
            ->whereDate('fecha', '<=', $filtros['hasta'])
            ->when($filtros['usuario_id'], fn ($consulta) => $consulta->where('usuario_id', $filtros['usuario_id']))
            ->when($filtros['item_id'], fn ($consulta) => $consulta->where('item_id', $filtros['item_id']))
            ->when($filtros['estado'] === 'pendientes', fn ($consulta) => $consulta->whereNull('liquidacion_id'))
            ->when($filtros['estado'] === 'liquidados', fn ($consulta) => $consulta->whereNotNull('liquidacion_id'))
            ->with(['responsable', 'item', 'usuario.valoresHora'])
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get();

        return [$registros, $filtros];
    }
}
