@extends('layouts.app')

@section('titulo', 'Confirmar liquidación')

@section('contenido')
    <h1 class="h3 mb-1">Confirmar liquidación de {{ $usuario->nombre }}</h1>
    <p class="text-muted">
        Registros pendientes del {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }}
        al {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}.
        Fecha de liquidación: {{ today()->format('d/m/Y') }}.
    </p>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Registros</div>
                    <div class="fs-3 fw-semibold">{{ $registros->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Total de horas</div>
                    <div class="fs-3 fw-semibold">{{ number_format($totales['horas'], 2, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Total a pagar</div>
                    <div class="fs-3 fw-semibold">${{ number_format($totales['valor'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-warning">
        Al confirmar, estos registros quedan liquidados: ya no se podrán editar ni eliminar. Esta acción no se puede deshacer.
    </div>

    <form method="POST" action="{{ route('liquidaciones.store', $usuario) }}" class="mb-4">
        @csrf
        <input type="hidden" name="desde" value="{{ $desde }}">
        <input type="hidden" name="hasta" value="{{ $hasta }}">
        <button type="submit" class="btn btn-danger">Confirmar liquidación</button>
        <a href="{{ route('usuarios.show', [$usuario, 'desde' => $desde, 'hasta' => $hasta]) }}" class="btn btn-outline-secondary">Cancelar</a>
    </form>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6">Resumen por responsable</h2>
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

    <div class="card shadow-sm">
        @include('parciales.tabla_registros', ['conEstado' => false, 'conAcciones' => false])
    </div>
@endsection
