<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El permiso lo controla el middleware rol:administrador en las rutas.
        return true;
    }

    public function rules(): array
    {
        $usuario = $this->route('usuario');

        // Si queda como dependiente y no tiene ningún valor de la hora (por ejemplo, era administrador),
        // se le pide el primer valor igual que al crearlo.
        $pedirValor = $this->input('rol') === 'dependiente' && ! $usuario->valoresHora()->exists();

        return [
            'nombre' => ['required', 'string', 'max:100'],
            // La cédula y el correo son únicos, sin contar al mismo usuario que se edita.
            'cedula' => ['required', 'regex:/^[0-9]+$/', 'max:45', Rule::unique('usuarios', 'cedula')->ignore($usuario->id)],
            'correo' => ['required', 'email', 'max:100', Rule::unique('usuarios', 'correo')->ignore($usuario->id)],
            'telefono' => ['required', 'string', 'max:45'],
            'direccion' => ['nullable', 'string', 'max:100'],
            'fecha_nacimiento' => ['nullable', 'date', 'before:today'],
            'rol' => ['required', 'in:administrador,dependiente'],
            // Al editar, la contraseña es opcional: si se deja vacía, se conserva la actual.
            'contrasena' => ['nullable', 'string', 'min:8', 'confirmed'],
            'valor_hora' => [Rule::requiredIf($pedirValor), 'nullable', 'numeric', 'min:'.config('nomina.valor_hora_minimo'), 'max:9999999999'],
            'vigente_desde' => [Rule::requiredIf($pedirValor), 'nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'cedula.regex' => 'La cédula solo puede tener números.',
            'valor_hora.required' => 'El valor de la hora es obligatorio para un dependiente.',
            'valor_hora.min' => 'El valor de la hora no puede ser menor al mínimo pactado ($'.number_format(config('nomina.valor_hora_minimo'), 0, ',', '.').').',
            'vigente_desde.required' => 'La fecha desde la que rige el valor es obligatoria para un dependiente.',
        ];
    }

    public function attributes(): array
    {
        return [
            'cedula' => 'cédula',
            'telefono' => 'teléfono',
            'direccion' => 'dirección',
            'fecha_nacimiento' => 'fecha de nacimiento',
            'contrasena' => 'contraseña',
            'valor_hora' => 'valor de la hora',
            'vigente_desde' => 'vigente desde',
        ];
    }
}
