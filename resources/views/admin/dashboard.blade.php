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

    <div class="row g-3">
        <div class="col-md-6">
            <a href="{{ route('admin.reservas.index') }}" class="card text-decoration-none">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bx bx-list-ul font-size-24 text-primary"></i>
                    <div>
                        <h6 class="mb-0">Ver todas las reservas</h6>
                        <small class="text-muted">Gestiona y filtra las reservas</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="{{ route('admin.horario.index') }}" class="card text-decoration-none">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bx bx-time-five font-size-24 text-success"></i>
                    <div>
                        <h6 class="mb-0">Configurar horario</h6>
                        <small class="text-muted">Días, franjas y aforo</small>
                    </div>
                </div>
            </a>
        </div>
    </div>
@endsection
