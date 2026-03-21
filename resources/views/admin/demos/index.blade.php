@extends('layouts.master')

@section('title', 'Demos')

@section('content')
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="bx bx-play-circle me-1"></i> Demos para clientes</h4>
            <button type="button" class="btn btn-primary" id="btn-nueva-demo">
                <i class="bx bx-plus me-1"></i> Generar nueva demo
            </button>
        </div>
    </div>

    <div id="demo-alert-success" class="alert alert-success d-none" role="alert"></div>
    <div id="demo-alert-error" class="alert alert-danger d-none" role="alert"></div>

    {{-- Modal selector de tema --}}
    <div class="modal fade" id="modal-nueva-demo" tabindex="-1" aria-labelledby="modal-nueva-demo-label" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-nueva-demo-label"><i class="bx bx-play-circle me-1"></i>Nueva demo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold mb-2">Tema visual</label>
                    <div class="d-flex flex-column gap-2">
                        @foreach(config('temas') as $key => $tema)
                        <div class="form-check tema-opcion border rounded p-2 ps-4" style="cursor:pointer;">
                            <input class="form-check-input" type="radio" name="tema_demo" id="tema_{{ $key }}" value="{{ $key }}" {{ $key === 'neon' ? 'checked' : '' }}>
                            <label class="form-check-label w-100" for="tema_{{ $key }}" style="cursor:pointer;">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="d-flex gap-1">
                                        @foreach($tema['preview'] as $color)
                                            <span style="width:14px;height:14px;border-radius:50%;background:{{ $color }};display:inline-block;border:1px solid #ccc;"></span>
                                        @endforeach
                                    </div>
                                    <span class="fw-semibold">{{ $tema['nombre'] }}</span>
                                    <small class="text-muted">{{ $tema['descripcion'] }}</small>
                                </div>
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btn-confirmar-demo">
                        <i class="bx bx-plus me-1"></i>Generar demo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0" id="demos-table">
                <thead class="table-light">
                    <tr>
                        <th>URL de la demo</th>
                        <th>Creada por</th>
                        <th>Creada el</th>
                        <th>Expira el</th>
                        <th>Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($demos as $demo)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <code class="text-truncate" style="max-width:300px;" id="url-{{ $demo->id }}">{{ route('reservas.public.index', $demo->tenant_id) }}</code>
                                <button class="btn btn-sm btn-outline-secondary btn-copiar"
                                    data-url="{{ route('reservas.public.index', $demo->tenant_id) }}"
                                    title="Copiar URL">
                                    <i class="bx bx-copy"></i>
                                </button>
                                <a href="{{ route('reservas.public.index', $demo->tenant_id) }}" target="_blank" class="btn btn-sm btn-outline-info" title="Abrir demo">
                                    <i class="bx bx-link-external"></i>
                                </a>
                            </div>
                        </td>
                        <td>{{ $demo->creadoPor?->name ?? '—' }}</td>
                        <td>{{ $demo->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $demo->expira_en->format('d/m/Y H:i') }}</td>
                        <td>
                            @if($demo->estaExpirada())
                                <span class="pill-label pill-label-secondary">Expirada</span>
                            @else
                                <span class="pill-label pill-label-primary">Activa</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-danger btn-eliminar-demo"
                                data-id="{{ $demo->tenant_id }}"
                                title="Eliminar demo">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No hay demos creadas todavía.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(function () {
    // ─── Abrir modal nueva demo ───────────────────────────────
    $('#btn-nueva-demo').on('click', function () {
        $('#modal-nueva-demo').modal('show');
    });

    // ─── Confirmar y generar demo ─────────────────────────────
    $('#btn-confirmar-demo').on('click', function () {
        var $btn = $(this);
        var tema = $('input[name="tema_demo"]:checked').val();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Generando...');
        $('#demo-alert-success, #demo-alert-error').addClass('d-none').text('');

        $.ajax({
            url: '{{ route('admin.demos.store') }}',
            type: 'POST',
            data: { '_token': $('meta[name="csrf-token"]').attr('content'), tema: tema },
            success: function (data) {
                $('#modal-nueva-demo').modal('hide');
                var html = 'Demo creada. URL: <strong>' + data.url + '</strong> &nbsp;';
                html += '<button class="btn btn-sm btn-outline-success btn-copiar" data-url="' + data.url + '"><i class="bx bx-copy"></i> Copiar</button>';
                html += ' &nbsp; Expira el: ' + data.expira_en;
                $('#demo-alert-success').removeClass('d-none').html(html);
                setTimeout(function () { location.reload(); }, 3000);
            },
            error: function () {
                $('#demo-alert-error').removeClass('d-none').text('Error al crear la demo. Inténtalo de nuevo.');
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="bx bx-plus me-1"></i>Generar demo');
            }
        });
    });

    // ─── Copiar URL ───────────────────────────────────────────
    $(document).on('click', '.btn-copiar', function () {
        var url = $(this).data('url');
        navigator.clipboard.writeText(url).then(function () {
            var $btn = $(this);
            $btn.html('<i class="bx bx-check"></i>');
            setTimeout(function () { $btn.html('<i class="bx bx-copy"></i>'); }, 1500);
        }.bind(this));
    });

    // ─── Eliminar demo ────────────────────────────────────────
    $(document).on('click', '.btn-eliminar-demo', function () {
        var id = $(this).data('id');
        var $btn = $(this);

        Swal.fire({
            title: '¿Eliminar esta demo?',
            text: 'Se borrará la base de datos y el enlace dejará de funcionar.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#d33',
        }).then(function (result) {
            if (!result.isConfirmed) { return; }

            $btn.prop('disabled', true);

            $.ajax({
                url: '{{ route('admin.demos.index') }}/' + id,
                type: 'POST',
                data: { '_method': 'DELETE', '_token': $('meta[name="csrf-token"]').attr('content') },
                success: function () {
                    $btn.closest('tr').fadeOut(400, function () { $(this).remove(); });
                },
                error: function () {
                    Swal.fire('Error', 'Error al eliminar la demo.', 'error');
                    $btn.prop('disabled', false);
                }
            });
        });
    });
});
</script>
@endpush
