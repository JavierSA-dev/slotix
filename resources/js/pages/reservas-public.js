/**
 * reservas-public.js
 * Lógica de la vista pública de reservas del Minigolf Córdoba.
 */

$(function () {
    // ─── ESTADO ────────────────────────────────────────────────
    let fechaSeleccionada = null;
    let horaInicioSeleccionada = null;

    const $franjasWrapper = $('#franjas-wrapper');
    const $fechaSelector = $('#fecha-selector');

    // ─── HELPERS ───────────────────────────────────────────────

    /**
     * Convierte decimal a string HH:MM para mostrar al usuario.
     * 10.5 → "10:30"
     */
    function decimalAHora(decimal) {
        const h = Math.floor(decimal);
        const m = Math.round((decimal - h) * 60);
        return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
    }

    /**
     * Convierte fecha YYYY-MM-DD a formato dd/mm/YYYY para mostrar.
     */
    function formatearFecha(fechaStr) {
        const [y, m, d] = fechaStr.split('-');
        return `${d}/${m}/${y}`;
    }

    // ─── SELECCIÓN DE FECHA ────────────────────────────────────

    $fechaSelector.on('click', '.fecha-pill', function () {
        $fechaSelector.find('.fecha-pill').removeClass('active');
        $(this).addClass('active');

        fechaSeleccionada = $(this).data('fecha');
        cargarFranjas(fechaSeleccionada);
    });

    // Auto-click en la primera fecha al cargar
    const $primeraPill = $fechaSelector.find('.fecha-pill').first();
    if ($primeraPill.length) {
        $primeraPill.trigger('click');
    }

    // ─── CARGA DE FRANJAS (AJAX) ───────────────────────────────

    function cargarFranjas(fecha) {
        $franjasWrapper.html('<div class="mg-loading"><div class="spinner-border" role="status"></div></div>');

        $.ajax({
            url: '/reservas/franjas',
            method: 'GET',
            data: { fecha: fecha },
            success: function (franjas) {
                renderFranjas(franjas);
            },
            error: function () {
                $franjasWrapper.html('<div class="mg-empty-state"><p class="text-danger">Error al cargar los horarios. Inténtalo de nuevo.</p></div>');
            }
        });
    }

    function renderFranjas(franjas) {
        if (!franjas || franjas.length === 0) {
            $franjasWrapper.html('<div class="mg-empty-state"><div class="mg-empty-icon">🚫</div><p>No hay franjas disponibles para este día.</p></div>');
            return;
        }

        let html = '<div class="franjas-grid">';

        franjas.forEach(function (franja) {
            const llena = !franja.disponible;
            const libre = franja.aforo - franja.reservadas;
            const claseLlena = llena ? ' llena' : '';
            const attrDisabled = llena ? '' : '';

            html += `
                <div class="franja-card${claseLlena}"
                     ${!llena ? `data-hora="${franja.hora_inicio}"` : ''}>
                    <div class="franja-hora">${franja.hora_inicio_fmt}</div>
                    <div class="franja-aforo">${llena ? 'Sin plazas' : libre + ' plaza' + (libre !== 1 ? 's' : '')}</div>
                </div>`;
        });

        html += '</div>';
        $franjasWrapper.html(html);
    }

    // ─── CLICK EN FRANJA DISPONIBLE ────────────────────────────

    $franjasWrapper.on('click', '.franja-card:not(.llena)', function () {
        horaInicioSeleccionada = parseFloat($(this).data('hora'));

        // Rellenar hidden inputs del modal
        $('#reserva-fecha').val(fechaSeleccionada);
        $('#reserva-hora-inicio').val(horaInicioSeleccionada);

        // Actualizar display informativo del modal
        const horaStr = decimalAHora(horaInicioSeleccionada);
        const fechaFmt = formatearFecha(fechaSeleccionada);
        $('#reserva-franja-display').text(horaStr);
        $('#reserva-fecha-display').text(fechaFmt);

        // Resetear estado del modal
        resetModal();

        // Abrir modal
        const modal = new bootstrap.Modal(document.getElementById('modal-reserva'));
        modal.show();
    });

    // ─── SUBMIT DEL FORMULARIO DE RESERVA ──────────────────────

    $('#btn-confirmar-reserva').on('click', function () {
        enviarReserva();
    });

    function enviarReserva() {
        const $btn = $('#btn-confirmar-reserva');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Confirmando...');
        $('#form-reserva-error').text('');
        $('[data-field-error]').text('');

        const formData = $('#form-reserva').serialize();

        $.ajax({
            url: '/reservas',
            method: 'POST',
            data: formData,
            success: function (response) {
                // Mostrar éxito
                $('#form-reserva').addClass('d-none');
                $('#modal-reserva-footer').addClass('d-none');
                $('#reserva-success').removeClass('d-none');
                $('#reserva-gestion-link').attr('href', response.url);

                // Refrescar franjas para mostrar la disponibilidad actualizada
                if (fechaSeleccionada) {
                    cargarFranjas(fechaSeleccionada);
                }
            },
            error: function (xhr) {
                $btn.prop('disabled', false).text('Confirmar reserva');

                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors || {};

                    // Mostrar errores de campo
                    Object.keys(errors).forEach(function (campo) {
                        const $el = $('[data-field-error="' + campo + '"]');
                        if ($el.length) {
                            $el.text(errors[campo][0]);
                        }
                    });

                    // Mensaje genérico si no hay errores de campo
                    const mensaje = xhr.responseJSON?.message || '';
                    if (mensaje && !Object.keys(errors).length) {
                        $('#form-reserva-error').text(mensaje);
                    }
                } else {
                    $('#form-reserva-error').text('Ha ocurrido un error. Inténtalo de nuevo.');
                }
            }
        });
    }

    // ─── RESETEAR MODAL ────────────────────────────────────────

    function resetModal() {
        $('#form-reserva').removeClass('d-none')[0].reset();
        $('#form-reserva-error').text('');
        $('[data-field-error]').text('');
        $('#reserva-success').addClass('d-none');
        $('#modal-reserva-footer').removeClass('d-none');
        $('#btn-confirmar-reserva').prop('disabled', false).text('Confirmar reserva');

        // Restaurar hidden inputs (el reset los borra)
        $('#reserva-fecha').val(fechaSeleccionada);
        $('#reserva-hora-inicio').val(horaInicioSeleccionada);
    }

    // Resetear también cuando se cierra el modal
    document.getElementById('modal-reserva')?.addEventListener('hidden.bs.modal', function () {
        resetModal();
    });
});
