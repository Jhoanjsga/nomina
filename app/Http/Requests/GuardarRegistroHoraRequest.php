<?php

namespace App\Http\Requests;

use App\Models\RegistroHora;
use App\Models\Usuario;
use App\Services\CalendarioLaboral;
use Carbon\Carbon;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Validator;

class GuardarRegistroHoraRequest extends FormRequest
{
    // Horario de trabajo de la firma.
    private const HORA_APERTURA = '08:00';
    private const HORA_CIERRE = '18:00';

    public function authorize(): bool
    {
        // El permiso lo controla el middleware de rol en las rutas.
        return true;
    }

    public function rules(): array
    {
        return [
            // 'bail': si la fecha no tiene formato válido, no se evalúan las reglas siguientes.
            'fecha' => [
                'bail', 'required', 'date_format:Y-m-d',
                $this->reglaDiaHabil(), $this->reglaVentanaDependiente(), $this->reglaConValorHora(),
            ],
            // Horario 8:00 a 18:00: se puede empezar a las 8:00 y terminar a las 18:00.
            'hora_inicio' => ['required', 'date_format:H:i', 'after_or_equal:'.self::HORA_APERTURA],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio', 'before_or_equal:'.self::HORA_CIERRE],
            // Solo se aceptan responsables e ítems activos, aunque alguien manipule el formulario.
            // Al editar, se permite conservar el que ya tenía el registro aunque lo hayan inactivado.
            'responsable_id' => ['required', $this->reglaActivoOActual('responsables', $this->route('registro')?->responsable_id)],
            'item_id' => ['required', $this->reglaActivoOActual('items', $this->route('registro')?->item_id)],
            'radicado' => ['required', 'string', 'max:100'],
            'cliente' => ['required', 'string', 'max:100'],
            'observacion' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function reglaActivoOActual(string $tabla, ?int $idActual): Exists
    {
        return Rule::exists($tabla, 'id')->where(function ($consulta) use ($idActual) {
            $consulta->where('activo', true)->orWhere('id', $idActual);
        });
    }

    // La fecha debe ser de lunes a viernes y no festivo (para todos los roles).
    private function reglaDiaHabil(): Closure
    {
        return function (string $atributo, mixed $valor, Closure $fallar) {
            if (! app(CalendarioLaboral::class)->esDiaHabil(Carbon::parse($valor))) {
                $fallar('La fecha debe ser un día hábil (lunes a viernes, sin festivos).');
            }
        };
    }

    // El dependiente solo puede registrar el día hábil anterior, hoy o el siguiente día hábil.
    // El administrador puede registrar cualquier fecha hábil.
    private function reglaVentanaDependiente(): Closure
    {
        return function (string $atributo, mixed $valor, Closure $fallar) {
            if (! $this->user()->esDependiente()) {
                return;
            }

            $permitidas = app(CalendarioLaboral::class)->fechasPermitidasDependiente(today());

            if (! in_array(Carbon::parse($valor)->toDateString(), $permitidas, true)) {
                $legibles = array_map(fn ($fecha) => Carbon::parse($fecha)->format('d/m/Y'), $permitidas);
                $fallar('Solo puedes registrar horas en estas fechas: '.implode(', ', $legibles).'.');
            }
        };
    }

    // No se aceptan registros de fechas en las que el usuario todavía no tenía valor de la hora,
    // porque no habría con qué calcular el pago.
    private function reglaConValorHora(): Closure
    {
        return function (string $atributo, mixed $valor, Closure $fallar) {
            $usuario = $this->usuarioDelRegistro();
            if (! $usuario) {
                return;
            }

            $primerValor = $usuario->valoresHora()->min('vigente_desde');

            if (! $primerValor) {
                $fallar('El usuario no tiene valor de la hora asignado. El administrador debe asignarlo antes de registrar horas.');
            } elseif (Carbon::parse($valor)->lt(Carbon::parse($primerValor)->startOfDay())) {
                $fallar('No se pueden registrar horas antes del '.Carbon::parse($primerValor)->format('d/m/Y').', fecha desde la que hay valor de la hora asignado.');
            }
        };
    }

    // Revisión de cruces: se hace al final, solo si fecha y horas ya son válidas.
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->hasAny(['fecha', 'hora_inicio', 'hora_fin'])) {
                return;
            }

            $usuario = $this->usuarioDelRegistro();
            if (! $usuario) {
                return;
            }

            $cruce = $this->buscarCruce($usuario);
            if ($cruce) {
                $validator->errors()->add('hora_inicio', sprintf(
                    'Este horario se cruza con otro registro del mismo día (%s a %s).',
                    substr($cruce->hora_inicio, 0, 5),
                    substr($cruce->hora_fin, 0, 5),
                ));
            }
        });
    }

    // Dueño del registro: el dependiente es siempre él mismo.
    // El administrador crea para el usuario de la ruta, o edita un registro que ya tiene dueño.
    private function usuarioDelRegistro(): ?Usuario
    {
        if ($this->user()->esDependiente()) {
            return $this->user();
        }

        return $this->route('usuario') ?? $this->route('registro')?->usuario;
    }

    // Dos registros se cruzan si uno empieza antes de que el otro termine.
    // Registros pegados (uno termina 10:00 y el otro empieza 10:00) sí se permiten.
    private function buscarCruce(Usuario $usuario): ?RegistroHora
    {
        $inicio = Carbon::parse($this->input('hora_inicio'))->format('H:i:s');
        $fin = Carbon::parse($this->input('hora_fin'))->format('H:i:s');

        return RegistroHora::where('usuario_id', $usuario->id)
            ->whereDate('fecha', $this->input('fecha'))
            ->where('hora_inicio', '<', $fin)
            ->where('hora_fin', '>', $inicio)
            // Al editar (actividad 16), el registro no se compara consigo mismo.
            ->when($this->route('registro'), fn ($consulta, $registro) => $consulta->where('id', '!=', $registro->id))
            ->orderBy('hora_inicio')
            ->first();
    }

    public function messages(): array
    {
        return [
            'fecha.date_format' => 'La fecha no es válida.',
            'hora_inicio.after_or_equal' => 'La hora de inicio no puede ser antes de las 8:00.',
            'hora_fin.before_or_equal' => 'La hora de fin no puede ser después de las 18:00.',
            'hora_fin.after' => 'La hora de fin debe ser mayor que la hora de inicio.',
            'responsable_id.exists' => 'El responsable elegido no existe o está inactivo.',
            'item_id.exists' => 'El ítem elegido no existe o está inactivo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'hora_inicio' => 'hora de inicio',
            'hora_fin' => 'hora de fin',
            'responsable_id' => 'responsable',
            'item_id' => 'ítem',
            'observacion' => 'observación',
        ];
    }
}
