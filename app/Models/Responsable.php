<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Responsable extends Model
{
    protected $table = 'responsables';

    protected $fillable = ['nombre', 'tipo', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function registros()
    {
        return $this->hasMany(RegistroHora::class);
    }

    // Savio es el responsable fijo de la firma: siempre debe estar disponible.
    public function esSavio(): bool
    {
        return $this->tipo === 'savio';
    }

    // Uso: Responsable::activos()->get()
    public function scopeActivos($consulta)
    {
        return $consulta->where('activo', true);
    }
}
