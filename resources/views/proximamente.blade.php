@extends('layouts.app')

@section('titulo', $modulo)

@section('contenido')
    <h1 class="h3">{{ $modulo }}</h1>
    <p class="text-muted">Este módulo se construye en una próxima actividad.</p>
@endsection