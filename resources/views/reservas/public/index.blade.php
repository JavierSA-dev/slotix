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
        {{-- Carrusel de fechas --}}
        <div class="fecha-selector mb-4" id="fecha-selector">
            @foreach($fechas as $fecha)
                @php
                    [$diaPart, $numPart] = explode(' ', $fecha['etiqueta']);
                @endphp
                <button class="fecha-pill" data-fecha="{{ $fecha['valor'] }}">
                    <span class="fecha-pill-dia">{{ $diaPart }}</span>
                    <span class="fecha-pill-num">{{ $numPart }}</span>
                </button>
            @endforeach
        </div>

        {{-- Grid de franjas (cargado por AJAX) --}}
        <div id="franjas-wrapper">
            <div class="mg-empty-state">
                <div class="mg-empty-icon">📅</div>
                <p>Selecciona una fecha para ver los horarios disponibles</p>
            </div>
        </div>
    @endif

    @include('reservas.public.partials.modal-reserva')
@endsection

@push('scripts')
    @vite(['resources/js/pages/reservas-public.js'])
@endpush
