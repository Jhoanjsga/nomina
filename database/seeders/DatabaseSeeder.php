<?php

namespace Database\Seeders;

use App\Models\Responsable;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Usuario::create([
            'nombre' => 'Administrador',
            'cedula' => '1000000000',
            'correo' => 'admin@savio.test',
            'telefono' => '3000000000',
            'contrasena' => 'Cambiar123*',
            'rol' => 'administrador',
            'activo' => true,
        ]);

        Responsable::create([
            'nombre' => 'Savio',
            'tipo' => 'savio',
            'activo' => true,
        ]);
                Usuario::create([
            'nombre' => 'Dependiente de prueba',
            'cedula' => '2000000000',
            'correo' => 'dependiente@savio.test',
            'telefono' => '3000000001',
            'contrasena' => 'Cambiar123*',
            'rol' => 'dependiente',
            'activo' => true,
        ]);
    }
}