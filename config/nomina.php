<?php

/*
|--------------------------------------------------------------------------
| Configuración propia de la plataforma de nómina
|--------------------------------------------------------------------------
|
| Valores de negocio que pueden cambiar sin tocar el código.
| Para cambiarlos, se agrega la variable en el archivo .env.
|
*/

return [

    // Valor mínimo pactado para la hora de trabajo (pesos colombianos).
    'valor_hora_minimo' => (float) env('VALOR_HORA_MINIMO', 8400),

];
