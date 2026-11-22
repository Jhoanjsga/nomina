<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\RegistroHora;
use App\Models\Responsable;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistroHorasDependienteTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $dependiente;
    private Responsable $savio;
    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        // "Hoy" fijo para que las pruebas no dependan del día en que se corran:
        // miércoles 11 de noviembre de 2026, 10:00 a. m.
        $this->travelTo(Carbon::parse('2026-11-11 10:00'));

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

    private function datosValidos(array $cambios = []): array
    {
        return array_merge([
            'fecha' => today()->format('Y-m-d'),
            'hora_inicio' => '08:00',
            'hora_fin' => '10:30',
            'responsable_id' => $this->savio->id,
            'item_id' => $this->item->id,
            'radicado' => '76001-31-03-001-2026-00123-00',
            'cliente' => 'Cliente de prueba',
            'observacion' => '',
        ], $cambios);
    }

    public function test_el_dependiente_ve_el_formulario_solo_con_opciones_activas(): void
    {
        Item::create(['nombre' => 'Item viejo', 'activo' => false]);
        Responsable::create(['nombre' => 'Dr. Retirado', 'tipo' => 'abogado', 'activo' => false]);

        $this->actingAs($this->dependiente)->get(route('mis-registros.index'))
            ->assertOk()
            ->assertSee('Audiencia')
            // Hoy es miércoles 11: se ofrecen martes 10, miércoles 11 (hoy) y jueves 12.
            ->assertSee('Martes 10 de noviembre')
            ->assertSee('Miércoles 11 de noviembre (hoy)')
            ->assertSee('Jueves 12 de noviembre')
            ->assertDontSee('Item viejo')
            ->assertDontSee('Dr. Retirado');
    }

    public function test_el_administrador_no_usa_el_formulario_del_dependiente(): void
    {
        $admin = $this->crearUsuario('administrador', '1');

        $this->actingAs($admin)->get(route('mis-registros.index'))->assertForbidden();
        $this->actingAs($admin)->post(route('mis-registros.store'), $this->datosValidos())->assertForbidden();
    }

    public function test_registrar_horas_crea_un_registro_pendiente_del_usuario_en_sesion(): void
    {
        $otro = $this->crearUsuario('dependiente', '3');

        // Aunque se envíe otro usuario_id o una liquidación, se ignoran.
        $this->actingAs($this->dependiente)
            ->post(route('mis-registros.store'), $this->datosValidos([
                'usuario_id' => $otro->id,
                'liquidacion_id' => 99,
            ]))
            ->assertRedirect(route('mis-registros.index'))
            ->assertSessionHasNoErrors();

        $registro = RegistroHora::first();
        $this->assertSame($this->dependiente->id, $registro->usuario_id);
        $this->assertNull($registro->liquidacion_id);
        $this->assertNull($registro->observacion);
    }

    public function test_todos_los_campos_son_obligatorios_menos_la_observacion(): void
    {
        $this->actingAs($this->dependiente)
            ->post(route('mis-registros.store'), [])
            ->assertSessionHasErrors(['fecha', 'hora_inicio', 'hora_fin', 'responsable_id', 'item_id', 'radicado', 'cliente'])
            ->assertSessionDoesntHaveErrors('observacion');

        $this->assertSame(0, RegistroHora::count());
    }

    public function test_la_hora_de_fin_debe_ser_mayor_que_la_de_inicio(): void
    {
        $this->actingAs($this->dependiente)
            ->post(route('mis-registros.store'), $this->datosValidos(['hora_inicio' => '10:00', 'hora_fin' => '10:00']))
            ->assertSessionHasErrors('hora_fin');

        $this->actingAs($this->dependiente)
            ->post(route('mis-registros.store'), $this->datosValidos(['hora_inicio' => '11:00', 'hora_fin' => '09:00']))
            ->assertSessionHasErrors('hora_fin');

        $this->assertSame(0, RegistroHora::count());
    }

    // ---- Actividad 13: horario, ventana de fechas y cruces ----

    private function registrar(array $cambios)
    {
        return $this->actingAs($this->dependiente)
            ->post(route('mis-registros.store'), $this->datosValidos($cambios));
    }

    public function test_horario_permitido_es_de_8_a_18_incluidos_los_bordes(): void
    {
        $this->registrar(['hora_inicio' => '08:00', 'hora_fin' => '18:00'])->assertSessionHasNoErrors();
    }

    public function test_no_se_puede_empezar_antes_de_las_8(): void
    {
        $this->registrar(['hora_inicio' => '07:59', 'hora_fin' => '09:00'])->assertSessionHasErrors('hora_inicio');
    }

    public function test_no_se_puede_terminar_despues_de_las_18(): void
    {
        $this->registrar(['hora_inicio' => '17:00', 'hora_fin' => '18:01'])->assertSessionHasErrors('hora_fin');
    }

    public function test_ventana_dia_normal_acepta_ayer_hoy_y_manana(): void
    {
        // Hoy: miércoles 11.
        $this->registrar(['fecha' => '2026-11-10', 'hora_inicio' => '08:00', 'hora_fin' => '09:00'])->assertSessionHasNoErrors();
        $this->registrar(['fecha' => '2026-11-11', 'hora_inicio' => '08:00', 'hora_fin' => '09:00'])->assertSessionHasNoErrors();
        $this->registrar(['fecha' => '2026-11-12', 'hora_inicio' => '08:00', 'hora_fin' => '09:00'])->assertSessionHasNoErrors();

        $this->assertSame(3, RegistroHora::count());
    }

    public function test_ventana_rechaza_fechas_fuera_de_rango(): void
    {
        // Hoy: miércoles 11. Lunes 9 (antes de ayer) y viernes 13 (pasado mañana) no se permiten.
        $this->registrar(['fecha' => '2026-11-09'])->assertSessionHasErrors('fecha');
        $this->registrar(['fecha' => '2026-11-13'])->assertSessionHasErrors('fecha');

        $this->assertSame(0, RegistroHora::count());
    }

    public function test_un_lunes_puede_registrar_el_viernes_anterior(): void
    {
        $this->travelTo(Carbon::parse('2026-11-09 09:00')); // lunes

        $this->registrar(['fecha' => '2026-11-06'])->assertSessionHasNoErrors(); // viernes
    }

    public function test_un_viernes_antes_de_puente_el_siguiente_habil_es_el_martes(): void
    {
        $this->travelTo(Carbon::parse('2026-11-13 09:00')); // viernes; el lunes 16 es festivo

        $this->registrar(['fecha' => '2026-11-16'])->assertSessionHasErrors('fecha'); // festivo
        $this->registrar(['fecha' => '2026-11-17'])->assertSessionHasNoErrors();     // martes
    }

    public function test_no_se_registra_en_fin_de_semana_ni_festivo(): void
    {
        $this->travelTo(Carbon::parse('2026-11-14 09:00')); // sábado

        $this->registrar(['fecha' => '2026-11-14'])->assertSessionHasErrors('fecha'); // hoy, sábado
        $this->registrar(['fecha' => '2026-11-13'])->assertSessionHasNoErrors();     // viernes anterior
    }

    public function test_no_se_permiten_cruces(): void
    {
        $this->registrar(['hora_inicio' => '09:00', 'hora_fin' => '11:00'])->assertSessionHasNoErrors();

        // Empieza dentro, termina dentro, envuelve por completo, y exactamente igual.
        $this->registrar(['hora_inicio' => '10:00', 'hora_fin' => '12:00'])->assertSessionHasErrors('hora_inicio');
        $this->registrar(['hora_inicio' => '08:00', 'hora_fin' => '09:30'])->assertSessionHasErrors('hora_inicio');
        $this->registrar(['hora_inicio' => '08:00', 'hora_fin' => '12:00'])->assertSessionHasErrors('hora_inicio');
        $this->registrar(['hora_inicio' => '09:00', 'hora_fin' => '11:00'])->assertSessionHasErrors('hora_inicio');

        $this->assertSame(1, RegistroHora::count());
    }

    public function test_registros_pegados_si_se_permiten(): void
    {
        $this->registrar(['hora_inicio' => '09:00', 'hora_fin' => '11:00'])->assertSessionHasNoErrors();
        $this->registrar(['hora_inicio' => '08:00', 'hora_fin' => '09:00'])->assertSessionHasNoErrors();
        $this->registrar(['hora_inicio' => '11:00', 'hora_fin' => '12:00'])->assertSessionHasNoErrors();

        $this->assertSame(3, RegistroHora::count());
    }

    public function test_el_cruce_solo_cuenta_registros_del_mismo_dia_y_usuario(): void
    {
        $otro = $this->crearUsuario('dependiente', '3');
        RegistroHora::create([
            'usuario_id' => $otro->id, 'responsable_id' => $this->savio->id, 'item_id' => $this->item->id,
            'fecha' => '2026-11-11', 'hora_inicio' => '09:00', 'hora_fin' => '11:00',
            'radicado' => 'R-1', 'cliente' => 'Otro cliente',
        ]);

        // Mismo horario, pero de otro dependiente: permitido.
        $this->registrar(['hora_inicio' => '09:00', 'hora_fin' => '11:00'])->assertSessionHasNoErrors();
        // Mismo horario, mismo dependiente, otro día: permitido.
        $this->registrar(['fecha' => '2026-11-12', 'hora_inicio' => '09:00', 'hora_fin' => '11:00'])->assertSessionHasNoErrors();
    }

    public function test_no_acepta_responsable_o_item_inactivos(): void
    {
        $abogadoInactivo = Responsable::create(['nombre' => 'Dr. Retirado', 'tipo' => 'abogado', 'activo' => false]);
        $itemInactivo = Item::create(['nombre' => 'Item viejo', 'activo' => false]);

        $this->actingAs($this->dependiente)
            ->post(route('mis-registros.store'), $this->datosValidos([
                'responsable_id' => $abogadoInactivo->id,
                'item_id' => $itemInactivo->id,
            ]))
            ->assertSessionHasErrors(['responsable_id', 'item_id']);
    }

    public function test_no_registra_antes_de_tener_valor_de_la_hora(): void
    {
        // Dependiente nuevo: su valor rige desde hoy (miércoles 11), así que ayer no tiene valor.
        $nuevo = $this->crearUsuario('dependiente', '3');
        $nuevo->valoresHora()->create(['valor' => 10000, 'vigente_desde' => '2026-11-11']);

        $this->actingAs($nuevo)
            ->post(route('mis-registros.store'), $this->datosValidos(['fecha' => '2026-11-10']))
            ->assertSessionHasErrors(['fecha' => 'No se pueden registrar horas antes del 11/11/2026, fecha desde la que hay valor de la hora asignado.']);

        $this->actingAs($nuevo)
            ->post(route('mis-registros.store'), $this->datosValidos(['fecha' => '2026-11-11']))
            ->assertSessionHasNoErrors();
    }

    public function test_no_registra_si_no_tiene_ningun_valor_de_la_hora(): void
    {
        $sinValor = $this->crearUsuario('dependiente', '4');

        $this->actingAs($sinValor)
            ->post(route('mis-registros.store'), $this->datosValidos())
            ->assertSessionHasErrors('fecha');

        $this->assertSame(0, RegistroHora::count());
    }
}
