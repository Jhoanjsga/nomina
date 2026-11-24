@extends('layouts.app')

@section('titulo', 'Reportes')

@section('contenido')
    <h1 class="h3 mb-3">Reportes</h1>

    <form method="GET" action="{{ route('reportes.index') }}" class="row g-2 align-items-end mb-2">
        <div class="col-md-2">
            <label for="desde" class="form-label">Desde</label>
            <input type="date" id="desde" name="desde" required
                   class="form-control @error('desde') is-invalid @enderror" value="{{ $filtros['desde'] }}">
            @error('desde') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-2">
            <label for="hasta" class="form-label">Hasta</label>
            <input type="date" id="hasta" name="hasta" required
                   class="form-control @error('hasta') is-invalid @enderror" value="{{ $filtros['hasta'] }}">
            @error('hasta') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-2">
            <label for="usuario_id" class="form-label">Dependiente</label>
            <select id="usuario_id" name="usuario_id" class="form-select @error('usuario_id') is-invalid @enderror">
                <option value="">Todos</option>
                @foreach ($dependientes as $dependiente)
                    <option value="{{ $dependiente->id }}" @selected($filtros['usuario_id'] == $dependiente->id)>
                        {{ $dependiente->nombre }}{{ $dependiente->activo ? '' : ' (inactivo)' }}
                    </option>
                @endforeach
            </select>
            @error('usuario_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-2">
            <label for="item_id" class="form-label">Ítem</label>
            <select id="item_id" name="item_id" class="form-select @error('item_id') is-invalid @enderror">
                <option value="">Todos</option>
                @foreach ($items as $item)
                    <option value="{{ $item->id }}" @selected($filtros['item_id'] == $item->id)>{{ $item->nombre }}</option>
                @endforeach
            </select>
            @error('item_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-2">
            <label for="estado" class="form-label">Estado</label>
            <select id="estado" name="estado" class="form-select">
                <option value="todos" @selected($filtros['estado'] === 'todos')>Todos</option>
                <option value="pendientes" @selected($filtros['estado'] === 'pendientes')>Pendientes</option>
                <option value="liquidados" @selected($filtros['estado'] === 'liquidados')>Liquidados</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary">Consultar</button>
        </div>
    </form>
    <p class="text-muted small mb-4">Para el reporte de un día, pon la misma fecha en "Desde" y "Hasta". Por defecto se muestra la quincena actual.</p>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <p class="mb-0">
            Del {{ \Carbon\Carbon::parse($filtros['desde'])->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($filtros['hasta'])->format('d/m/Y') }}:
            {{ $registros->count() }} {{ $registros->count() === 1 ? 'registro' : 'registros' }},
            <strong>{{ number_format($totales['horas'], 2, ',', '.') }} horas</strong>,
            <strong>${{ number_format($totales['valor'], 0, ',', '.') }}</strong>
        </p>
        <div class="text-nowrap">
            <a href="{{ route('reportes.excel', $filtros) }}" class="btn btn-outline-success">Excel</a>
            <a href="{{ route('reportes.imprimir', $filtros) }}" class="btn btn-outline-danger">PDF</a>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6">Resumen por responsable (lo que debe devolver cada abogado)</h2>
            @include('parciales.resumen_responsables')
        </div>
    </div>

    <div class="card shadow-sm">
        @include('parciales.tabla_registros', ['conDependiente' => true, 'conEstado' => true, 'conAcciones' => false])
    </div>
@endsection
