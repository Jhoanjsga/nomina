{{-- Horas y valor por responsable. Recibe $resumen (de CalculadoraPago::resumenPorResponsable). --}}
<table class="table table-sm mb-0">
    <thead>
        <tr>
            <th>Responsable</th>
            <th class="text-end">Horas</th>
            <th class="text-end">Valor</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($resumen as $fila)
            <tr>
                <td>{{ $fila['responsable'] }}</td>
                <td class="text-end">{{ number_format($fila['horas'], 2, ',', '.') }}</td>
                <td class="text-end">${{ number_format($fila['valor'], 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="3" class="text-center text-muted">No hay registros.</td>
            </tr>
        @endforelse
    </tbody>
</table>
