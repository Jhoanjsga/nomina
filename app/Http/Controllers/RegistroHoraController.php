<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarRegistroHoraRequest;
use App\Models\Item;
use App\Models\RegistroHora;
use App\Models\Responsable;
use App\Models\Usuario;

// Registros de horas que maneja el administrador desde el detalle de usuario.
// Puede crear en cualquier día hábil, pero solo edita o elimina registros pendientes.
class RegistroHoraController extends Controller
{
    public function store(GuardarRegistroHoraRequest $request, Usuario $usuario)
    {
        abort_unless($usuario->esDependiente(), 404);

        RegistroHora::create([
            ...$request->validated(),
            'usuario_id' => $usuario->id,
        ]);

        return back()->with('exito', 'Registro agregado correctamente.');
    }

    public function edit(RegistroHora $registro)
    {
        if ($registro->liquidacion_id) {
            return $this->rechazarLiquidado($registro);
        }

        // Activos, más el responsable y el ítem que ya tiene el registro aunque estén inactivos.
        $responsables = Responsable::where('activo', true)
            ->orWhere('id', $registro->responsable_id)
            ->orderByRaw("tipo = 'savio' desc")
            ->orderBy('nombre')
            ->get();

        $items = Item::where('activo', true)
            ->orWhere('id', $registro->item_id)
            ->orderBy('nombre')
            ->get();

        return view('registros.edit', compact('registro', 'responsables', 'items'));
    }

    public function update(GuardarRegistroHoraRequest $request, RegistroHora $registro)
    {
        if ($registro->liquidacion_id) {
            return $this->rechazarLiquidado($registro);
        }

        $registro->update($request->validated());

        return redirect()->route('usuarios.show', $registro->usuario_id)->with('exito', 'Registro actualizado correctamente.');
    }

    public function destroy(RegistroHora $registro)
    {
        if ($registro->liquidacion_id) {
            return $this->rechazarLiquidado($registro);
        }

        $registro->delete();

        return back()->with('exito', 'Registro eliminado.');
    }

    // Un registro liquidado ya se pagó: no se puede cambiar ni borrar.
    private function rechazarLiquidado(RegistroHora $registro)
    {
        return redirect()->route('usuarios.show', $registro->usuario_id)
            ->with('error', 'Este registro ya fue liquidado y no se puede modificar.');
    }
}
