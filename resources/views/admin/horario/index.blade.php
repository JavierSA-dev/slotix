@extends('layouts.master')

@section('title', 'Horario y aforo')

@push('style')
@endpush

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="mb-0">Horario y aforo</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 col-xl-6">
            <div class="card">
                <div class="card-body">

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.horario.update') }}">
                        @csrf
                        @method('PUT')

                        {{-- Días de apertura --}}
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Días de apertura</label>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach(['Lun' => 0, 'Mar' => 1, 'Mié' => 2, 'Jue' => 3, 'Vie' => 4, 'Sáb' => 5, 'Dom' => 6] as $nombre => $valor)
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               name="dias_semana[]"
                                               value="{{ $valor }}"
                                               id="dia_{{ $valor }}"
                                               {{ $horario && in_array($valor, $horario->dias_semana) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="dia_{{ $valor }}">{{ $nombre }}</label>
                                    </div>
                                @endforeach
                            </div>
                            @error('dias_semana')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Horas --}}
                        <div class="row mb-3">
                            <div class="col-6">
                                <label for="hora_apertura" class="form-label fw-semibold">Hora apertura</label>
                                <input type="time"
                                       class="form-control @error('hora_apertura') is-invalid @enderror"
                                       id="hora_apertura"
                                       name="hora_apertura"
                                       value="{{ $horarioAperturaFmt }}">
                                @error('hora_apertura')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-6">
                                <label for="hora_cierre" class="form-label fw-semibold">Hora cierre</label>
                                <input type="time"
                                       class="form-control @error('hora_cierre') is-invalid @enderror"
                                       id="hora_cierre"
                                       name="hora_cierre"
                                       value="{{ $horarioCierreFmt }}">
                                @error('hora_cierre')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Duración del tramo --}}
                        <div class="mb-3">
                            <label for="duracion_tramo" class="form-label fw-semibold">Duración del tramo</label>
                            <select class="form-select @error('duracion_tramo') is-invalid @enderror"
                                    id="duracion_tramo"
                                    name="duracion_tramo">
                                @foreach([15 => '15 minutos', 30 => '30 minutos', 45 => '45 minutos', 60 => '1 hora', 90 => '1 hora 30 min', 120 => '2 horas'] as $min => $label)
                                    <option value="{{ $min }}"
                                        {{ $horario && $horario->duracion_tramo == $min ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('duracion_tramo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Aforo --}}
                        <div class="mb-3">
                            <label for="aforo_por_tramo" class="form-label fw-semibold">
                                Aforo por tramo
                                <small class="text-muted fw-normal">(máx. personas simultáneas)</small>
                            </label>
                            <input type="number"
                                   class="form-control @error('aforo_por_tramo') is-invalid @enderror"
                                   id="aforo_por_tramo"
                                   name="aforo_por_tramo"
                                   min="1"
                                   max="100"
                                   value="{{ $horario ? $horario->aforo_por_tramo : 8 }}">
                            @error('aforo_por_tramo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Antelación mínima --}}
                        <div class="row mb-3">
                            <div class="col-6">
                                <label for="horas_min_reserva" class="form-label fw-semibold">
                                    Antelación mín. para reservar
                                    <small class="text-muted fw-normal">(horas)</small>
                                </label>
                                <input type="number"
                                       class="form-control @error('horas_min_reserva') is-invalid @enderror"
                                       id="horas_min_reserva"
                                       name="horas_min_reserva"
                                       min="0"
                                       max="72"
                                       value="{{ $horario ? $horario->horas_min_reserva : 0 }}">
                                <div class="form-text">0 = sin restricción</div>
                                @error('horas_min_reserva')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-6">
                                <label for="horas_min_cancelacion" class="form-label fw-semibold">
                                    Antelación mín. para cancelar
                                    <small class="text-muted fw-normal">(horas)</small>
                                </label>
                                <input type="number"
                                       class="form-control @error('horas_min_cancelacion') is-invalid @enderror"
                                       id="horas_min_cancelacion"
                                       name="horas_min_cancelacion"
                                       min="0"
                                       max="72"
                                       value="{{ $horario ? $horario->horas_min_cancelacion : 0 }}">
                                <div class="form-text">0 = sin restricción</div>
                                @error('horas_min_cancelacion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Máximo de antelación para reservar --}}
                        <div class="mb-4">
                            <label for="semanas_max_reserva" class="form-label fw-semibold">
                                Máximo de antelación para reservar
                                <small class="text-muted fw-normal">(semanas)</small>
                            </label>
                            <input type="number"
                                   class="form-control @error('semanas_max_reserva') is-invalid @enderror"
                                   id="semanas_max_reserva"
                                   name="semanas_max_reserva"
                                   min="1"
                                   max="52"
                                   value="{{ $horario ? $horario->semanas_max_reserva : 4 }}">
                            <div class="form-text">Número de semanas desde hoy que se muestran disponibles para reservar.</div>
                            @error('semanas_max_reserva')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i> Guardar cambios
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    {{-- SECCIÓN DÍAS CERRADOS --}}
    <div class="row mt-4">
        <div class="col-lg-8 col-xl-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Días cerrados / festivos</h5>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalDiaCerrado">
                            <i class="bx bx-plus me-1"></i> Añadir
                        </button>
                    </div>
                    <p class="text-muted small mb-3">Los días marcados aquí no aparecerán como disponibles para reservar, independientemente del horario configurado.</p>

                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Período</th>
                                <th>Motivo</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="tablaDiasCerrados">
                            @forelse($diasCerrados as $dia)
                                <tr id="dia-cerrado-{{ $dia->id }}">
                                    <td>
                                        @if($dia->fecha_inicio->eq($dia->fecha_fin))
                                            {{ $dia->fecha_inicio->format('d/m/Y') }}
                                        @else
                                            {{ $dia->fecha_inicio->format('d/m/Y') }} → {{ $dia->fecha_fin->format('d/m/Y') }}
                                        @endif
                                    </td>
                                    <td>{{ $dia->motivo ?? '—' }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-danger" onclick="eliminarDiaCerrado({{ $dia->id }})">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr id="tablaDiasCerradosVacio">
                                    <td colspan="3" class="text-muted text-center py-3">No hay días cerrados configurados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @include('admin.horario.partials.modal-dia-cerrado')
@endsection
