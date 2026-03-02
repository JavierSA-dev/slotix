@extends('layouts.public')

@section('title', 'Reservar partida')

@section('content')
    <div class="mb-4">
        <h1 class="mg-section-title">
            Reserva tu partida
            <small>Selecciona día y horario</small>
        </h1>
    </div>

    @if(!$horario)
        <div class="mg-empty-state">
            <div class="mg-empty-icon">⛳</div>
            <p>En este momento no hay horario configurado. Vuelve pronto.</p>
        </div>
    @elseif(empty($fechasDisponibles))
        <div class="mg-empty-state">
            <div class="mg-empty-icon">📅</div>
            <p>No hay fechas disponibles próximamente. Vuelve pronto.</p>
        </div>
    @else
        {{-- Tira de chips de fechas --}}
        <div class="mg-fechas-strip" id="mg-fechas"></div>

        {{-- Grid de franjas (cargado por AJAX) --}}
        <div class="mg-franjas-wrapper">
            <div id="franjas-wrapper">
                <div class="mg-loading">
                    <div class="spinner-border" role="status"></div>
                </div>
            </div>
        </div>
    @endif

    @include('reservas.public.partials.modal-reserva')
@endsection

@push('scripts')
    <script>
        window.mgFechas = @json($fechasDisponibles);
    </script>
    @vite(['resources/js/pages/reservas-public.js'])
@endpush
