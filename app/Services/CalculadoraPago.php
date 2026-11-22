<?php

namespace App\Services;

use App\Models\RegistroHora;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Horas y dinero de cada registro, con el valor de la hora vigente en la fecha del registro.
 */
class CalculadoraPago
{
    public function horas(RegistroHora $registro): float
    {
        return round($this->minutos($registro) / 60, 2);
    }

    public function valor(RegistroHora $registro): float
    {
        $valorHora = $this->valorHoraVigente($registro->usuario->valoresHora, $registro->fecha);

        // Se calcula con los minutos y no con las horas redondeadas,
        // para que 20 minutos no se paguen como 0.33 horas.
        return round($this->minutos($registro) * $valorHora / 60, 2);
    }

    // El valor que aplica es el último con vigente_desde menor o igual a la fecha.
    public function valorHoraVigente(Collection $valores, CarbonInterface $fecha): float
    {
        $vigente = $valores
            ->filter(fn ($valor) => $valor->vigente_desde->toDateString() <= $fecha->toDateString())
            ->sortByDesc('vigente_desde')
            ->first();

        if (! $vigente) {
            throw new RuntimeException(
                'El usuario no tiene valor de la hora vigente para el ' . $fecha->toDateString() . '.'
            );
        }

        return (float) $vigente->valor;
    }

    public function totales(Collection $registros): array
    {
        $minutos = 0;
        $valor = 0;

        foreach ($registros as $registro) {
            $minutos += $this->minutos($registro);
            $valor += $this->valor($registro);
        }

        return [
            'horas' => round($minutos / 60, 2),
            'valor' => round($valor, 2),
        ];
    }

    // Horas y valor agrupados por responsable (Savio o cada abogado).
    // Los registros deben venir con su responsable cargado.
    public function resumenPorResponsable(Collection $registros): Collection
    {
        return $registros
            ->groupBy('responsable_id')
            ->map(fn ($grupo) => [
                'responsable' => $grupo->first()->responsable->nombre,
                ...$this->totales($grupo),
            ])
            ->sortBy('responsable')
            ->values();
    }

    private function minutos(RegistroHora $registro): int
    {
        $inicio = Carbon::parse($registro->hora_inicio);
        $fin = Carbon::parse($registro->hora_fin);

        return (int) $inicio->diffInMinutes($fin);
    }
}
