@extends('layouts.app')

@section('titulo', 'Mis reportes')

@section('contenido')
    <h1 class="h3 mb-3">Mis reportes</h1>

    <form method="GET" action="{{ route('mis-reportes.index') }}" class="row g-2 align-items-end mb-4">
        <div class="col-md-4">
            <label for="desde" class="form-label">Desde</label>
            <input type="date" id="desde" name="desde" required
                   class="form-control @error('desde') is-invalid @enderror" value="{{ $desde }}">
            @error('desde') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <label for="hasta" class="form-label">Hasta</label>
            <input type="date" id="hasta" name="hasta" required
                   class="form-control @error('hasta') is-invalid @enderror" value="{{ $hasta }}">
            @error('hasta') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-primary">Consultar</button>
        </div>
    </form>

    <p class="mb-3">
        Del {{ \Carbon\Carbon::parse($desde)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($hasta)->format('d/m/Y') }}:
        <strong>{{ number_format($totales['horas'], 2, ',', '.') }} horas</strong>,
        <strong>${{ number_format($totales['valor'], 0, ',', '.') }}</strong>
    </p>

    <div class="card shadow-sm">
        @include('parciales.tabla_registros', ['registros' => $registros, 'conEstado' => true, 'conAcciones' => false])
    </div>
@endsection
