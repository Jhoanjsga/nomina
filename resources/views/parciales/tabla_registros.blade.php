{{--
    Tabla de registros de horas. Recibe:
    $registros, $calculadora, $conEstado (columna de estado) y $conAcciones (editar/eliminar, solo administrador).
--}}
<div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>Fecha</th>
                <th>Horario</th>
                <th>Responsable</th>
                <th>Ítem</th>
                <th>Radicado</th>
                <th>Cliente</th>
                <th>Observación</th>
                @if ($conEstado) <th>Estado</th> @endif
                <th class="text-end">Horas</th>
                <th class="text-end">Valor</th>
                @if ($conAcciones) <th class="text-end">Acciones</th> @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($registros as $registro)
                <tr>
                    <td class="text-nowrap">{{ $registro->fecha->format('d/m/Y') }}</td>
                    <td class="text-nowrap">{{ substr($registro->hora_inicio, 0, 5) }} a {{ substr($registro->hora_fin, 0, 5) }}</td>
                    <td>{{ $registro->responsable->nombre }}</td>
                    <td>{{ $registro->item->nombre }}</td>
                    <td>{{ $registro->radicado }}</td>
                    <td>{{ $registro->cliente }}</td>
                    <td>{{ $registro->observacion }}</td>
                    @if ($conEstado)
                        <td>
                            @if ($registro->liquidacion_id)
                                <span class="badge bg-success">Liquidado</span>
                            @else
                                <span class="badge bg-warning text-dark">Pendiente</span>
                            @endif
                        </td>
                    @endif
                    <td class="text-end">{{ number_format($calculadora->horas($registro), 2, ',', '.') }}</td>
                    <td class="text-end">${{ number_format($calculadora->valor($registro), 0, ',', '.') }}</td>
                    @if ($conAcciones)
                        <td class="text-end text-nowrap">
                            {{-- Los liquidados no se tocan: no se muestran los botones (el servidor también lo impide). --}}
                            @unless ($registro->liquidacion_id)
                                <a href="{{ route('registros.edit', $registro) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                                <form method="POST" action="{{ route('registros.destroy', $registro) }}" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar este registro? Esta acción no se puede deshacer.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                                </form>
                            @endunless
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 9 + ($conEstado ? 1 : 0) + ($conAcciones ? 1 : 0) }}" class="text-center text-muted py-4">
                        No hay registros.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
