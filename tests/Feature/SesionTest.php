<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SesionTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuario(array $datos = []): Usuario
    {
        return Usuario::create(array_merge([
            'nombre' => 'Dependiente',
            'cedula' => '123456',
            'correo' => 'dep@savio.test',
            'telefono' => '3000000000',
            'contrasena' => 'Clave1234',
            'rol' => 'dependiente',
            'activo' => true,
        ], $datos));
    }

    public function test_un_usuario_inactivo_no_puede_iniciar_sesion(): void
    {
        $this->crearUsuario(['activo' => false]);

        $this->post('/login', ['correo' => 'dep@savio.test', 'contrasena' => 'Clave1234'])
            ->assertSessionHasErrors('correo');

        $this->assertGuest();
    }

    public function test_un_usuario_inactivado_con_sesion_abierta_es_expulsado(): void
    {
        $usuario = $this->crearUsuario();

        $this->actingAs($usuario)->get(route('inicio'))->assertOk();

        // El administrador lo inactiva mientras está conectado.
        $usuario->update(['activo' => false]);

        $this->get(route('inicio'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('correo');

        $this->assertGuest();
    }
}
