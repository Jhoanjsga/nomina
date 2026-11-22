@extends('layouts.app')

@section('titulo', 'Agregar registro')

@section('contenido')
    <h1 class="h3 mb-3">Agregar registro</h1>

    <div class="card shadow-sm">
        <div class="card-body">
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
@endsection
