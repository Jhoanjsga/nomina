<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\RegistroHora;
use App\Models\Responsable;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsuariosTest extends TestCase
{
    use RefreshDatabase;

    private int $consecutivo = 0;

    // Crea un usuario de prueba con datos únicos.
    private function crearUsuario(array $datos = []): Usuario
    {
        $this->consecutivo++;

        return Usuario::create(array_merge([
            'nombre' => 'Usuario '.$this->consecutivo,
            'cedula' => '900'.$this->consecutivo,
            'correo' => 'usuario'.$this->consecutivo.'@savio.test',
            'telefono' => '3000000000',
            'contrasena' => 'Clave1234',
            'rol' => 'dependiente',
            'activo' => true,
        ], $datos));
    }

    private function administrador(): Usuario
    {
        return $this->crearUsuario(['rol' => 'administrador']);
    }

    private function datosNuevoDependiente(array $cambios = []): array
    {
        return array_merge([
            'nombre' => 'Ana Pérez',
            'cedula' => '1112223334',
            'correo' => 'ana@savio.test',
            'telefono' => '3101234567',
            'rol' => 'dependiente',
            'contrasena' => 'Clave1234',
            'contrasena_confirmation' => 'Clave1234',
            'valor_hora' => 10000,
            'vigente_desde' => '2026-01-01',
        ], $cambios);
    }

    public function test_un_dependiente_no_puede_entrar_a_usuarios(): void
    {
        $dependiente = $this->crearUsuario();

        $this->actingAs($dependiente)->get(route('usuarios.index'))->assertForbidden();
    }

    public function test_un_visitante_sin_sesion_es_enviado_al_login(): void
    {
        $this->get(route('usuarios.index'))->assertRedirect(route('login'));
    }

    public function test_las_pantallas_de_crear_y_editar_cargan(): void
    {
        $admin = $this->administrador();
        $usuario = $this->crearUsuario();
        $usuario->valoresHora()->create(['valor' => 9000, 'vigente_desde' => '2026-01-01']);

        $this->actingAs($admin)->get(route('usuarios.create'))->assertOk()->assertSee('Mínimo pactado');
        $this->actingAs($admin)->get(route('usuarios.edit', $usuario))->assertOk()->assertSee('$9.000');
    }

    public function test_la_lista_filtra_activos_e_inactivos(): void
    {
        $admin = $this->administrador();
        $this->crearUsuario(['nombre' => 'Activo Uno']);
        $this->crearUsuario(['nombre' => 'Inactivo Uno', 'activo' => false]);

        $this->actingAs($admin)->get(route('usuarios.index'))
            ->assertSee('Activo Uno')->assertDontSee('Inactivo Uno');

        $this->actingAs($admin)->get(route('usuarios.index', ['estado' => 'inactivos']))
            ->assertSee('Inactivo Uno')->assertDontSee('Activo Uno');
    }

    public function test_crear_dependiente_guarda_su_primer_valor_de_la_hora(): void
    {
        $admin = $this->administrador();

        $this->actingAs($admin)->post(route('usuarios.store'), $this->datosNuevoDependiente())
            ->assertRedirect(route('usuarios.index'));

        $nuevo = Usuario::where('correo', 'ana@savio.test')->first();
        $this->assertNotNull($nuevo);
        $this->assertCount(1, $nuevo->valoresHora);
        $this->assertEquals(10000, $nuevo->valoresHora->first()->valor);
    }

    public function test_no_se_crea_dependiente_con_valor_menor_al_minimo(): void
    {
        config(['nomina.valor_hora_minimo' => 8400]);
        $admin = $this->administrador();

        $this->actingAs($admin)
            ->post(route('usuarios.store'), $this->datosNuevoDependiente(['valor_hora' => 8399]))
            ->assertSessionHasErrors('valor_hora');

        $this->assertDatabaseMissing('usuarios', ['correo' => 'ana@savio.test']);
    }

    public function test_se_acepta_exactamente_el_valor_minimo(): void
    {
        config(['nomina.valor_hora_minimo' => 8400]);
        $admin = $this->administrador();

        $this->actingAs($admin)
            ->post(route('usuarios.store'), $this->datosNuevoDependiente(['valor_hora' => 8400]))
            ->assertSessionHasNoErrors();
    }

    public function test_un_administrador_puede_crear_otro_administrador_sin_valor_de_hora(): void
    {
        $admin = $this->administrador();

        $this->actingAs($admin)->post(route('usuarios.store'), $this->datosNuevoDependiente([
            'rol' => 'administrador',
            'valor_hora' => null,
            'vigente_desde' => null,
        ]))->assertSessionHasNoErrors();

        $nuevo = Usuario::where('correo', 'ana@savio.test')->first();
        $this->assertTrue($nuevo->esAdministrador());
        $this->assertCount(0, $nuevo->valoresHora);
    }

    public function test_los_errores_de_validacion_salen_en_espanol(): void
    {
        $this->actingAs($this->administrador())
            ->post(route('usuarios.store'), [])
            ->assertSessionHasErrors([
                'nombre' => 'El campo nombre es obligatorio.',
                'cedula' => 'El campo cédula es obligatorio.',
                'contrasena' => 'El campo contraseña es obligatorio.',
            ]);

        $this->actingAs($this->administrador())
            ->post(route('usuarios.store'), $this->datosNuevoDependiente(['correo' => 'no-es-correo']))
            ->assertSessionHasErrors(['correo' => 'El campo correo debe ser un correo electrónico válido.']);
    }

    public function test_no_se_repite_cedula_ni_correo(): void
    {
        $admin = $this->administrador();
        $this->crearUsuario(['cedula' => '1112223334', 'correo' => 'ana@savio.test']);

        $this->actingAs($admin)->post(route('usuarios.store'), $this->datosNuevoDependiente())
            ->assertSessionHasErrors(['cedula', 'correo']);
    }

    public function test_editar_sin_contrasena_conserva_la_actual(): void
    {
        $admin = $this->administrador();
        $usuario = $this->crearUsuario();
        $usuario->valoresHora()->create(['valor' => 9000, 'vigente_desde' => '2026-01-01']);
        $hashAnterior = $usuario->contrasena;

        $this->actingAs($admin)->put(route('usuarios.update', $usuario), [
            'nombre' => 'Nombre cambiado',
            'cedula' => $usuario->cedula,
            'correo' => $usuario->correo,
            'telefono' => $usuario->telefono,
            'rol' => 'dependiente',
            'contrasena' => '',
            'contrasena_confirmation' => '',
        ])->assertSessionHasNoErrors();

        $usuario->refresh();
        $this->assertSame('Nombre cambiado', $usuario->nombre);
        $this->assertSame($hashAnterior, $usuario->contrasena);
    }

    public function test_activar_e_inactivar_usuario(): void
    {
        $admin = $this->administrador();
        $usuario = $this->crearUsuario();

        $this->actingAs($admin)->patch(route('usuarios.estado', $usuario));
        $this->assertFalse($usuario->fresh()->activo);

        $this->actingAs($admin)->patch(route('usuarios.estado', $usuario));
        $this->assertTrue($usuario->fresh()->activo);
    }

    public function test_el_administrador_no_puede_inactivarse_ni_eliminarse_a_si_mismo(): void
    {
        $admin = $this->administrador();

        $this->actingAs($admin)->patch(route('usuarios.estado', $admin));
        $this->actingAs($admin)->delete(route('usuarios.destroy', $admin));

        $this->assertTrue($admin->fresh()->activo);
    }

    public function test_se_elimina_un_usuario_sin_registros_junto_con_su_historial(): void
    {
        $admin = $this->administrador();
        $usuario = $this->crearUsuario();
        $usuario->valoresHora()->create(['valor' => 9000, 'vigente_desde' => '2026-01-01']);

        $this->actingAs($admin)->get(route('usuarios.eliminar', $usuario))
            ->assertOk()
            ->assertSee('¿Seguro que deseas eliminar este usuario?');

        $this->actingAs($admin)->delete(route('usuarios.destroy', $usuario))
            ->assertRedirect(route('usuarios.index'));

        $this->assertDatabaseMissing('usuarios', ['id' => $usuario->id]);
        $this->assertDatabaseMissing('valores_hora', ['usuario_id' => $usuario->id]);
    }

    public function test_no_se_elimina_un_usuario_con_registros(): void
    {
        $admin = $this->administrador();
        $usuario = $this->crearUsuario();

        RegistroHora::create([
            'usuario_id' => $usuario->id,
            'responsable_id' => Responsable::create(['nombre' => 'Savio', 'tipo' => 'savio', 'activo' => true])->id,
            'item_id' => Item::create(['nombre' => 'Audiencia', 'activo' => true])->id,
            'fecha' => '2026-11-10',
            'hora_inicio' => '08:00',
            'hora_fin' => '10:00',
            'radicado' => '2026-001',
            'cliente' => 'Cliente de prueba',
        ]);

        // La página de eliminar avisa y ofrece inactivar en vez de eliminar.
        $this->actingAs($admin)->get(route('usuarios.eliminar', $usuario))
            ->assertOk()
            ->assertSee('no se puede eliminar')
            ->assertSee('Inactivar')
            ->assertDontSee('¿Seguro que deseas eliminar este usuario?');

        $this->actingAs($admin)->delete(route('usuarios.destroy', $usuario))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('usuarios', ['id' => $usuario->id]);
    }

    public function test_agregar_valor_de_la_hora_conserva_el_historial(): void
    {
        $admin = $this->administrador();
        $usuario = $this->crearUsuario();
        $usuario->valoresHora()->create(['valor' => 9000, 'vigente_desde' => '2026-01-01']);

        $this->actingAs($admin)->post(route('valores-hora.store', $usuario), [
            'valor' => 9500,
            'vigente_desde' => '2027-01-01',
        ])->assertSessionHasNoErrors();

        $this->assertCount(2, $usuario->valoresHora()->get());
        $this->assertDatabaseHas('valores_hora', ['usuario_id' => $usuario->id, 'valor' => 9000]);
    }

    public function test_el_nuevo_valor_debe_ser_posterior_al_ultimo(): void
    {
        $admin = $this->administrador();
        $usuario = $this->crearUsuario();
        $usuario->valoresHora()->create(['valor' => 9000, 'vigente_desde' => '2026-06-01']);

        $this->actingAs($admin)->post(route('valores-hora.store', $usuario), [
            'valor' => 9500,
            'vigente_desde' => '2026-06-01',
        ])->assertSessionHasErrors('vigente_desde');
    }

    public function test_el_nuevo_valor_respeta_el_minimo(): void
    {
        config(['nomina.valor_hora_minimo' => 8400]);
        $admin = $this->administrador();
        $usuario = $this->crearUsuario();

        $this->actingAs($admin)->post(route('valores-hora.store', $usuario), [
            'valor' => 8000,
            'vigente_desde' => '2026-12-01',
        ])->assertSessionHasErrors('valor');
    }

    public function test_al_pasar_de_administrador_a_dependiente_se_pide_el_valor_de_la_hora(): void
    {
        $admin = $this->administrador();
        $otroAdmin = $this->administrador();

        $datos = [
            'nombre' => $otroAdmin->nombre,
            'cedula' => $otroAdmin->cedula,
            'correo' => $otroAdmin->correo,
            'telefono' => $otroAdmin->telefono,
            'rol' => 'dependiente',
        ];

        $this->actingAs($admin)->put(route('usuarios.update', $otroAdmin), $datos)
            ->assertSessionHasErrors(['valor_hora', 'vigente_desde']);
        $this->assertSame('administrador', $otroAdmin->fresh()->rol);

        $this->actingAs($admin)->put(route('usuarios.update', $otroAdmin), [
            ...$datos,
            'valor_hora' => 10000,
            'vigente_desde' => '2026-11-01',
        ])->assertSessionHasNoErrors();

        $otroAdmin->refresh();
        $this->assertSame('dependiente', $otroAdmin->rol);
        $this->assertCount(1, $otroAdmin->valoresHora);
        $this->assertEquals(10000, $otroAdmin->valoresHora->first()->valor);
    }
}
