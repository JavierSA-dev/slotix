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
    @else
        <div class="mg-reserva-layout">
            {{-- Calendario Flatpickr --}}
            <div class="mg-calendar-wrapper">
                <div id="mg-calendar"></div>
            </div>

            {{-- Grid de franjas (cargado por AJAX) --}}
            <div class="mg-franjas-wrapper">
                <div id="franjas-wrapper">
                    <div class="mg-empty-state">
                        <div class="mg-empty-icon">📅</div>
                        <p>Selecciona una fecha para ver los horarios disponibles</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @include('reservas.public.partials.modal-reserva')
@endsection

@push('scripts')
    <script>
        window.mgDiasHabiles = @json($diasHabiles);
    </script>
    @vite(['resources/js/pages/reservas-public.js'])
@endpush
