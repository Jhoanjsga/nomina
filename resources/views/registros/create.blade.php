@extends('layouts.app')

@section('titulo', 'Agregar registro')

@section('contenido')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="h3 mb-1">Agregar registro a {{ $usuario->nombre }}</h1>
            <div class="text-muted small">
                Se puede registrar cualquier día hábil, también de una quincena ya pagada: quedará pendiente para la siguiente liquidación.
            </div>
        </div>
        <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary">Volver</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('registros.store', $usuario) }}">
                @csrf
                @include('parciales.campos_registro', ['registro' => null, 'fechasPermitidas' => null])

                <button type="submit" class="btn btn-primary mt-3">Agregar</button>
            </form>
        </div>
    </div>
@endsection
