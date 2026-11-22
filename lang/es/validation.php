<?php

// Mensajes de validación en español. Solo están las reglas que usa el proyecto;
// si alguna regla nueva no aparece aquí, Laravel muestra el mensaje en inglés.

return [

    'after' => 'El campo :attribute debe ser una fecha posterior a :date.',
    'after_or_equal' => 'El campo :attribute debe ser una fecha igual o posterior a :date.',
    'before' => 'El campo :attribute debe ser una fecha anterior a :date.',
    'before_or_equal' => 'El campo :attribute debe ser una fecha igual o anterior a :date.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'date' => 'El campo :attribute no es una fecha válida.',
    'date_format' => 'El campo :attribute no tiene un formato válido.',
    'email' => 'El campo :attribute debe ser un correo electrónico válido.',
    'exists' => 'El :attribute seleccionado no es válido.',
    'in' => 'El :attribute seleccionado no es válido.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'max' => [
        'numeric' => 'El campo :attribute no puede ser mayor que :max.',
        'string' => 'El campo :attribute no puede tener más de :max caracteres.',
    ],
    'min' => [
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'numeric' => 'El campo :attribute debe ser un número.',
    'regex' => 'El formato del campo :attribute no es válido.',
    'required' => 'El campo :attribute es obligatorio.',
    'required_if' => 'El campo :attribute es obligatorio cuando :other es :value.',
    'string' => 'El campo :attribute debe ser texto.',
    'unique' => 'Ya existe un registro con ese :attribute.',

    // Mensajes para casos puntuales en los que el mensaje general se lee raro.
    'custom' => [
        'fecha_nacimiento' => [
            'before' => 'La fecha de nacimiento debe ser anterior a hoy.',
        ],
    ],

    // Nombres de los campos tal como los ve el usuario.
    'attributes' => [
        'nombre' => 'nombre',
        'cedula' => 'cédula',
        'correo' => 'correo',
        'telefono' => 'teléfono',
        'direccion' => 'dirección',
        'fecha_nacimiento' => 'fecha de nacimiento',
        'rol' => 'rol',
        'contrasena' => 'contraseña',
        'valor' => 'valor de la hora',
        'valor_hora' => 'valor de la hora',
        'vigente_desde' => 'vigente desde',
        'fecha' => 'fecha',
        'hora_inicio' => 'hora de inicio',
        'hora_fin' => 'hora de fin',
        'responsable_id' => 'responsable',
        'item_id' => 'ítem',
        'radicado' => 'radicado',
        'cliente' => 'cliente',
        'observacion' => 'observación',
        'desde' => 'desde',
        'hasta' => 'hasta',
        'estado' => 'estado',
    ],

];
