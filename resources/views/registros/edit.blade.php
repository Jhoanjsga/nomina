@extends('layouts.app')

@section('titulo', 'Editar registro')

@section('contenido')
    <h1 class="h3 mb-3">Editar registro de {{ $registro->usuario->nombre }}</h1>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('registros.update', $registro) }}">
                @csrf
                @method('PUT')
                @include('parciales.campos_registro', ['fechasPermitidas' => null])

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                    <a href="{{ route('usuarios.show', $registro->usuario_id) }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection
