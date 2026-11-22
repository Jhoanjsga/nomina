<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Liquidacion;
use App\Models\RegistroHora;
use App\Models\Responsable;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InicioAdministradorTest extends TestCase
{
    use RefreshDatabase;

    private Responsable $savio;
    private Item $item;
    private int $consecutivo = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->savio = Responsable::create(['nombre' => 'Savio', 'tipo' => 'savio', 'activo' => true]);
        $this->item = Item::create(['nombre' => 'Audiencia', 'activo' => true]);
    }

    private function crearUsuario(string $nombre, string $rol = 'dependiente', bool $activo = true): Usuario
    {
        $this->consecutivo++;

        $usuario = Usuario::create([
            'nombre' => $nombre,
            'cedula' => '900'.$this->consecutivo,
            'correo' => 'usuario'.$this->consecutivo.'@savio.test',
            'telefono' => '3000000000',
            'contrasena' => 'Clave1234',
            'rol' => $rol,
            'activo' => $activo,
        ]);

        if ($rol === 'dependiente') {
            $usuario->valoresHora()->create(['valor' => 10000, 'vigente_desde' => '2026-01-01']);
        }

        return $usuario;
    }

    private function crearRegistro(Usuario $usuario, string $inicio, string $fin, ?int $liquidacionId = null): void
    {
        RegistroHora::create([
            'usuario_id' => $usuario->id,
            'responsable_id' => $this->savio->id,
            'item_id' => $this->item->id,
            'liquidacion_id' => $liquidacionId,
            'fecha' => '2026-11-10',
            'hora_inicio' => $inicio,
            'hora_fin' => $fin,
            'radicado' => 'RAD-'.$inicio,
            'cliente' => 'Cliente',
        ]);
    }

    public function test_muestra_todos_los_dependientes_en_orden_con_su_estado(): void
    {
        $admin = $this->crearUsuario('Administrador Uno', 'administrador');
        $this->crearUsuario('Carlos Ruiz');
        $this->crearUsuario('Ana Gómez', 'dependiente', false);
        $this->crearUsuario('Beatriz Mora');

        $this->actingAs($admin)->get(route('inicio'))
            ->assertOk()
            ->assertSeeInOrder(['Ana Gómez', 'Inactivo', 'Beatriz Mora', 'Activo', 'Carlos Ruiz', 'Activo'])
            ->assertDontSee('Administrador Uno</h2>', false);
    }

    public function test_los_totales_solo_cuentan_los_registros_pendientes(): void
    {
        $admin = $this->crearUsuario('Administrador Uno', 'administrador');
        $dependiente = $this->crearUsuario('Ana Gómez');

        $liquidacion = Liquidacion::create([
            'usuario_id' => $dependiente->id,
            'fecha_finalizacion' => '2026-10-31',
            'total_horas' => 3,
            'total_pagado' => 30000,
        ]);

        $this->crearRegistro($dependiente, '08:00', '10:00');
        $this->crearRegistro($dependiente, '10:00', '10:30');
        $this->crearRegistro($dependiente, '14:00', '17:00', $liquidacion->id);

        $respuesta = $this->actingAs($admin)->get(route('inicio'));

        $tarjeta = $respuesta->viewData('tarjetas')[0];
        $this->assertSame(2.5, $tarjeta['horas']);
        $this->assertSame(25000.0, $tarjeta['valor']);
        $respuesta->assertSee('$25.000');
    }

    public function test_un_dependiente_en_cero_tambien_tiene_tarjeta(): void
    {
        $admin = $this->crearUsuario('Administrador Uno', 'administrador');
        $this->crearUsuario('Ana Gómez');

        $this->actingAs($admin)->get(route('inicio'))
            ->assertSee('Ana Gómez')
            ->assertSee('$0');
    }

    public function test_la_tarjeta_lleva_a_los_registros_del_dependiente(): void
    {
        $admin = $this->crearUsuario('Administrador Uno', 'administrador');
        $dependiente = $this->crearUsuario('Ana Gómez');

        $this->actingAs($admin)->get(route('inicio'))
            ->assertSee(route('usuarios.show', $dependiente));
    }

    public function test_el_dependiente_no_ve_tarjetas(): void
    {
        $dependiente = $this->crearUsuario('Ana Gómez');
        $this->crearUsuario('Beatriz Mora');

        $this->actingAs($dependiente)->get(route('inicio'))
            ->assertOk()
            ->assertDontSee('Beatriz Mora');
    }
}
