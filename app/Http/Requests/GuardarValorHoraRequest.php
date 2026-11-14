<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GuardarValorHoraRequest extends FormRequest
{
    public function authorize(): bool
    {
        // El permiso lo controla el middleware rol:administrador en las rutas.
        return true;
    }

    public function rules(): array
    {
        $usuario = $this->route('usuario');
        $ultimo = $usuario->valoresHora()->max('vigente_desde');

        $reglasFecha = ['required', 'date'];

        // El nuevo valor debe empezar a regir después del último registrado.
        // Así no se cambia el valor de días que ya tenían uno asignado.
        if ($ultimo) {
            $reglasFecha[] = 'after:'.$ultimo;
        }

        return [
            'valor' => ['required', 'numeric', 'min:'.config('nomina.valor_hora_minimo'), 'max:9999999999'],
            'vigente_desde' => $reglasFecha,
        ];
    }

    public function messages(): array
    {
        return [
            'valor.min' => 'El valor de la hora no puede ser menor al mínimo pactado ($'.number_format(config('nomina.valor_hora_minimo'), 0, ',', '.').').',
            'vigente_desde.after' => 'La fecha debe ser posterior a la del último valor registrado.',
        ];
    }

    public function attributes(): array
    {
        return [
            'valor' => 'valor de la hora',
            'vigente_desde' => 'vigente desde',
        ];
    }
}
