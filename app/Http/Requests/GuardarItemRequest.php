<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El permiso lo controla el middleware rol:administrador en las rutas.
        return true;
    }

    public function rules(): array
    {
        // Al editar, la ruta trae el ítem; al crear, no.
        $item = $this->route('item');

        return [
            'nombre' => [
                'required', 'string', 'max:45',
                Rule::unique('items', 'nombre')->ignore($item?->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.unique' => 'Ya existe un ítem con ese nombre.',
        ];
    }
}
