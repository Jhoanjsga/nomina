<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarItemRequest;
use App\Models\Item;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    // Lista con filtro y formulario para crear en la misma pantalla.
    public function index(Request $request)
    {
        $estado = $request->query('estado', 'activos');

        $consulta = Item::orderBy('nombre');

        if ($estado === 'activos') {
            $consulta->where('activo', true);
        } elseif ($estado === 'inactivos') {
            $consulta->where('activo', false);
        }

        $items = $consulta->get();

        return view('items.index', compact('items', 'estado'));
    }

    public function store(GuardarItemRequest $request)
    {
        Item::create([
            'nombre' => $request->validated('nombre'),
            'activo' => true,
        ]);

        return redirect()->route('items.index')->with('exito', 'Ítem creado correctamente.');
    }

    public function edit(Item $item)
    {
        return view('items.edit', compact('item'));
    }

    public function update(GuardarItemRequest $request, Item $item)
    {
        $item->update(['nombre' => $request->validated('nombre')]);

        return redirect()->route('items.index')->with('exito', 'Ítem actualizado correctamente.');
    }

    // Los ítems no se borran (pueden tener registros): se inactivan.
    public function cambiarEstado(Item $item)
    {
        $item->update(['activo' => ! $item->activo]);

        $mensaje = $item->activo ? 'Ítem activado.' : 'Ítem inactivado.';

        return back()->with('exito', $mensaje);
    }
}
