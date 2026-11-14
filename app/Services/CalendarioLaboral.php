<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Días hábiles y festivos de Colombia, calculados en código (sin tabla en la base de datos).
 *
 * Día hábil: lunes a viernes que no sea festivo.
 *
 * Festivos según la Ley 51 de 1983 ("Ley Emiliani"):
 * - Fijos: no se mueven.
 * - Trasladables: si no caen en lunes, pasan al lunes siguiente.
 * - Religiosos que dependen del Domingo de Pascua.
 */
class CalendarioLaboral
{
    // Festivos que siempre caen en su fecha: [mes, día, nombre].
    private const FIJOS = [
        [1, 1, 'Año Nuevo'],
        [5, 1, 'Día del Trabajo'],
        [7, 20, 'Día de la Independencia'],
        [8, 7, 'Batalla de Boyacá'],
        [12, 8, 'Inmaculada Concepción'],
        [12, 25, 'Navidad'],
    ];

    // Festivos que se trasladan al lunes siguiente: [mes, día, nombre].
    private const TRASLADABLES = [
        [1, 6, 'Reyes Magos'],
        [3, 19, 'San José'],
        [6, 29, 'San Pedro y San Pablo'],
        [8, 15, 'Asunción de la Virgen'],
        [10, 12, 'Día de la Raza'],
        [11, 1, 'Todos los Santos'],
        [11, 11, 'Independencia de Cartagena'],
    ];

    // Festivos que dependen de la Pascua: [días desde el Domingo de Pascua, nombre, ¿se traslada al lunes?].
    private const SEGUN_PASCUA = [
        [-3, 'Jueves Santo', false],
        [-2, 'Viernes Santo', false],
        [39, 'Ascensión del Señor', true],
        [60, 'Corpus Christi', true],
        [68, 'Sagrado Corazón', true],
    ];

    /**
     * Festivos de un año, como ['2026-01-01' => 'Año Nuevo', ...] ordenados por fecha.
     */
    public function festivos(int $anio): array
    {
        $festivos = [];

        foreach (self::FIJOS as [$mes, $dia, $nombre]) {
            $festivos[Carbon::create($anio, $mes, $dia)->toDateString()] = $nombre;
        }

        foreach (self::TRASLADABLES as [$mes, $dia, $nombre]) {
            $fecha = $this->trasladarAlLunes(Carbon::create($anio, $mes, $dia));
            $festivos[$fecha->toDateString()] = $nombre;
        }

        $pascua = $this->domingoDePascua($anio);

        foreach (self::SEGUN_PASCUA as [$dias, $nombre, $seTraslada]) {
            $fecha = $pascua->copy()->addDays($dias);
            if ($seTraslada) {
                $fecha = $this->trasladarAlLunes($fecha);
            }
            $festivos[$fecha->toDateString()] = $nombre;
        }

        ksort($festivos);

        return $festivos;
    }

    public function esFestivo(CarbonInterface $fecha): bool
    {
        return array_key_exists($fecha->toDateString(), $this->festivos($fecha->year));
    }

    public function esDiaHabil(CarbonInterface $fecha): bool
    {
        return ! $fecha->isWeekend() && ! $this->esFestivo($fecha);
    }

    public function siguienteDiaHabil(CarbonInterface $fecha): Carbon
    {
        $dia = Carbon::parse($fecha)->startOfDay()->addDay();

        while (! $this->esDiaHabil($dia)) {
            $dia->addDay();
        }

        return $dia;
    }

    public function anteriorDiaHabil(CarbonInterface $fecha): Carbon
    {
        $dia = Carbon::parse($fecha)->startOfDay()->subDay();

        while (! $this->esDiaHabil($dia)) {
            $dia->subDay();
        }

        return $dia;
    }

    /**
     * Fechas en las que un dependiente puede registrar horas, a partir de $hoy:
     * el día hábil anterior, hoy (solo si es hábil) y el siguiente día hábil.
     * Devuelve fechas 'Y-m-d' ordenadas.
     */
    public function fechasPermitidasDependiente(CarbonInterface $hoy): array
    {
        $fechas = [$this->anteriorDiaHabil($hoy)->toDateString()];

        if ($this->esDiaHabil($hoy)) {
            $fechas[] = $hoy->toDateString();
        }

        $fechas[] = $this->siguienteDiaHabil($hoy)->toDateString();

        return $fechas;
    }

    // Si la fecha no cae en lunes, la mueve al lunes siguiente.
    private function trasladarAlLunes(Carbon $fecha): Carbon
    {
        return $fecha->isMonday() ? $fecha : $fecha->next(Carbon::MONDAY);
    }

    // Algoritmo de Meeus/Jones/Butcher para el Domingo de Pascua (calendario gregoriano).
    // Se calcula a mano para no depender de la extensión "calendar" de PHP.
    private function domingoDePascua(int $anio): Carbon
    {
        $a = $anio % 19;
        $b = intdiv($anio, 100);
        $c = $anio % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $mes = intdiv($h + $l - 7 * $m + 114, 31);
        $dia = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::create($anio, $mes, $dia)->startOfDay();
    }
}
