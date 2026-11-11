<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Liquidacion extends Model
{
    protected $table = 'liquidaciones';

    protected $fillable = [
        'usuario_id', 'fecha_finalizacion', 'total_horas', 'total_pagado',
    ];

    protected $casts = [
        'fecha_finalizacion' => 'date',
        'total_horas' => 'decimal:2',
        'total_pagado' => 'decimal:2',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }

    public function registros()
    {
        return $this->hasMany(RegistroHora::class);
    }
}