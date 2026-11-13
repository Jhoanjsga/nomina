<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Usuario extends Authenticatable
{
    protected $table = 'usuarios';

    protected $fillable = [
        'nombre', 'cedula', 'correo', 'telefono', 'direccion',
        'fecha_nacimiento', 'contrasena', 'rol', 'activo',
    ];

    protected $hidden = ['contrasena'];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'contrasena' => 'hashed',
        'activo' => 'boolean',
    ];

    public function getAuthPasswordName()
    {
        return 'contrasena';
    }

    public function getAuthPassword()
    {
        return $this->contrasena;
    }

    public function valoresHora()
    {
        return $this->hasMany(ValorHora::class);
    }

    public function registros()
    {
        return $this->hasMany(RegistroHora::class);
    }

    public function liquidaciones()
    {
        return $this->hasMany(Liquidacion::class);
    }
        public function esAdministrador(): bool
    {
        return $this->rol === 'administrador';
    }

    public function esDependiente(): bool
    {
        return $this->rol === 'dependiente';
    }
}