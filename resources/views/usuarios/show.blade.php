@extends('layouts.app')

@section('titulo', 'Detalle de '.$usuario->nombre)

@section('contenido')
    @php($valorActual = $usuario->valorHoraActual())

    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h1 class="h3 mb-1">
                {{ $usuario->nombre }}
                @if ($usuario->activo)
                    <span class="badge bg-success fs-6 align-middle">Activo</span>
                @else
                    <span class="badge bg-secondary fs-6 align-middle">Inactivo</span>
                @endif
            </h1>
            <div class="text-muted">
                Cédula {{ $usuario->cedula }} ·
                Valor de la hora actual:
                {{ $valorActual ? '$'.number_format($valorActual->valor, 0, ',', '.') : 'sin valor vigente' }}
            </div>
        </div>
        <div class="text-nowrap">
            <a href="{{ route('usuarios.edit', $usuario) }}" class="btn btn-outline-primary">Editar usuario</a>
            <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary">Volver</a>
        </div>
    </div>

    <form method="GET" action="{{ route('usuarios.show', $usuario) }}" class="row g-2 align-items-end mb-3">
        <div class="col-md-3">
            <label for="desde" class="form-label">Desde</label>
            <input type="date" id="desde" name="desde"
                   class="form-control @error('desde') is-invalid @enderror" value="{{ $desde }}">
            @error('desde') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label for="hasta" class="form-label">Hasta</label>
            <input type="date" id="hasta" name="hasta"
                   class="form-control @error('hasta') is-invalid @enderror" value="{{ $hasta }}">
            @error('hasta') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label for="estado" class="form-label">Estado</label>
            <select id="estado" name="estado" class="form-select">
                <option value="pendientes" @selected($estado === 'pendientes')>Pendientes</option>
                <option value="liquidados" @selected($estado === 'liquidados')>Liquidados</option>
                <option value="todos" @selected($estado === 'todos')>Todos</option>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="{{ route('usuarios.show', $usuario) }}" class="btn btn-outline-secondary">Limpiar</a>
        </div>
    </form>

    <p class="mb-3">
        {{ $registros->count() }} {{ $registros->count() === 1 ? 'registro' : 'registros' }}:
        <strong>{{ number_format($totales['horas'], 2, ',', '.') }} horas</strong>,
        <strong>${{ number_format($totales['valor'], 0, ',', '.') }}</strong>
    </p>

    <div class="card shadow-sm mb-4">
        @include('parciales.tabla_registros', ['conEstado' => true, 'conAcciones' => true])
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h5 mb-3">Agregar registro</h2>
            <p class="text-muted small">
                Se puede registrar cualquier día hábil, también de una quincena ya pagada: quedará pendiente para la siguiente liquidación.
            </p>

            <form method="POST" action="{{ route('registros.store', $usuario) }}">
                @csrf
                @include('parciales.campos_registro', ['registro' => null, 'fechasPermitidas' => null])

                <button type="submit" class="btn btn-primary mt-3">Agregar</button>
            </form>
        </div>
    </div>
@endsection
