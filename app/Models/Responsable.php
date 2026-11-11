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
}