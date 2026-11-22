<?php

namespace Tests\Unit;

use App\Models\RegistroHora;
use App\Models\Responsable;
use App\Models\Usuario;
use App\Models\ValorHora;
use App\Services\CalculadoraPago;
use RuntimeException;
use Tests\TestCase;

// Los modelos se arman en memoria, sin guardarlos en la base de datos.
class CalculadoraPagoTest extends TestCase
{
    private CalculadoraPago $calculadora;
    private Usuario $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculadora = new CalculadoraPago();

        // Ganaba 10.000 en 2025 y desde el 1 de enero de 2026 gana 12.000.
        $this->usuario = new Usuario();
        $this->usuario->setRelation('valoresHora', collect([
            new ValorHora(['valor' => 10000, 'vigente_desde' => '2025-01-01']),
            new ValorHora(['valor' => 12000, 'vigente_desde' => '2026-01-01']),
        ]));
    }

    private function registro(string $fecha, string $inicio, string $fin, ?Responsable $responsable = null): RegistroHora
    {
        $registro = new RegistroHora(['fecha' => $fecha, 'hora_inicio' => $inicio, 'hora_fin' => $fin]);
        $registro->setRelation('usuario', $this->usuario);

        if ($responsable) {
            $registro->responsable_id = $responsable->id;
            $registro->setRelation('responsable', $responsable);
        }

        return $registro;
    }

    public function test_calcula_las_horas_de_un_registro(): void
    {
        $this->assertSame(1.5, $this->calculadora->horas($this->registro('2026-03-02', '08:00', '09:30')));
        $this->assertSame(10.0, $this->calculadora->horas($this->registro('2026-03-02', '08:00', '18:00')));
    }

    public function test_usa_el_valor_vigente_en_la_fecha_del_registro(): void
    {
        $diciembre = $this->registro('2025-12-30', '08:00', '10:00');
        $enero = $this->registro('2026-01-05', '08:00', '10:00');

        $this->assertSame(20000.0, $this->calculadora->valor($diciembre));
        $this->assertSame(24000.0, $this->calculadora->valor($enero));
    }

    public function test_el_valor_aplica_desde_el_mismo_dia_de_vigencia(): void
    {
        $registro = $this->registro('2026-01-01', '08:00', '09:00');

        $this->assertSame(12000.0, $this->calculadora->valor($registro));
    }

    public function test_veinte_minutos_se_pagan_exactos(): void
    {
        // 20 minutos a 12.000 la hora = 4.000, no 0.33 x 12.000 = 3.960.
        $registro = $this->registro('2026-03-02', '08:00', '08:20');

        $this->assertSame(4000.0, $this->calculadora->valor($registro));
    }

    public function test_suma_los_totales_de_varios_registros(): void
    {
        $registros = collect([
            $this->registro('2025-12-30', '08:00', '10:00'), // 2 h x 10.000
            $this->registro('2026-01-05', '14:00', '15:30'), // 1.5 h x 12.000
        ]);

        $this->assertSame(['horas' => 3.5, 'valor' => 38000.0], $this->calculadora->totales($registros));
    }

    public function test_sin_registros_los_totales_son_cero(): void
    {
        $this->assertSame(['horas' => 0.0, 'valor' => 0.0], $this->calculadora->totales(collect()));
    }

    public function test_resumen_agrupa_horas_y_valor_por_responsable(): void
    {
        $savio = new Responsable(['nombre' => 'Savio']);
        $savio->id = 1;
        $abogado = new Responsable(['nombre' => 'Dr. Gómez']);
        $abogado->id = 2;

        $registros = collect([
            $this->registro('2026-03-02', '08:00', '10:00', $savio),
            $this->registro('2026-03-02', '10:00', '11:00', $abogado),
            $this->registro('2026-03-03', '08:00', '09:30', $savio),
        ]);

        // Ordenado por nombre del responsable.
        $this->assertSame([
            ['responsable' => 'Dr. Gómez', 'horas' => 1.0, 'valor' => 12000.0],
            ['responsable' => 'Savio', 'horas' => 3.5, 'valor' => 42000.0],
        ], $this->calculadora->resumenPorResponsable($registros)->all());
    }

    public function test_falla_si_no_hay_valor_vigente_para_la_fecha(): void
    {
        $registro = $this->registro('2024-06-10', '08:00', '09:00');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('El usuario no tiene valor de la hora vigente para el 2024-06-10.');

        $this->calculadora->valor($registro);
    }
}
