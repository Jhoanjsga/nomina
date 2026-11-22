@extends('layouts.app')

@section('titulo', 'Inicio')

@section('contenido')
    <h1 class="h3">Bienvenido, {{ auth()->user()->nombre }}</h1>

    @if (auth()->user()->esAdministrador())
        <p class="text-muted">Horas pendientes por liquidar de cada dependiente.</p>

        <div class="row g-3">
            @forelse ($tarjetas as $tarjeta)
                <div class="col-sm-6 col-md-4 col-lg-3">
                    <a href="{{ route('usuarios.show', $tarjeta['usuario']) }}" class="card h-100 shadow-sm text-decoration-none text-body">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h2 class="h6 mb-0">{{ $tarjeta['usuario']->nombre }}</h2>
                                @if ($tarjeta['usuario']->activo)
                                    <span class="badge bg-success">Activo</span>
                                @else
                                    <span class="badge bg-secondary">Inactivo</span>
                                @endif
                            </div>
                            <div class="text-muted small">Horas pendientes</div>
                            <div class="fw-semibold">{{ number_format($tarjeta['horas'], 2, ',', '.') }}</div>
                            <div class="text-muted small mt-1">Total a pagar</div>
                            <div class="fs-5 fw-semibold">${{ number_format($tarjeta['valor'], 0, ',', '.') }}</div>
                        </div>
                    </a>
                </div>
            @empty
                <div class="col">
                    <div class="alert alert-info mb-0">Todavía no hay dependientes registrados.</div>
                </div>
            @endforelse
        </div>
    @else
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">Pendientes por liquidar</h2>
            <a href="{{ route('mis-registros.create') }}" class="btn btn-primary">Agregar registro</a>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Total de horas</div>
                        <div class="fs-3 fw-semibold">{{ number_format($totales['horas'], 2, ',', '.') }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Total a pagar</div>
                        <div class="fs-3 fw-semibold">${{ number_format($totales['valor'], 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        </div>

        @if ($resumen->isNotEmpty())
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h3 class="h6">Resumen por responsable</h3>
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Responsable</th>
                                <th class="text-end">Horas</th>
                                <th class="text-end">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($resumen as $fila)
                                <tr>
                                    <td>{{ $fila['responsable'] }}</td>
                                    <td class="text-end">{{ number_format($fila['horas'], 2, ',', '.') }}</td>
                                    <td class="text-end">${{ number_format($fila['valor'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="card shadow-sm">
            @include('parciales.tabla_registros', ['registros' => $pendientes, 'conEstado' => false, 'conAcciones' => false])
        </div>
    @endif
@endsection
