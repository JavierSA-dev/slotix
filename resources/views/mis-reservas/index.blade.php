@extends('layouts.master')

@section('title', 'Mis reservas')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-0">Mis reservas</h4>
                <a href="{{ route('reservas.public.index') }}" class="btn btn-primary btn-sm">
                    <i class="bx bx-plus me-1"></i> Nueva reserva
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            @if($reservas->isEmpty())
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bx bx-calendar-x" style="font-size:3rem;color:#ccc;"></i>
                        <p class="mt-3 text-muted">Aún no tienes reservas.</p>
                        <a href="{{ route('reservas.public.index') }}" class="btn btn-primary">Hacer una reserva</a>
                    </div>
                </div>
            @else
                <div class="row g-3">
                    @foreach($reservas as $reserva)
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h6 class="card-title mb-0">{{ $reserva->fecha->format('d/m/Y') }}</h6>
                                            <small class="text-muted">{{ $reserva->hora_inicio_fmt }} – {{ $reserva->hora_fin_fmt }}</small>
                                        </div>
                                        <span class="pill-label pill-label-{{ $reserva->estado === 'confirmada' ? 'primary' : ($reserva->estado === 'cancelada' ? 'secondary' : 'warning') }}">
                                            {{ ucfirst($reserva->estado) }}
                                        </span>
                                    </div>
                                    <p class="mb-1 small"><i class="bx bx-group me-1"></i>{{ $reserva->num_personas }} persona{{ $reserva->num_personas > 1 ? 's' : '' }}</p>
                                    @if($reserva->notas)
                                        <p class="mb-0 small text-muted">{{ Str::limit($reserva->notas, 60) }}</p>
                                    @endif
                                </div>
                                @if($reserva->estado !== 'cancelada')
                                    <div class="card-footer bg-transparent border-top-0 pt-0">
                                        <button class="btn btn-outline-danger btn-sm btn-cancelar-reserva"
                                                data-id="{{ $reserva->id }}">
                                            Cancelar
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(function () {
    $('.btn-cancelar-reserva').on('click', function () {
        if (!confirm('¿Seguro que quieres cancelar esta reserva?')) {
            return;
        }

        const id = $(this).data('id');
        const $btn = $(this);
        $btn.prop('disabled', true).text('Cancelando...');

        $.ajax({
            url: '/mis-reservas/' + id + '/cancelar',
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function () {
                location.reload();
            },
            error: function (xhr) {
                alert(xhr.responseJSON?.message || 'Error al cancelar.');
                $btn.prop('disabled', false).text('Cancelar');
            }
        });
    });
});
</script>
@endpush
