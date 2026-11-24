@extends('layouts.app')

@section('titulo', 'Eliminar usuario')

@section('contenido')
    <h1 class="h3 mb-3">Eliminar a {{ $usuario->nombre }}</h1>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Cédula</dt>
                <dd class="col-sm-9">{{ $usuario->cedula }}</dd>
                <dt class="col-sm-3">Correo</dt>
                <dd class="col-sm-9">{{ $usuario->correo }}</dd>
                <dt class="col-sm-3">Rol</dt>
                <dd class="col-sm-9">{{ ucfirst($usuario->rol) }}</dd>
            </dl>
        </div>
    </div>

    @if ($tieneMovimientos)
        <div class="alert alert-info">
            Este usuario tiene registros o liquidaciones, por eso no se puede eliminar.
            @if ($usuario->activo) Puedes inactivarlo para que no vuelva a iniciar sesión. @else Ya está inactivo. @endif
        </div>

        @if ($usuario->activo)
            <form method="POST" action="{{ route('usuarios.estado', $usuario) }}" class="d-inline">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-warning">Inactivar</button>
            </form>
        @endif
        <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary">Volver</a>
    @else
        <div class="alert alert-warning">¿Seguro que deseas eliminar este usuario? Esta acción no se puede deshacer.</div>

        <form method="POST" action="{{ route('usuarios.destroy', $usuario) }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Eliminar</button>
            <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary">Cancelar</a>
        </form>
    @endif
@endsection
