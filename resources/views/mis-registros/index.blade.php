@extends('layouts.app')

@section('titulo', 'Mis horas activas')

@section('contenido')
    <h1 class="h3 mb-3">Mis horas activas</h1>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">Registrar horas</h2>

            @if ($items->isEmpty())
                <div class="alert alert-warning">
                    Todavía no hay ítems disponibles. Pide al administrador que los cree antes de registrar horas.
                </div>
            @endif

            <form method="POST" action="{{ route('mis-registros.store') }}">
                @csrf
                @include('parciales.campos_registro', ['registro' => null])

                <button type="submit" class="btn btn-primary mt-3">Registrar</button>
            </form>
        </div>
    </div>

    <h2 class="h5 mb-3">Pendientes por liquidar</h2>

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
@endsection
