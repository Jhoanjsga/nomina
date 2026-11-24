<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Liquidacion;
use App\Models\RegistroHora;
use App\Models\Responsable;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Actividad 21: reportes del administrador y lo que debe devolver cada abogado.
class ReportesTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;
    private Usuario $ana;
    private Usuario $beto;
    private Responsable $savio;
    private Responsable $gomez;
    private Item $audiencia;
    private Item $memorial;

    protected function setUp(): void
    {
        parent::setUp();

        // Lunes 23 de noviembre de 2026: la quincena actual es del 16 al 30.
        $this->travelTo(Carbon::parse('2026-11-23 10:00'));

        $this->admin = $this->crearUsuario('administrador', '1');
        $this->ana = $this->crearUsuario('dependiente', '2', 'Ana');
        $this->beto = $this->crearUsuario('dependiente', '3', 'Beto');
        $this->ana->valoresHora()->create(['valor' => 10000, 'vigente_desde' => '2026-01-01']);
        $this->beto->valoresHora()->create(['valor' => 20000, 'vigente_desde' => '2026-01-01']);

        $this->savio = Responsable::create(['nombre' => 'Savio', 'tipo' => 'savio', 'activo' => true]);
        $this->gomez = Responsable::create(['nombre' => 'Dr. Gómez', 'tipo' => 'abogado', 'activo' => true]);
        $this->audiencia = Item::create(['nombre' => 'Audiencia', 'activo' => true]);
        $this->memorial = Item::create(['nombre' => 'Memorial', 'activo' => true]);
    }

    private function crearUsuario(string $rol, string $cedula, ?string $nombre = null): Usuario
    {
        return Usuario::create([
            'nombre' => $nombre ?? 'Usuario '.$cedula,
            'cedula' => $cedula,
            'correo' => 'usuario'.$cedula.'@savio.test',
            'telefono' => '3000000000',
            'contrasena' => 'Clave1234',
            'rol' => $rol,
            'activo' => true,
        ]);
    }

    private function crearRegistro(Usuario $usuario, string $fecha, string $inicio, string $fin, array $otros = []): RegistroHora
    {
        return RegistroHora::create(array_merge([
            'usuario_id' => $usuario->id,
            'responsable_id' => $this->savio->id,
            'item_id' => $this->audiencia->id,
            'fecha' => $fecha,
            'hora_inicio' => $inicio,
            'hora_fin' => $fin,
            'radicado' => 'RAD-'.$usuario->id.'-'.$fecha.'-'.$inicio,
            'cliente' => 'Cliente',
        ], $otros));
    }

    public function test_por_defecto_muestra_la_quincena_actual(): void
    {
        $this->crearRegistro($this->ana, '2026-11-13', '08:00', '09:00', ['radicado' => 'RAD-ANTERIOR']);
        $this->crearRegistro($this->ana, '2026-11-17', '08:00', '09:00', ['radicado' => 'RAD-ACTUAL']);

        $this->actingAs($this->admin)->get(route('reportes.index'))
            ->assertOk()
            ->assertViewHas('filtros', fn ($filtros) => $filtros['desde'] === '2026-11-16' && $filtros['hasta'] === '2026-11-30')
            ->assertSee('RAD-ACTUAL')
            ->assertDontSee('RAD-ANTERIOR');
    }

    public function test_filtra_por_dependiente_item_y_estado(): void
    {
        $this->crearRegistro($this->ana, '2026-11-17', '08:00', '09:00', ['radicado' => 'RAD-ANA']);
        $this->crearRegistro($this->beto, '2026-11-17', '08:00', '09:00', ['radicado' => 'RAD-BETO']);
        $this->crearRegistro($this->ana, '2026-11-18', '08:00', '09:00', ['radicado' => 'RAD-MEMORIAL', 'item_id' => $this->memorial->id]);

        $liquidacion = Liquidacion::create([
            'usuario_id' => $this->ana->id, 'fecha_finalizacion' => '2026-11-20', 'total_horas' => 1, 'total_pagado' => 10000,
        ]);
        $this->crearRegistro($this->ana, '2026-11-19', '08:00', '09:00', ['radicado' => 'RAD-PAGADO', 'liquidacion_id' => $liquidacion->id]);

        $this->actingAs($this->admin)->get(route('reportes.index', ['usuario_id' => $this->beto->id]))
            ->assertSee('RAD-BETO')
            ->assertDontSee('RAD-ANA');

        $this->actingAs($this->admin)->get(route('reportes.index', ['item_id' => $this->memorial->id]))
            ->assertSee('RAD-MEMORIAL')
            ->assertDontSee('RAD-BETO');

        $this->actingAs($this->admin)->get(route('reportes.index', ['estado' => 'liquidados']))
            ->assertSee('RAD-PAGADO')
            ->assertDontSee('RAD-MEMORIAL');
    }

    public function test_reporte_de_un_dia(): void
    {
        $this->crearRegistro($this->ana, '2026-11-17', '08:00', '09:00', ['radicado' => 'RAD-DIA-17']);
        $this->crearRegistro($this->ana, '2026-11-18', '08:00', '09:00', ['radicado' => 'RAD-DIA-18']);

        $this->actingAs($this->admin)->get(route('reportes.index', ['desde' => '2026-11-18', 'hasta' => '2026-11-18']))
            ->assertSee('RAD-DIA-18')
            ->assertDontSee('RAD-DIA-17');
    }

    public function test_resumen_por_responsable_incluye_a_savio_y_usa_el_valor_vigente(): void
    {
        // Ana sube a 12.000 desde el 18: el registro del 17 va a 10.000 y el del 18 a 12.000.
        $this->ana->valoresHora()->create(['valor' => 12000, 'vigente_desde' => '2026-11-18']);

        $this->crearRegistro($this->ana, '2026-11-17', '08:00', '09:00', ['responsable_id' => $this->gomez->id]);
        $this->crearRegistro($this->ana, '2026-11-18', '08:00', '09:00', ['responsable_id' => $this->gomez->id]);
        $this->crearRegistro($this->beto, '2026-11-17', '08:00', '10:00', ['responsable_id' => $this->gomez->id]);
        $this->crearRegistro($this->ana, '2026-11-19', '08:00', '09:30');

        $this->actingAs($this->admin)->get(route('reportes.index'))
            ->assertViewHas('resumen', fn ($resumen) => $resumen->values()->all() === [
                ['responsable' => 'Dr. Gómez', 'horas' => 4.0, 'valor' => 62000.0],
                ['responsable' => 'Savio', 'horas' => 1.5, 'valor' => 18000.0],
            ])
            ->assertViewHas('totales', ['horas' => 5.5, 'valor' => 80000.0]);
    }

    public function test_exporta_a_excel_con_el_resumen(): void
    {
        $this->crearRegistro($this->ana, '2026-11-17', '08:00', '09:00', ['responsable_id' => $this->gomez->id, 'cliente' => 'Señora Peña']);
        $this->crearRegistro($this->beto, '2026-11-17', '08:00', '09:00');

        $respuesta = $this->actingAs($this->admin)
            ->get(route('reportes.excel', ['desde' => '2026-11-16', 'hasta' => '2026-11-30']));

        $respuesta->assertOk()->assertDownload('reporte-2026-11-16-a-2026-11-30.csv');

        $contenido = $respuesta->streamedContent();
        $this->assertStringContainsString('Señora Peña', $contenido);
        $this->assertStringContainsString('Ana', $contenido);
        $this->assertStringContainsString('Total;;;;;;;;;;2,00;30000,00', $contenido);
        $this->assertStringContainsString('"Dr. Gómez";1,00;10000,00', $contenido);
        $this->assertStringContainsString('Savio;1,00;20000,00', $contenido);
    }

    public function test_la_pagina_para_pdf_muestra_filtros_y_resumen(): void
    {
        $this->crearRegistro($this->ana, '2026-11-17', '08:00', '09:00', ['responsable_id' => $this->gomez->id, 'radicado' => 'RAD-IMPRIMIR']);

        $this->actingAs($this->admin)->get(route('reportes.imprimir', ['usuario_id' => $this->ana->id]))
            ->assertOk()
            ->assertSee('Guardar como PDF')
            ->assertSee('Dependiente: Ana')
            ->assertSee('Dr. Gómez')
            ->assertSee('RAD-IMPRIMIR')
            ->assertDontSee('<script', false);
    }

    public function test_rechaza_un_rango_invertido(): void
    {
        $this->actingAs($this->admin)->get(route('reportes.index', ['desde' => '2026-11-30', 'hasta' => '2026-11-16']))
            ->assertSessionHasErrors('hasta');
    }

    public function test_un_dependiente_no_puede_ver_reportes(): void
    {
        $this->actingAs($this->ana)->get(route('reportes.index'))->assertForbidden();
        $this->actingAs($this->ana)->get(route('reportes.excel'))->assertForbidden();
        $this->actingAs($this->ana)->get(route('reportes.imprimir'))->assertForbidden();
    }
}
