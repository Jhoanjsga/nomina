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

// Actividad 15: "Mis horas activas" y "Mis reportes" del dependiente.
class MisHorasTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $dependiente;
    private Responsable $savio;
    private Responsable $abogado;
    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        // Miércoles 11 de noviembre de 2026: la quincena actual va del 1 al 15.
        $this->travelTo(Carbon::parse('2026-11-11 10:00'));

        $this->dependiente = $this->crearUsuario('dependiente', '2');
        $this->dependiente->valoresHora()->create(['valor' => 10000, 'vigente_desde' => '2026-01-01']);

        $this->savio = Responsable::create(['nombre' => 'Savio', 'tipo' => 'savio', 'activo' => true]);
        $this->abogado = Responsable::create(['nombre' => 'Dr. Gómez', 'tipo' => 'abogado', 'activo' => true]);
        $this->item = Item::create(['nombre' => 'Audiencia', 'activo' => true]);
    }

    private function crearUsuario(string $rol, string $cedula): Usuario
    {
        return Usuario::create([
            'nombre' => 'Usuario '.$cedula,
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
            'item_id' => $this->item->id,
            'fecha' => $fecha,
            'hora_inicio' => $inicio,
            'hora_fin' => $fin,
            'radicado' => 'RAD-'.$fecha.'-'.$inicio,
            'cliente' => 'Cliente',
        ], $otros));
    }

    private function liquidacion(): Liquidacion
    {
        return Liquidacion::create([
            'usuario_id' => $this->dependiente->id,
            'fecha_finalizacion' => '2026-10-31',
            'total_horas' => 2,
            'total_pagado' => 20000,
        ]);
    }

    public function test_el_inicio_muestra_solo_pendientes_con_sus_totales(): void
    {
        $this->crearRegistro($this->dependiente, '2026-11-10', '08:00', '10:00', ['radicado' => 'RAD-PENDIENTE']);
        $this->crearRegistro($this->dependiente, '2026-11-11', '14:00', '15:30', ['responsable_id' => $this->abogado->id]);
        $this->crearRegistro($this->dependiente, '2026-10-30', '08:00', '10:00', [
            'radicado' => 'RAD-LIQUIDADO',
            'liquidacion_id' => $this->liquidacion()->id,
        ]);

        $respuesta = $this->actingAs($this->dependiente)->get(route('inicio'));

        $respuesta->assertOk()
            ->assertSee('RAD-PENDIENTE')
            ->assertDontSee('RAD-LIQUIDADO')
            ->assertViewHas('totales', ['horas' => 3.5, 'valor' => 35000.0]);

        // Resumen: Dr. Gómez 1.5 h y Savio 2 h.
        $resumen = $respuesta->viewData('resumen')->all();
        $this->assertSame([
            ['responsable' => 'Dr. Gómez', 'horas' => 1.5, 'valor' => 15000.0],
            ['responsable' => 'Savio', 'horas' => 2.0, 'valor' => 20000.0],
        ], $resumen);
    }

    public function test_no_ve_registros_de_otros_dependientes(): void
    {
        $otro = $this->crearUsuario('dependiente', '3');
        $otro->valoresHora()->create(['valor' => 10000, 'vigente_desde' => '2026-01-01']);
        $this->crearRegistro($otro, '2026-11-10', '08:00', '10:00', ['radicado' => 'RAD-AJENO']);

        $this->actingAs($this->dependiente)->get(route('inicio'))
            ->assertOk()
            ->assertDontSee('RAD-AJENO')
            ->assertViewHas('totales', ['horas' => 0.0, 'valor' => 0.0]);

        $this->actingAs($this->dependiente)->get(route('mis-reportes.index'))
            ->assertOk()
            ->assertDontSee('RAD-AJENO');
    }

    public function test_reportes_sin_fechas_muestra_la_quincena_actual_con_pendientes_y_liquidados(): void
    {
        $this->crearRegistro($this->dependiente, '2026-11-03', '08:00', '10:00', [
            'radicado' => 'RAD-LIQUIDADO',
            'liquidacion_id' => $this->liquidacion()->id,
        ]);
        $this->crearRegistro($this->dependiente, '2026-11-10', '08:00', '09:00', ['radicado' => 'RAD-PENDIENTE']);
        $this->crearRegistro($this->dependiente, '2026-10-30', '08:00', '09:00', ['radicado' => 'RAD-OCTUBRE']);

        $this->actingAs($this->dependiente)->get(route('mis-reportes.index'))
            ->assertOk()
            ->assertViewHas('desde', '2026-11-01')
            ->assertViewHas('hasta', '2026-11-15')
            ->assertSee('RAD-LIQUIDADO')
            ->assertSee('Liquidado')
            ->assertSee('RAD-PENDIENTE')
            ->assertDontSee('RAD-OCTUBRE')
            ->assertViewHas('totales', ['horas' => 3.0, 'valor' => 30000.0]);
    }

    public function test_reportes_filtra_por_el_rango_elegido_incluyendo_los_extremos(): void
    {
        $this->crearRegistro($this->dependiente, '2026-10-01', '08:00', '09:00', ['radicado' => 'RAD-INICIO']);
        $this->crearRegistro($this->dependiente, '2026-10-30', '08:00', '09:00', ['radicado' => 'RAD-FIN']);
        $this->crearRegistro($this->dependiente, '2026-11-03', '08:00', '09:00', ['radicado' => 'RAD-FUERA']);

        $this->actingAs($this->dependiente)
            ->get(route('mis-reportes.index', ['desde' => '2026-10-01', 'hasta' => '2026-10-30']))
            ->assertOk()
            ->assertSee('RAD-INICIO')
            ->assertSee('RAD-FIN')
            ->assertDontSee('RAD-FUERA');
    }

    public function test_reportes_filtra_por_estado(): void
    {
        $this->crearRegistro($this->dependiente, '2026-11-03', '08:00', '10:00', [
            'radicado' => 'RAD-LIQUIDADO',
            'liquidacion_id' => $this->liquidacion()->id,
        ]);
        $this->crearRegistro($this->dependiente, '2026-11-10', '08:00', '09:00', ['radicado' => 'RAD-PENDIENTE']);

        $this->actingAs($this->dependiente)->get(route('mis-reportes.index', ['estado' => 'pendientes']))
            ->assertSee('RAD-PENDIENTE')
            ->assertDontSee('RAD-LIQUIDADO')
            ->assertViewHas('totales', ['horas' => 1.0, 'valor' => 10000.0]);

        $this->actingAs($this->dependiente)->get(route('mis-reportes.index', ['estado' => 'liquidados']))
            ->assertSee('RAD-LIQUIDADO')
            ->assertDontSee('RAD-PENDIENTE')
            ->assertViewHas('totales', ['horas' => 2.0, 'valor' => 20000.0]);
    }

    public function test_reportes_rechaza_un_estado_que_no_existe(): void
    {
        $this->actingAs($this->dependiente)
            ->get(route('mis-reportes.index', ['estado' => 'borrados']))
            ->assertSessionHasErrors('estado');
    }

    public function test_agregar_registro_solo_muestra_el_formulario(): void
    {
        $this->crearRegistro($this->dependiente, '2026-11-10', '08:00', '10:00', ['radicado' => 'RAD-PENDIENTE']);

        $this->actingAs($this->dependiente)->get(route('mis-registros.create'))
            ->assertOk()
            ->assertSee('Registrar')
            ->assertDontSee('RAD-PENDIENTE');
    }

    public function test_reportes_rechaza_un_rango_invertido(): void
    {
        $this->actingAs($this->dependiente)
            ->get(route('mis-reportes.index', ['desde' => '2026-11-15', 'hasta' => '2026-11-01']))
            ->assertSessionHasErrors(['hasta' => 'La fecha final no puede ser anterior a la inicial.']);
    }

    public function test_el_administrador_no_entra_a_las_pantallas_del_dependiente(): void
    {
        $admin = $this->crearUsuario('administrador', '1');

        $this->actingAs($admin)->get(route('mis-registros.create'))->assertForbidden();
        $this->actingAs($admin)->get(route('mis-reportes.index'))->assertForbidden();
    }
}
