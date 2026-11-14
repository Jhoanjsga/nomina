<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarResponsableRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El permiso lo controla el middleware rol:administrador en las rutas.
        return true;
    }

    public function rules(): array
    {
        // Al editar, la ruta trae el responsable; al crear, no.
        $responsable = $this->route('responsable');

        return [
            // Solo se pide el nombre: el tipo lo decide el sistema (los nuevos siempre son abogados).
            'nombre' => [
                'required', 'string', 'max:45',
                Rule::unique('responsables', 'nombre')->ignore($responsable?->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.unique' => 'Ya existe un responsable con ese nombre.',
        ];
    }
}
