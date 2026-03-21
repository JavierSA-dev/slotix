<!-- JAVASCRIPT -->
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.all.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/jquery/jquery.min.js')}}"></script>
<script src="{{ URL::asset('build/libs/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
<script src="{{ URL::asset('build/libs/metismenu/metisMenu.min.js')}}"></script>
<script src="{{ URL::asset('build/libs/simplebar/simplebar.min.js')}}"></script>
<script src="{{ URL::asset('build/libs/node-waves/waves.min.js')}}"></script>
<script src="{{ URL::asset('build/js/app.js') }}"></script>
<script>
    $('#change-password').on('submit',function(event){
        event.preventDefault();
        var Id = $('#data_id').val();
        var current_password = $('#current-password').val();
        var password = $('#password').val();
        var password_confirm = $('#password-confirm').val();
        $('#current_passwordError').text('');
        $('#passwordError').text('');
        $('#password_confirmError').text('');
        $.ajax({
            url: "{{ url('update-password') }}" + "/" + Id,
            type:"POST",
            data:{
                "current_password": current_password,
                "password": password,
                "password_confirmation": password_confirm,
                "_token": "{{ csrf_token() }}",
            },
            success:function(response){
                $('#current_passwordError').text('');
                $('#passwordError').text('');
                $('#password_confirmError').text('');
                if(response.isSuccess == false){ 
                    $('#current_passwordError').text(response.Message);
                }else if(response.isSuccess == true){
                    setTimeout(function () {   
                        window.location.href = "{{ route('home') }}";
                    }, 1000);
                }
            },
            error: function(response) {
                $('#current_passwordError').text(response.responseJSON.errors.current_password);
                $('#passwordError').text(response.responseJSON.errors.password);
                $('#password_confirmError').text(response.responseJSON.errors.password_confirmation);
            }
        });
    });
</script>

@hasanyrole('SuperAdmin|Admin')
<script>
    $('#switch-mantenimiento').on('change', function () {
        var $switch = $(this);
        var $label = $('#mantenimiento-label');
        var $wrapper = $('#mantenimiento-toggle-wrapper');
        $switch.prop('disabled', true);

        $.ajax({
            url: "{{ route('admin.mantenimiento.toggle') }}",
            type: 'POST',
            data: { '_token': "{{ csrf_token() }}" },
            success: function (response) {
                $switch.prop('disabled', false);
                if (response.en_mantenimiento) {
                    $label.removeClass('text-muted').addClass('text-warning').text('Mantenimiento');
                    $wrapper.attr('title', 'Desactivar modo mantenimiento');
                } else {
                    $label.removeClass('text-warning').addClass('text-muted').text('Web pública');
                    $wrapper.attr('title', 'Activar modo mantenimiento');
                }
            },
            error: function () {
                $switch.prop('checked', !$switch.prop('checked')).prop('disabled', false);
            }
        });
    });
</script>
@endhasanyrole

@hasanyrole('SuperAdmin|Admin')
<script>
(function () {
    const notifUrl    = document.getElementById('btn-notificaciones')?.dataset.url;
    const leidaUrl    = document.getElementById('btn-notificaciones')?.dataset.leidaUrl;
    const csrfToken   = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (!notifUrl) return;

    const $badge  = $('#notif-badge');
    const $lista  = $('#notif-lista');

    const iconos = {
        nueva_reserva: 'bx-calendar-plus text-success',
        cancelacion:   'bx-calendar-x text-danger',
        cambio_fecha:  'bx-calendar-event text-warning',
        cambio_estado: 'bx-check-circle text-info',
    };

    const etiquetas = {
        nueva_reserva: 'Nueva reserva',
        cancelacion:   'Reserva cancelada',
        cambio_fecha:  'Cambio de fecha',
        cambio_estado: 'Cambio de estado',
    };

    function cargarNotificaciones() {
        $.getJSON(notifUrl, function (data) {
            const total = data.total || 0;

            if (total > 0) {
                $badge.text(total > 9 ? '9+' : total).removeClass('d-none');
            } else {
                $badge.addClass('d-none');
            }

            if (!data.notificaciones || data.notificaciones.length === 0) {
                $lista.html('<div class="text-center text-muted py-3 font-size-13">Sin notificaciones nuevas</div>');
                return;
            }

            let html = '';
            data.notificaciones.forEach(function (n) {
                const icono = iconos[n.tipo] || 'bx-info-circle text-secondary';
                const label = etiquetas[n.tipo] || n.tipo;
                const d = n.datos || {};

                html += `<a href="javascript:void(0)" class="dropdown-item notif-item border-bottom py-2 px-3" data-id="${n.id}">
                    <div class="d-flex align-items-start gap-2">
                        <div class="flex-shrink-0 pt-1"><i class="bx ${icono} font-size-18"></i></div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="fw-semibold font-size-13 text-truncate">${label}</div>
                            <div class="text-muted font-size-12 text-truncate">${d.nombre || ''} · ${d.fecha || ''} ${d.hora || ''}</div>
                            <div class="text-muted font-size-11">${n.created_at}</div>
                        </div>
                    </div>
                </a>`;
            });

            $lista.html(html);
        });
    }

    // Marcar individual como leída
    $(document).on('click', '.notif-item', function () {
        const id = $(this).data('id');
        $.ajax({
            url: leidaUrl + '/' + id + '/leida',
            method: 'POST',
            data: { _method: 'PATCH', _token: csrfToken },
            complete: function () { cargarNotificaciones(); }
        });
    });

    // Marcar todas como leídas
    $('#btn-marcar-todas-leidas').on('click', function (e) {
        e.stopPropagation();
        $.ajax({
            url: leidaUrl + '/todas-leidas',
            method: 'POST',
            data: { _method: 'PATCH', _token: csrfToken },
            complete: function () { cargarNotificaciones(); }
        });
    });

    // Cargar al abrir el dropdown
    $('#btn-notificaciones').on('click', function () {
        cargarNotificaciones();
    });

    // Polling cada 60 segundos
    cargarNotificaciones();
    setInterval(cargarNotificaciones, 60000);
})();
</script>
@endhasanyrole

@yield('script')

@stack('scripts')

<!-- App js -->

@yield('script-bottom')