<?php

namespace Tests\Unit;

use App\Services\CalendarioLaboral;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class CalendarioLaboralTest extends TestCase
{
    private CalendarioLaboral $calendario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calendario = new CalendarioLaboral();
    }

    public function test_festivos_de_colombia_2026(): void
    {
        // Calendario oficial 2026 (18 festivos).
        $esperados = [
            '2026-01-01', '2026-01-12', '2026-03-23', '2026-04-02', '2026-04-03',
            '2026-05-01', '2026-05-18', '2026-06-08', '2026-06-15', '2026-06-29',
            '2026-07-20', '2026-08-07', '2026-08-17', '2026-10-12', '2026-11-02',
            '2026-11-16', '2026-12-08', '2026-12-25',
        ];

        $this->assertSame($esperados, array_keys($this->calendario->festivos(2026)));
    }

    public function test_festivos_de_colombia_2027(): void
    {
        // Calendario oficial 2027 (18 festivos).
        $esperados = [
            '2027-01-01', '2027-01-11', '2027-03-22', '2027-03-25', '2027-03-26',
            '2027-05-01', '2027-05-10', '2027-05-31', '2027-06-07', '2027-07-05',
            '2027-07-20', '2027-08-07', '2027-08-16', '2027-10-18', '2027-11-01',
            '2027-11-15', '2027-12-08', '2027-12-25',
        ];

        $this->assertSame($esperados, array_keys($this->calendario->festivos(2027)));
    }

    public function test_dias_habiles(): void
    {
        $this->assertTrue($this->calendario->esDiaHabil(Carbon::parse('2026-11-13')));  // viernes
        $this->assertFalse($this->calendario->esDiaHabil(Carbon::parse('2026-11-14'))); // sábado
        $this->assertFalse($this->calendario->esDiaHabil(Carbon::parse('2026-11-15'))); // domingo
        $this->assertFalse($this->calendario->esDiaHabil(Carbon::parse('2026-11-16'))); // lunes festivo
    }

    public function test_siguiente_y_anterior_dia_habil_saltan_fines_de_semana_y_festivos(): void
    {
        // Viernes 13 → el siguiente hábil es el martes 17 (lunes 16 es festivo).
        $this->assertSame('2026-11-17', $this->calendario->siguienteDiaHabil(Carbon::parse('2026-11-13'))->toDateString());

        // Martes 17 → el anterior hábil es el viernes 13.
        $this->assertSame('2026-11-13', $this->calendario->anteriorDiaHabil(Carbon::parse('2026-11-17'))->toDateString());

        // Lunes 4 de enero de 2027 → el anterior hábil es el jueves 31 de diciembre (cambia de año).
        $this->assertSame('2026-12-31', $this->calendario->anteriorDiaHabil(Carbon::parse('2027-01-04'))->toDateString());
    }

    public function test_ventana_del_dependiente_en_un_dia_normal(): void
    {
        // Miércoles 11 de noviembre de 2026.
        $this->assertSame(
            ['2026-11-10', '2026-11-11', '2026-11-12'],
            $this->calendario->fechasPermitidasDependiente(Carbon::parse('2026-11-11'))
        );
    }

    public function test_ventana_del_dependiente_un_viernes_antes_de_puente(): void
    {
        // Viernes 13: jueves 12, viernes 13 y martes 17 (el lunes 16 es festivo).
        $this->assertSame(
            ['2026-11-12', '2026-11-13', '2026-11-17'],
            $this->calendario->fechasPermitidasDependiente(Carbon::parse('2026-11-13'))
        );
    }

    public function test_ventana_del_dependiente_en_fin_de_semana_no_incluye_hoy(): void
    {
        // Sábado 14: solo el viernes 13 y el martes 17.
        $this->assertSame(
            ['2026-11-13', '2026-11-17'],
            $this->calendario->fechasPermitidasDependiente(Carbon::parse('2026-11-14'))
        );
    }
}
