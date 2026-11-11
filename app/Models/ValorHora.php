<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ValorHora extends Model
{
    protected $table = 'valores_hora';

    protected $fillable = ['usuario_id', 'valor', 'vigente_desde'];

    protected $casts = [
        'valor' => 'decimal:2',
        'vigente_desde' => 'date',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }
}