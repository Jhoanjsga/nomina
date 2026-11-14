<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Responsable;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResponsablesItemsTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol): Usuario
    {
        return Usuario::create([
            'nombre' => 'Usuario '.$rol,
            'cedula' => $rol === 'administrador' ? '1' : '2',
            'correo' => $rol.'@savio.test',
            'telefono' => '3000000000',
            'contrasena' => 'Clave1234',
            'rol' => $rol,
            'activo' => true,
        ]);
    }

    private function savio(): Responsable
    {
        return Responsable::create(['nombre' => 'Savio', 'tipo' => 'savio', 'activo' => true]);
    }

    public function test_un_dependiente_no_puede_administrar_responsables_ni_items(): void
    {
        $dependiente = $this->usuario('dependiente');

        $this->actingAs($dependiente)->get(route('responsables.index'))->assertForbidden();
        $this->actingAs($dependiente)->get(route('items.index'))->assertForbidden();
        $this->actingAs($dependiente)->post(route('items.store'), ['nombre' => 'Audiencia'])->assertForbidden();
    }

    public function test_crear_responsable_siempre_crea_un_abogado(): void
    {
        $admin = $this->usuario('administrador');

        // Aunque alguien envíe tipo=savio a mano, se ignora.
        $this->actingAs($admin)->post(route('responsables.store'), ['nombre' => 'Dr. Gómez', 'tipo' => 'savio'])
            ->assertRedirect(route('responsables.index'));

        $this->assertDatabaseHas('responsables', ['nombre' => 'Dr. Gómez', 'tipo' => 'abogado', 'activo' => true]);
    }

    public function test_savio_no_se_puede_inactivar(): void
    {
        $admin = $this->usuario('administrador');
        $savio = $this->savio();

        $this->actingAs($admin)->patch(route('responsables.estado', $savio))->assertSessionHas('error');

        $this->assertTrue($savio->fresh()->activo);
    }

    public function test_editar_no_cambia_el_tipo(): void
    {
        $admin = $this->usuario('administrador');
        $savio = $this->savio();

        $this->actingAs($admin)->put(route('responsables.update', $savio), ['nombre' => 'Savio Abogados', 'tipo' => 'abogado']);

        $savio->refresh();
        $this->assertSame('Savio Abogados', $savio->nombre);
        $this->assertTrue($savio->esSavio());
    }

    public function test_un_abogado_se_inactiva_y_se_activa(): void
    {
        $admin = $this->usuario('administrador');
        $abogado = Responsable::create(['nombre' => 'Dr. Ruiz', 'tipo' => 'abogado', 'activo' => true]);

        $this->actingAs($admin)->patch(route('responsables.estado', $abogado));
        $this->assertFalse($abogado->fresh()->activo);

        $this->actingAs($admin)->patch(route('responsables.estado', $abogado));
        $this->assertTrue($abogado->fresh()->activo);
    }

    public function test_no_se_repite_el_nombre_de_un_responsable(): void
    {
        $admin = $this->usuario('administrador');
        $this->savio();

        $this->actingAs($admin)->post(route('responsables.store'), ['nombre' => 'Savio'])
            ->assertSessionHasErrors('nombre');
    }

    public function test_crear_item_y_no_repetir_nombre(): void
    {
        $admin = $this->usuario('administrador');

        $this->actingAs($admin)->post(route('items.store'), ['nombre' => 'Audiencia'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('items.store'), ['nombre' => 'Audiencia'])->assertSessionHasErrors('nombre');

        $this->assertSame(1, Item::where('nombre', 'Audiencia')->count());
    }

    public function test_editar_item_conservando_su_mismo_nombre(): void
    {
        $admin = $this->usuario('administrador');
        $item = Item::create(['nombre' => 'Audiencia', 'activo' => true]);

        $this->actingAs($admin)->put(route('items.update', $item), ['nombre' => 'Audiencia'])
            ->assertSessionHasNoErrors();
    }

    public function test_un_item_se_inactiva_y_se_activa(): void
    {
        $admin = $this->usuario('administrador');
        $item = Item::create(['nombre' => 'Memorial', 'activo' => true]);

        $this->actingAs($admin)->patch(route('items.estado', $item));
        $this->assertFalse($item->fresh()->activo);

        $this->actingAs($admin)->patch(route('items.estado', $item));
        $this->assertTrue($item->fresh()->activo);
    }

    public function test_las_pantallas_de_edicion_cargan(): void
    {
        $admin = $this->usuario('administrador');
        $savio = $this->savio();
        $item = Item::create(['nombre' => 'Audiencia', 'activo' => true]);

        $this->actingAs($admin)->get(route('responsables.edit', $savio))->assertOk()->assertSee('El tipo no se puede cambiar');
        $this->actingAs($admin)->get(route('items.edit', $item))->assertOk()->assertSee('Audiencia');
    }

    public function test_las_listas_filtran_por_estado(): void
    {
        $admin = $this->usuario('administrador');
        Item::create(['nombre' => 'Item activo', 'activo' => true]);
        Item::create(['nombre' => 'Item inactivo', 'activo' => false]);

        $this->actingAs($admin)->get(route('items.index'))
            ->assertOk()->assertSee('Item activo')->assertDontSee('Item inactivo');
        $this->actingAs($admin)->get(route('items.index', ['estado' => 'inactivos']))
            ->assertOk()->assertSee('Item inactivo')->assertDontSee('Item activo');
        $this->actingAs($admin)->get(route('responsables.index'))->assertOk();
    }
}
