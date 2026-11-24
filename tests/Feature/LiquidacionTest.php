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

// Actividad 19: liquidar desde los registros del dependiente.
class LiquidacionTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;
    private Usuario $dependiente;
    private Responsable $savio;
    private Responsable $abogado;
    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-23 10:00'));

        $this->admin = $this->crearUsuario('administrador', '1');
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

    private function crearRegistro(string $fecha, string $inicio, string $fin, array $otros = []): RegistroHora
    {
        return RegistroHora::create(array_merge([
            'usuario_id' => $this->dependiente->id,
            'responsable_id' => $this->savio->id,
            'item_id' => $this->item->id,
            'fecha' => $fecha,
            'hora_inicio' => $inicio,
            'hora_fin' => $fin,
            'radicado' => 'RAD-'.$fecha.'-'.$inicio,
            'cliente' => 'Cliente',
        ], $otros));
    }

    private function rango(string $desde = '2026-11-01', string $hasta = '2026-11-15'): array
    {
        return ['desde' => $desde, 'hasta' => $hasta];
    }

    public function test_la_confirmacion_muestra_totales_sin_liquidar_nada(): void
    {
        $this->crearRegistro('2026-11-03', '08:00', '10:00');
        $this->crearRegistro('2026-11-04', '08:00', '09:30', ['responsable_id' => $this->abogado->id]);

        $this->actingAs($this->admin)
            ->get(route('liquidaciones.confirmar', [$this->dependiente, ...$this->rango()]))
            ->assertOk()
            ->assertSee('Confirmar liquidación')
            ->assertSee('Dr. Gómez')
            ->assertViewHas('totales', ['horas' => 3.5, 'valor' => 35000.0]);

        $this->assertSame(0, Liquidacion::count());
        $this->assertSame(2, RegistroHora::whereNull('liquidacion_id')->count());
    }

    public function test_liquida_solo_los_pendientes_del_rango_y_congela_los_totales(): void
    {
        $dentro1 = $this->crearRegistro('2026-11-03', '08:00', '10:00');
        $dentro2 = $this->crearRegistro('2026-11-13', '14:00', '14:20');
        $fuera = $this->crearRegistro('2026-11-17', '08:00', '12:00');

        $this->actingAs($this->admin)
            ->post(route('liquidaciones.store', $this->dependiente), $this->rango())
            ->assertRedirect(route('usuarios.show', $this->dependiente))
            ->assertSessionHas('exito');

        $liquidacion = Liquidacion::sole();
        $this->assertSame($this->dependiente->id, $liquidacion->usuario_id);
        $this->assertSame('2026-11-23', $liquidacion->fecha_finalizacion->toDateString());
        $this->assertEquals(2.33, $liquidacion->total_horas);
        $this->assertEquals(23333.33, $liquidacion->total_pagado);

        $this->assertSame($liquidacion->id, $dentro1->fresh()->liquidacion_id);
        $this->assertSame($liquidacion->id, $dentro2->fresh()->liquidacion_id);
        $this->assertNull($fuera->fresh()->liquidacion_id);

        // Aunque después cambie el valor de la hora, lo liquidado no cambia.
        $this->dependiente->valoresHora()->create(['valor' => 20000, 'vigente_desde' => '2026-11-01']);
        $this->assertEquals(23333.33, $liquidacion->fresh()->total_pagado);
    }

    public function test_usa_el_valor_vigente_en_la_fecha_de_cada_registro(): void
    {
        $this->dependiente->valoresHora()->create(['valor' => 12000, 'vigente_desde' => '2026-11-10']);
        $this->crearRegistro('2026-11-09', '08:00', '09:00');
        $this->crearRegistro('2026-11-10', '08:00', '09:00');

        $this->actingAs($this->admin)->post(route('liquidaciones.store', $this->dependiente), $this->rango());

        $this->assertEquals(22000, Liquidacion::sole()->total_pagado);
    }

    public function test_no_vuelve_a_liquidar_lo_ya_liquidado(): void
    {
        $this->crearRegistro('2026-11-03', '08:00', '10:00');

        $this->actingAs($this->admin)->post(route('liquidaciones.store', $this->dependiente), $this->rango());
        $this->actingAs($this->admin)->post(route('liquidaciones.store', $this->dependiente), $this->rango())
            ->assertSessionHas('error', 'No hay registros pendientes para liquidar en ese rango.');

        $this->assertSame(1, Liquidacion::count());
    }

    public function test_un_registro_nuevo_de_una_quincena_pagada_entra_en_la_siguiente_liquidacion(): void
    {
        $this->crearRegistro('2026-11-03', '08:00', '10:00');
        $this->actingAs($this->admin)->post(route('liquidaciones.store', $this->dependiente), $this->rango());

        $tardio = $this->crearRegistro('2026-11-05', '08:00', '09:00');
        $this->actingAs($this->admin)->post(route('liquidaciones.store', $this->dependiente), $this->rango('2026-11-01', '2026-11-30'));

        $this->assertSame(2, Liquidacion::count());
        $this->assertEquals(1, Liquidacion::latest('id')->first()->total_horas);
        $this->assertNotNull($tardio->fresh()->liquidacion_id);
    }

    public function test_el_rango_es_obligatorio_pero_puede_ser_de_cualquier_tamano(): void
    {
        $this->crearRegistro('2026-03-02', '08:00', '10:00');
        $this->crearRegistro('2026-11-03', '08:00', '10:00');

        $this->actingAs($this->admin)
            ->post(route('liquidaciones.store', $this->dependiente), ['desde' => '2026-11-01'])
            ->assertSessionHasErrors('hasta');

        $this->actingAs($this->admin)
            ->get(route('liquidaciones.confirmar', $this->dependiente))
            ->assertSessionHasErrors(['desde', 'hasta']);

        $this->actingAs($this->admin)
            ->post(route('liquidaciones.store', $this->dependiente), $this->rango('2026-11-15', '2026-11-01'))
            ->assertSessionHasErrors('hasta');

        $this->assertSame(0, Liquidacion::count());

        // Un rango de casi un año también vale.
        $this->actingAs($this->admin)
            ->post(route('liquidaciones.store', $this->dependiente), $this->rango('2026-01-01', '2026-11-30'))
            ->assertSessionHas('exito');

        $this->assertEquals(4, Liquidacion::sole()->total_horas);
    }

    public function test_el_boton_liquidar_solo_aparece_con_rango_y_pendientes(): void
    {
        $this->crearRegistro('2026-11-03', '08:00', '10:00');

        $this->actingAs($this->admin)->get(route('usuarios.show', $this->dependiente))
            ->assertDontSee('Liquidar pendientes del rango')
            ->assertSee('Para liquidar, filtra un rango de fechas');

        $this->actingAs($this->admin)->get(route('usuarios.show', [$this->dependiente, ...$this->rango()]))
            ->assertSee('Liquidar pendientes del rango');

        $this->actingAs($this->admin)->get(route('usuarios.show', [$this->dependiente, ...$this->rango('2026-11-16', '2026-11-30')]))
            ->assertDontSee('Liquidar pendientes del rango');
    }

    public function test_sin_pendientes_la_confirmacion_regresa_con_error(): void
    {
        $this->actingAs($this->admin)
            ->get(route('liquidaciones.confirmar', [$this->dependiente, ...$this->rango()]))
            ->assertRedirect(route('usuarios.show', $this->dependiente))
            ->assertSessionHas('error');
    }

    public function test_se_puede_liquidar_a_un_usuario_inactivo(): void
    {
        $this->crearRegistro('2026-11-03', '08:00', '10:00');
        $this->dependiente->update(['activo' => false]);

        $this->actingAs($this->admin)->post(route('liquidaciones.store', $this->dependiente), $this->rango())
            ->assertSessionHas('exito');

        $this->assertSame(1, Liquidacion::count());
    }

    public function test_sin_valor_de_la_hora_vigente_no_liquida_nada(): void
    {
        $this->crearRegistro('2025-12-15', '08:00', '10:00');

        $this->actingAs($this->admin)
            ->get(route('liquidaciones.confirmar', [$this->dependiente, ...$this->rango('2025-12-01', '2025-12-31')]))
            ->assertRedirect(route('usuarios.show', $this->dependiente))
            ->assertSessionHas('error');

        $this->assertSame(0, Liquidacion::count());
        $this->assertNull(RegistroHora::first()->liquidacion_id);
    }

    public function test_un_dependiente_no_puede_liquidar(): void
    {
        $this->crearRegistro('2026-11-03', '08:00', '10:00');

        $this->actingAs($this->dependiente)
            ->get(route('liquidaciones.confirmar', [$this->dependiente, ...$this->rango()]))
            ->assertForbidden();

        $this->actingAs($this->dependiente)
            ->post(route('liquidaciones.store', $this->dependiente), $this->rango())
            ->assertForbidden();

        $this->assertSame(0, Liquidacion::count());
    }

    public function test_un_administrador_no_se_liquida(): void
    {
        $this->actingAs($this->admin)
            ->post(route('liquidaciones.store', $this->admin), $this->rango())
            ->assertNotFound();
    }
}
