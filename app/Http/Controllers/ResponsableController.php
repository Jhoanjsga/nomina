<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarResponsableRequest;
use App\Models\Responsable;
use Illuminate\Http\Request;

class ResponsableController extends Controller
{
    // Lista con filtro y formulario para crear en la misma pantalla.
    public function index(Request $request)
    {
        $estado = $request->query('estado', 'activos');

        $consulta = Responsable::orderByRaw("tipo = 'savio' desc")->orderBy('nombre');

        if ($estado === 'activos') {
            $consulta->where('activo', true);
        } elseif ($estado === 'inactivos') {
            $consulta->where('activo', false);
        }

        $responsables = $consulta->get();

        return view('responsables.index', compact('responsables', 'estado'));
    }

    public function store(GuardarResponsableRequest $request)
    {
        // Savio ya existe y es único: todo responsable nuevo es un abogado.
        Responsable::create([
            'nombre' => $request->validated('nombre'),
            'tipo' => 'abogado',
            'activo' => true,
        ]);

        return redirect()->route('responsables.index')->with('exito', 'Responsable creado correctamente.');
    }

    public function edit(Responsable $responsable)
    {
        return view('responsables.edit', compact('responsable'));
    }

    public function update(GuardarResponsableRequest $request, Responsable $responsable)
    {
        // Solo se actualiza el nombre: el tipo nunca cambia.
        $responsable->update(['nombre' => $request->validated('nombre')]);

        return redirect()->route('responsables.index')->with('exito', 'Responsable actualizado correctamente.');
    }

    public function cambiarEstado(Responsable $responsable)
    {
        // Regla: Savio siempre está disponible. Se valida aquí, no solo ocultando el botón.
        if ($responsable->esSavio()) {
            return back()->with('error', 'Savio siempre debe estar disponible; no se puede inactivar.');
        }

        $responsable->update(['activo' => ! $responsable->activo]);

        $mensaje = $responsable->activo ? 'Responsable activado.' : 'Responsable inactivado.';

        return back()->with('exito', $mensaje);
    }
}
