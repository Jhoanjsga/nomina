@extends('layouts.app')

@section('titulo', 'Responsables')

@section('contenido')
    <h1 class="h3 mb-3">Responsables</h1>
    <p class="text-muted">Savio y los abogados para quienes trabajan los dependientes. No se eliminan: se inactivan.</p>

    {{-- Crear un abogado nuevo --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('responsables.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-6">
                    <label for="nombre" class="form-label">Nuevo abogado</label>
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

    @include('parciales.filtro_estado', ['ruta' => 'responsables.index'])

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($responsables as $responsable)
                        <tr>
                            <td>{{ $responsable->nombre }}</td>
                            <td>{{ $responsable->esSavio() ? 'Savio (firma)' : 'Abogado' }}</td>
                            <td>
                                @if ($responsable->activo)
                                    <span class="badge bg-success">Activo</span>
                                @else
                                    <span class="badge bg-secondary">Inactivo</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('responsables.edit', $responsable) }}" class="btn btn-sm btn-outline-primary">Editar</a>

                                @unless ($responsable->esSavio())
                                    <form method="POST" action="{{ route('responsables.estado', $responsable) }}" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-warning">
                                            {{ $responsable->activo ? 'Inactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No hay responsables en este filtro.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
