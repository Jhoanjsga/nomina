<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarUsuarioRequest;
use App\Http\Requests\CrearUsuarioRequest;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        // Filtro: activos (por defecto), inactivos o todos.
        $estado = $request->query('estado', 'activos');

        $consulta = Usuario::with('valoresHora')->orderBy('nombre');

        if ($estado === 'activos') {
            $consulta->where('activo', true);
        } elseif ($estado === 'inactivos') {
            $consulta->where('activo', false);
        }

        $usuarios = $consulta->get();

        return view('usuarios.index', compact('usuarios', 'estado'));
    }

    public function create()
    {
        return view('usuarios.create');
    }

    public function store(CrearUsuarioRequest $request)
    {
        $datos = $request->validated();

        // Se guarda el usuario y su primer valor de la hora juntos:
        // si algo falla, no queda ninguno de los dos a medias.
        DB::transaction(function () use ($datos) {
            $usuario = Usuario::create([
                'nombre' => $datos['nombre'],
                'cedula' => $datos['cedula'],
                'correo' => $datos['correo'],
                'telefono' => $datos['telefono'],
                'direccion' => $datos['direccion'] ?? null,
                'fecha_nacimiento' => $datos['fecha_nacimiento'] ?? null,
                'contrasena' => $datos['contrasena'],
                'rol' => $datos['rol'],
                'activo' => true,
            ]);

            if ($usuario->esDependiente()) {
                $usuario->valoresHora()->create([
                    'valor' => $datos['valor_hora'],
                    'vigente_desde' => $datos['vigente_desde'],
                ]);
            }
        });

        return redirect()->route('usuarios.index')->with('exito', 'Usuario creado correctamente.');
    }

    public function edit(Usuario $usuario)
    {
        $valoresHora = $usuario->valoresHora()->orderByDesc('vigente_desde')->get();

        return view('usuarios.edit', compact('usuario', 'valoresHora'));
    }

    public function update(ActualizarUsuarioRequest $request, Usuario $usuario)
    {
        $datos = $request->validated();

        // El administrador no puede quitarse su propio rol: se quedaría sin acceso.
        if ($usuario->is($request->user()) && $datos['rol'] !== 'administrador') {
            return back()->withInput()->with('error', 'No puedes quitarte tu propio rol de administrador.');
        }

        // Si la contraseña viene vacía, se conserva la actual.
        if (empty($datos['contrasena'])) {
            unset($datos['contrasena']);
        }

        $usuario->update($datos);

        return redirect()->route('usuarios.index')->with('exito', 'Usuario actualizado correctamente.');
    }

    public function cambiarEstado(Request $request, Usuario $usuario)
    {
        if ($usuario->is($request->user())) {
            return back()->with('error', 'No puedes inactivar tu propio usuario.');
        }

        $usuario->update(['activo' => ! $usuario->activo]);

        $mensaje = $usuario->activo ? 'Usuario activado.' : 'Usuario inactivado.';

        return back()->with('exito', $mensaje);
    }

    public function destroy(Request $request, Usuario $usuario)
    {
        if ($usuario->is($request->user())) {
            return back()->with('error', 'No puedes eliminar tu propio usuario.');
        }

        // Regla de negocio: con registros o liquidaciones no se elimina, se inactiva.
        if ($usuario->registros()->exists() || $usuario->liquidaciones()->exists()) {
            return back()->with('error', 'El usuario tiene registros o liquidaciones. No se puede eliminar; inactívalo.');
        }

        // Su historial de valores de la hora se borra con él (no tiene registros que dependan de esos valores).
        DB::transaction(function () use ($usuario) {
            $usuario->valoresHora()->delete();
            $usuario->delete();
        });

        return redirect()->route('usuarios.index')->with('exito', 'Usuario eliminado.');
    }
}
