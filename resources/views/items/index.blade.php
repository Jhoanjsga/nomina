@extends('layouts.app')

@section('titulo', 'Ítems')

@section('contenido')
    <h1 class="h3 mb-3">Ítems</h1>
    <p class="text-muted">Tipos de actividad que el dependiente elige al registrar sus horas. No se eliminan: se inactivan.</p>

    {{-- Crear un ítem nuevo --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('items.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-6">
                    <label for="nombre" class="form-label">Nuevo ítem</label>
                    <input type="text" id="nombre" name="nombre" maxlength="45" required
                           class="form-control @error('nombre') is-invalid @enderror" value="{{ old('nombre') }}">
                    @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary">Agregar</button>
                </div>
            </form>
        </div>
    </div>

    @include('parciales.filtro_estado', ['ruta' => 'items.index'])

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td>{{ $item->nombre }}</td>
                            <td>
                                @if ($item->activo)
                                    <span class="badge bg-success">Activo</span>
                                @else
                                    <span class="badge bg-secondary">Inactivo</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('items.edit', $item) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                                <form method="POST" action="{{ route('items.estado', $item) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-outline-warning">
                                        {{ $item->activo ? 'Inactivar' : 'Activar' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">No hay ítems en este filtro.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
