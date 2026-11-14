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
        ];
    }

    public function messages(): array
    {
        return [
            'cedula.regex' => 'La cédula solo puede tener números.',
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
        ];
    }
}
