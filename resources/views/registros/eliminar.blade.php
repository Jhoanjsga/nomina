@extends('layouts.app')

@section('titulo', 'Eliminar registro')

@section('contenido')
    <h1 class="h3 mb-3">¿Eliminar este registro de {{ $registro->usuario->nombre }}?</h1>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Fecha</dt>
                <dd class="col-sm-9">{{ $registro->fecha->format('d/m/Y') }}</dd>
                <dt class="col-sm-3">Horario</dt>
                <dd class="col-sm-9">{{ substr($registro->hora_inicio, 0, 5) }} a {{ substr($registro->hora_fin, 0, 5) }}</dd>
                <dt class="col-sm-3">Responsable</dt>
                <dd class="col-sm-9">{{ $registro->responsable->nombre }}</dd>
                <dt class="col-sm-3">Ítem</dt>
                <dd class="col-sm-9">{{ $registro->item->nombre }}</dd>
                <dt class="col-sm-3">Radicado</dt>
                <dd class="col-sm-9">{{ $registro->radicado }}</dd>
                <dt class="col-sm-3">Cliente</dt>
                <dd class="col-sm-9">{{ $registro->cliente }}</dd>
                <dt class="col-sm-3">Horas</dt>
                <dd class="col-sm-9">{{ number_format($calculadora->horas($registro), 2, ',', '.') }}</dd>
            </dl>
        </div>
    </div>

    <div class="alert alert-warning">Esta acción no se puede deshacer.</div>

    <form method="POST" action="{{ route('registros.destroy', $registro) }}">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger">Eliminar</button>
        <a href="{{ route('usuarios.show', $registro->usuario_id) }}" class="btn btn-outline-secondary">Cancelar</a>
    </form>
@endsection
