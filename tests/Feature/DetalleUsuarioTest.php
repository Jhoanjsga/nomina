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

// Actividad 16: detalle de usuario para el administrador.
class DetalleUsuarioTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;
    private Usuario $dependiente;
    private Responsable $savio;
    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        // Miércoles 11 de noviembre de 2026.
        $this->travelTo(Carbon::parse('2026-11-11 10:00'));

        $this->admin = $this->crearUsuario('administrador', '1');
        $this->dependiente = $this->crearUsuario('dependiente', '2');
        $this->dependiente->valoresHora()->create(['valor' => 10000, 'vigente_desde' => '2026-01-01']);

        $this->savio = Responsable::create(['nombre' => 'Savio', 'tipo' => 'savio', 'activo' => true]);
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

    private function registroLiquidado(): RegistroHora
    {
        $liquidacion = Liquidacion::create([
            'usuario_id' => $this->dependiente->id,
            'fecha_finalizacion' => '2026-10-31',
            'total_horas' => 2,
            'total_pagado' => 20000,
        ]);

        return $this->crearRegistro('2026-10-30', '08:00', '10:00', [
            'radicado' => 'RAD-LIQUIDADO',
            'liquidacion_id' => $liquidacion->id,
        ]);
    }

    private function datosRegistro(array $cambios = []): array
    {
        return array_merge([
            'fecha' => '2026-11-11',
            'hora_inicio' => '08:00',
            'hora_fin' => '10:00',
            'responsable_id' => $this->savio->id,
            'item_id' => $this->item->id,
            'radicado' => 'RAD-NUEVO',
            'cliente' => 'Cliente nuevo',
            'observacion' => '',
        ], $cambios);
    }

    public function test_por_defecto_muestra_todos_los_pendientes(): void
    {
        $this->crearRegistro('2026-03-02', '08:00', '09:00', ['radicado' => 'RAD-MARZO']);
        $this->crearRegistro('2026-11-10', '08:00', '10:00', ['radicado' => 'RAD-NOVIEMBRE']);
        $this->registroLiquidado();

        $this->actingAs($this->admin)->get(route('usuarios.show', $this->dependiente))
            ->assertOk()
            ->assertSee('RAD-MARZO')
            ->assertSee('RAD-NOVIEMBRE')
            ->assertDontSee('RAD-LIQUIDADO')
            ->assertViewHas('totales', ['horas' => 3.0, 'valor' => 30000.0]);
    }

    public function test_filtra_por_estado_y_por_fechas(): void
    {
        $this->crearRegistro('2026-11-03', '08:00', '09:00', ['radicado' => 'RAD-DIA-3']);
        $this->crearRegistro('2026-11-10', '08:00', '09:00', ['radicado' => 'RAD-DIA-10']);
        $this->registroLiquidado();

        $this->actingAs($this->admin)->get(route('usuarios.show', [$this->dependiente, 'estado' => 'liquidados']))
            ->assertSee('RAD-LIQUIDADO')
            ->assertDontSee('RAD-DIA-3');

        $this->actingAs($this->admin)
            ->get(route('usuarios.show', [$this->dependiente, 'estado' => 'todos', 'desde' => '2026-11-01', 'hasta' => '2026-11-05']))
            ->assertSee('RAD-DIA-3')
            ->assertDontSee('RAD-DIA-10')
            ->assertDontSee('RAD-LIQUIDADO');
    }

    public function test_rechaza_un_rango_invertido(): void
    {
        $this->actingAs($this->admin)
            ->get(route('usuarios.show', [$this->dependiente, 'desde' => '2026-11-15', 'hasta' => '2026-11-01']))
            ->assertSessionHasErrors('hasta');
    }

    public function test_el_detalle_funciona_con_un_usuario_inactivo(): void
    {
        $this->dependiente->update(['activo' => false]);
        $this->crearRegistro('2026-11-10', '08:00', '10:00', ['radicado' => 'RAD-INACTIVO']);

        $this->actingAs($this->admin)->get(route('usuarios.show', $this->dependiente))
            ->assertOk()
            ->assertSee('Inactivo')
            ->assertSee('RAD-INACTIVO');

        // También se le pueden agregar registros (decisión acordada).
        $this->actingAs($this->admin)
            ->post(route('registros.store', $this->dependiente), $this->datosRegistro())
            ->assertSessionHasNoErrors();
    }

    public function test_un_administrador_no_tiene_detalle(): void
    {
        $this->actingAs($this->admin)->get(route('usuarios.show', $this->admin))->assertNotFound();
    }

    public function test_un_dependiente_no_puede_entrar_al_detalle_ni_tocar_registros(): void
    {
        $registro = $this->crearRegistro('2026-11-10', '08:00', '10:00');

        $this->actingAs($this->dependiente)->get(route('usuarios.show', $this->dependiente))->assertForbidden();
        $this->actingAs($this->dependiente)->delete(route('registros.destroy', $registro))->assertForbidden();
        $this->assertNotNull($registro->fresh());
    }

    public function test_la_pagina_de_registros_ya_no_tiene_formulario_de_agregar(): void
    {
        $this->actingAs($this->admin)->get(route('usuarios.show', $this->dependiente))
            ->assertOk()
            ->assertDontSee('Agregar registro');
    }

    public function test_agregar_registro_muestra_el_formulario_y_vuelve_a_el_al_guardar(): void
    {
        $this->actingAs($this->admin)->get(route('registros.create', $this->dependiente))
            ->assertOk()
            ->assertSee('Agregar registro a Usuario 2');

        $this->actingAs($this->admin)
            ->post(route('registros.store', $this->dependiente), $this->datosRegistro())
            ->assertRedirect(route('registros.create', $this->dependiente))
            ->assertSessionHas('exito');

        $this->assertSame(1, RegistroHora::count());
    }

    public function test_un_administrador_no_tiene_formulario_de_agregar_registro(): void
    {
        $this->actingAs($this->admin)->get(route('registros.create', $this->admin))->assertNotFound();
    }

    public function test_el_administrador_registra_fuera_de_la_ventana_del_dependiente(): void
    {
        // Un mes atrás: el dependiente no podría, el administrador sí.
        $this->actingAs($this->admin)
            ->post(route('registros.store', $this->dependiente), $this->datosRegistro(['fecha' => '2026-10-13']))
            ->assertSessionHasNoErrors();

        $registro = RegistroHora::first();
        $this->assertSame($this->dependiente->id, $registro->usuario_id);
        $this->assertNull($registro->liquidacion_id);
    }

    public function test_el_administrador_tambien_respeta_dia_habil_horario_y_cruces(): void
    {
        $this->crearRegistro('2026-11-10', '09:00', '11:00');

        $this->actingAs($this->admin)
            ->post(route('registros.store', $this->dependiente), $this->datosRegistro(['fecha' => '2026-11-14'])) // sábado
            ->assertSessionHasErrors('fecha');

        $this->actingAs($this->admin)
            ->post(route('registros.store', $this->dependiente), $this->datosRegistro(['hora_fin' => '18:30']))
            ->assertSessionHasErrors('hora_fin');

        $this->actingAs($this->admin)
            ->post(route('registros.store', $this->dependiente), $this->datosRegistro([
                'fecha' => '2026-11-10', 'hora_inicio' => '10:00', 'hora_fin' => '12:00',
            ]))
            ->assertSessionHasErrors('hora_inicio');
    }

    public function test_editar_un_registro_pendiente(): void
    {
        $registro = $this->crearRegistro('2026-11-10', '08:00', '10:00');

        $this->actingAs($this->admin)->get(route('registros.edit', $registro))->assertOk();

        $this->actingAs($this->admin)
            ->put(route('registros.update', $registro), $this->datosRegistro([
                'fecha' => '2026-11-10', 'hora_inicio' => '08:00', 'hora_fin' => '11:00', 'cliente' => 'Cliente corregido',
            ]))
            ->assertRedirect(route('usuarios.show', $this->dependiente));

        $registro->refresh();
        $this->assertSame('11:00:00', $registro->hora_fin);
        $this->assertSame('Cliente corregido', $registro->cliente);
    }

    public function test_al_editar_no_se_cruza_consigo_mismo_pero_si_con_otros(): void
    {
        $registro = $this->crearRegistro('2026-11-10', '08:00', '10:00');
        $this->crearRegistro('2026-11-10', '10:00', '12:00');

        // Mover dentro de su propio horario: permitido.
        $this->actingAs($this->admin)
            ->put(route('registros.update', $registro), $this->datosRegistro([
                'fecha' => '2026-11-10', 'hora_inicio' => '08:30', 'hora_fin' => '10:00',
            ]))
            ->assertSessionHasNoErrors();

        // Alargarlo encima del otro registro: no.
        $this->actingAs($this->admin)
            ->put(route('registros.update', $registro), $this->datosRegistro([
                'fecha' => '2026-11-10', 'hora_inicio' => '08:30', 'hora_fin' => '11:00',
            ]))
            ->assertSessionHasErrors('hora_inicio');
    }

    public function test_al_editar_se_conserva_un_responsable_inactivo_pero_no_se_elige_otro_inactivo(): void
    {
        $abogado = Responsable::create(['nombre' => 'Dr. Retirado', 'tipo' => 'abogado', 'activo' => true]);
        $registro = $this->crearRegistro('2026-11-10', '08:00', '10:00', ['responsable_id' => $abogado->id]);
        $abogado->update(['activo' => false]);
        $otroInactivo = Responsable::create(['nombre' => 'Dr. Otro', 'tipo' => 'abogado', 'activo' => false]);

        $this->actingAs($this->admin)
            ->put(route('registros.update', $registro), $this->datosRegistro([
                'fecha' => '2026-11-10', 'responsable_id' => $abogado->id,
            ]))
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin)
            ->put(route('registros.update', $registro), $this->datosRegistro([
                'fecha' => '2026-11-10', 'responsable_id' => $otroInactivo->id,
            ]))
            ->assertSessionHasErrors('responsable_id');

        // Al crear, el inactivo no se acepta.
        $this->actingAs($this->admin)
            ->post(route('registros.store', $this->dependiente), $this->datosRegistro(['responsable_id' => $abogado->id]))
            ->assertSessionHasErrors('responsable_id');
    }

    public function test_eliminar_un_registro_pendiente(): void
    {
        $registro = $this->crearRegistro('2026-11-10', '08:00', '10:00');

        $this->actingAs($this->admin)->delete(route('registros.destroy', $registro))
            ->assertSessionHas('exito');

        $this->assertNull($registro->fresh());
    }

    public function test_un_registro_liquidado_no_se_edita_ni_se_elimina(): void
    {
        $registro = $this->registroLiquidado();
        $mensaje = 'Este registro ya fue liquidado y no se puede modificar.';

        $this->actingAs($this->admin)->get(route('registros.edit', $registro))
            ->assertRedirect(route('usuarios.show', $this->dependiente))
            ->assertSessionHas('error', $mensaje);

        $this->actingAs($this->admin)
            ->put(route('registros.update', $registro), $this->datosRegistro(['fecha' => '2026-10-30', 'cliente' => 'Cambiado']))
            ->assertSessionHas('error', $mensaje);

        $this->actingAs($this->admin)->delete(route('registros.destroy', $registro))
            ->assertSessionHas('error', $mensaje);

        $registro->refresh();
        $this->assertSame('Cliente', $registro->cliente);
        $this->assertNotNull($registro->liquidacion_id);
    }
}
