@extends('layouts.master')

@section('title', 'Panel principal')

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="mb-0">Panel principal</h4>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-muted fw-medium">Reservas hoy</p>
                            <h4 class="mb-0">{{ $reservasHoy }}</h4>
                        </div>
                        <div class="avatar-sm rounded-circle bg-primary align-self-center mini-stat-icon">
                            <span class="avatar-title rounded-circle bg-primary">
                                <i class="bx bx-calendar-check font-size-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-muted fw-medium">Próximos 7 días</p>
                            <h4 class="mb-0">{{ $reservasSemana }}</h4>
                        </div>
                        <div class="avatar-sm rounded-circle bg-success align-self-center mini-stat-icon">
                            <span class="avatar-title rounded-circle bg-success">
                                <i class="bx bx-calendar font-size-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mini-stats-wid">
                <div class="card-body">
                    <div class="d-flex">
                        <div class="flex-grow-1">
                            <p class="text-muted fw-medium">Canceladas este mes</p>
                            <h4 class="mb-0">{{ $canceladasMes }}</h4>
                        </div>
                        <div class="avatar-sm rounded-circle bg-danger align-self-center mini-stat-icon">
                            <span class="avatar-title rounded-circle bg-danger">
                                <i class="bx bx-x-circle font-size-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Calendario de reservas --}}
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0">Calendario de reservas</h5>
                <div class="d-flex gap-2 align-items-center" style="font-size: 0.8rem; color: #6c757d;">
                    <span class="d-flex align-items-center gap-1">
                        <span style="width:12px;height:12px;background:#2a5228;border-radius:3px;display:inline-block;"></span>
                        Confirmada
                    </span>
                    <span class="d-flex align-items-center gap-1">
                        <span style="width:12px;height:12px;background:#856404;border-radius:3px;display:inline-block;"></span>
                        Pendiente
                    </span>
                </div>
            </div>
            <div id="admin-calendar" data-events-url="{{ route('admin.reservas.calendarEvents') }}"></div>
        </div>
    </div>

    {{-- Modal detalle evento calendario --}}
    <div class="modal fade" id="modal-evento-detalle" tabindex="-1" aria-labelledby="modalEvDetalleLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEvDetalleLabel">
                        <i class="bx bx-calendar me-1"></i> Detalle de reserva
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr>
                                <th class="text-muted fw-normal" style="width:120px;">Nombre</th>
                                <td id="modal-ev-nombre" class="fw-semibold"></td>
                            </tr>
                            <tr>
                                <th class="text-muted fw-normal">Estado</th>
                                <td><span id="modal-ev-estado" class="pill-label"></span></td>
                            </tr>
                            <tr>
                                <th class="text-muted fw-normal">Fecha</th>
                                <td id="modal-ev-fecha"></td>
                            </tr>
                            <tr>
                                <th class="text-muted fw-normal">Horario</th>
                                <td id="modal-ev-hora"></td>
                            </tr>
                            <tr>
                                <th class="text-muted fw-normal">Personas</th>
                                <td id="modal-ev-personas"></td>
                            </tr>
                            <tr>
                                <th class="text-muted fw-normal">Email</th>
                                <td id="modal-ev-email"></td>
                            </tr>
                            <tr>
                                <th class="text-muted fw-normal">Teléfono</th>
                                <td id="modal-ev-telefono"></td>
                            </tr>
                            <tr>
                                <th class="text-muted fw-normal">Notas</th>
                                <td id="modal-ev-notas" class="text-muted fst-italic"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <a id="modal-ev-link" href="#" target="_blank" class="btn btn-sm btn-info d-none">
                        <i class="bx bx-link-external me-1"></i>Ver reserva completa
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/pages/admin-dashboard.js'])
@endpush
