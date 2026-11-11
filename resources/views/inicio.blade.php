@extends('layouts.app')

@section('titulo', 'Inicio')

@section('contenido')
    <h1 class="h3">Bienvenido, {{ auth()->user()->nombre }}</h1>
    <p class="text-muted">Rol: {{ auth()->user()->rol }}</p>
@endsection