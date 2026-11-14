@extends('layouts.app')

@section('titulo', 'Editar responsable')

@section('contenido')
    <h1 class="h3 mb-3">Editar responsable</h1>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('responsables.update', $responsable) }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre</label>
                    <input type="text" id="nombre" name="nombre" maxlength="45" required
                           class="form-control @error('nombre') is-invalid @enderror"
                           value="{{ old('nombre', $responsable->nombre) }}">
                    @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <p class="text-muted small">Tipo: {{ $responsable->esSavio() ? 'Savio (firma)' : 'Abogado' }}. El tipo no se puede cambiar.</p>
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
                <a href="{{ route('responsables.index') }}" class="btn btn-outline-secondary">Volver</a>
            </form>
        </div>
    </div>
@endsection
