<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reporte {{ $filtros['desde'] }} a {{ $filtros['hasta'] }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-imprimir { display: none; }
        }
    </style>
</head>
<body class="p-4">
    <div class="alert alert-info no-imprimir">
        Para guardar en PDF usa <strong>Ctrl + P</strong> y elige <strong>Guardar como PDF</strong>.
        <a href="{{ route('reportes.index', $filtros) }}" class="ms-2">Volver a reportes</a>
    </div>

    <h1 class="h4 mb-1">Savio Abogados · Reporte de horas</h1>
    <p class="mb-3">
        Del {{ \Carbon\Carbon::parse($filtros['desde'])->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($filtros['hasta'])->format('d/m/Y') }}
        · Dependiente: {{ $dependiente->nombre ?? 'Todos' }}
        · Ítem: {{ $item->nombre ?? 'Todos' }}
        · Estado: {{ ucfirst($filtros['estado']) }}
    </p>

    <p>
        {{ $registros->count() }} {{ $registros->count() === 1 ? 'registro' : 'registros' }}:
        <strong>{{ number_format($totales['horas'], 2, ',', '.') }} horas</strong>,
        <strong>${{ number_format($totales['valor'], 0, ',', '.') }}</strong>
    </p>

    <h2 class="h6">Resumen por responsable (lo que debe devolver cada abogado)</h2>
    <div class="mb-4">
        @include('parciales.resumen_responsables')
    </div>

    <h2 class="h6">Registros</h2>
    @include('parciales.tabla_registros', ['conDependiente' => true, 'conEstado' => true, 'conAcciones' => false])

    <p class="text-muted small mt-3">Generado el {{ now()->format('d/m/Y H:i') }}</p>
</body>
</html>
