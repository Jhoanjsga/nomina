<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CrearUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El permiso lo controla el middleware rol:administrador en las rutas.
        return true;
    }

    public function rules(): array
    {
        $minimo = config('nomina.valor_hora_minimo');

        return [
            'nombre' => ['required', 'string', 'max:100'],
            'cedula' => ['required', 'regex:/^[0-9]+$/', 'max:45', 'unique:usuarios,cedula'],
            'correo' => ['required', 'email', 'max:100', 'unique:usuarios,correo'],
            'telefono' => ['required', 'string', 'max:45'],
            'direccion' => ['nullable', 'string', 'max:100'],
            'fecha_nacimiento' => ['nullable', 'date', 'before:today'],
            'rol' => ['required', 'in:administrador,dependiente'],
            'contrasena' => ['required', 'string', 'min:8', 'confirmed'],
            // El valor de la hora solo se pide para dependientes.
            'valor_hora' => ['required_if:rol,dependiente', 'nullable', 'numeric', 'min:'.$minimo, 'max:9999999999'],
            'vigente_desde' => ['required_if:rol,dependiente', 'nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'cedula.regex' => 'La cédula solo puede tener números.',
            'valor_hora.required_if' => 'El valor de la hora es obligatorio para un dependiente.',
            'valor_hora.min' => 'El valor de la hora no puede ser menor al mínimo pactado ($'.number_format(config('nomina.valor_hora_minimo'), 0, ',', '.').').',
            'vigente_desde.required_if' => 'La fecha desde la que rige el valor es obligatoria para un dependiente.',
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
