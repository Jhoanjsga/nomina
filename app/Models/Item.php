<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $table = 'items';

    protected $fillable = ['nombre', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function registros()
    {
        return $this->hasMany(RegistroHora::class);
    }

    // Uso: Item::activos()->get()
    public function scopeActivos($consulta)
    {
        return $consulta->where('activo', true);
    }
}
