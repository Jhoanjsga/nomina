@extends('layouts.app')

@section('titulo', 'Registros de '.$usuario->nombre)

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
            <a href="{{ route('inicio') }}" class="btn btn-outline-secondary">Volver</a>
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

    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="mb-0">
            {{ $registros->count() }} {{ $registros->count() === 1 ? 'registro' : 'registros' }}:
            <strong>{{ number_format($totales['horas'], 2, ',', '.') }} horas</strong>,
            <strong>${{ number_format($totales['valor'], 0, ',', '.') }}</strong>
        </p>
        <div class="text-nowrap">
            @if ($registros->isNotEmpty())
                <a href="{{ route('usuarios.exportar', [$usuario, 'desde' => $desde, 'hasta' => $hasta, 'estado' => $estado]) }}"
                   class="btn btn-outline-success">Exportar a Excel</a>
            @endif
            @if ($puedeLiquidar)
                <a href="{{ route('liquidaciones.confirmar', [$usuario, 'desde' => $desde, 'hasta' => $hasta]) }}"
                   class="btn btn-danger">Liquidar pendientes del rango</a>
            @elseif (! $desde || ! $hasta)
                <span class="text-muted small">Para liquidar, filtra un rango de fechas (desde y hasta).</span>
            @endif
        </div>
    </div>

    <div class="card shadow-sm">
        @include('parciales.tabla_registros', ['conEstado' => true, 'conAcciones' => true])
    </div>
@endsection
