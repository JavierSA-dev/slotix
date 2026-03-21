@if($reservas->isEmpty())
    <div class="mg-empty-state">
        <div class="mg-empty-icon">📅</div>
        <p>No hay reservas para el rango seleccionado.</p>
        <p class="text-muted small mt-2">Accede a la página de tu empresa para hacer una reserva.</p>
    </div>
@else
    <div class="mg-mis-reservas-grid">
        @foreach($reservas as $reserva)
            <div class="mg-reserva-item">
                <div class="mg-ri-header">
                    <div>
                        <div class="mg-ri-fecha"><span class="mg-ri-dia">{{ $reserva->dia_semana }}</span> {{ $reserva->fecha->format('d/m/Y') }}</div>
                        <div class="mg-ri-hora">{{ $reserva->hora_inicio_fmt }} – {{ $reserva->hora_fin_fmt }}</div>
                    </div>
                    <span class="mg-estado-pill {{ $reserva->estado }}">{{ ucfirst($reserva->estado) }}</span>
                </div>
                <div class="mg-ri-body">
                    <span><i class="bx bx-group me-1"></i>{{ $reserva->num_personas }} persona{{ $reserva->num_personas > 1 ? 's' : '' }}</span>
                    @if($reserva->notas)
                        <span class="mg-ri-notas">{{ Str::limit($reserva->notas, 60) }}</span>
                    @endif
                </div>
                <div class="mg-ri-actions">
                    <a href="{{ $reserva->google_calendar_url }}" target="_blank" rel="noopener" class="btn-gcal">
                        <i class="bx bx-calendar-plus"></i> Google Calendar
                    </a>
                    @if($reserva->estado !== 'cancelada')
                        <button class="btn btn-outline-danger btn-sm btn-cancelar-mi-reserva"
                                data-id="{{ $reserva->id }}"
                                style="margin-left:auto;">
                            Cancelar
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif
