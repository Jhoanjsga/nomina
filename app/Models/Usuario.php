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
    // Valor de la hora que rige hoy: el último con vigente_desde menor o igual a hoy.
    // Usa la relación ya cargada para no consultar la base por cada usuario de la lista.
    public function valorHoraActual(): ?ValorHora
    {
        $hoy = today()->toDateString();

        return $this->valoresHora
            ->filter(fn ($valor) => $valor->vigente_desde->toDateString() <= $hoy)
            ->sortByDesc('vigente_desde')
            ->first();
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