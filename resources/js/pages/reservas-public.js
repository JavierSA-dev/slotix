/**
 * reservas-public.js
 * Lógica de la vista pública de reservas del Minigolf Córdoba.
 */

$(function () {
    // ─── ESTADO ────────────────────────────────────────────────
    let fechaSeleccionada = null;
    let horaInicioSeleccionada = null;

    const $franjasWrapper = $('#franjas-wrapper');

    // ─── HELPERS ───────────────────────────────────────────────

    function decimalAHora(decimal) {
        const h = Math.floor(decimal);
        const m = Math.round((decimal - h) * 60);
        return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
    }

    function formatearFecha(fechaStr) {
        const [y, m, d] = fechaStr.split('-');
        return `${d}/${m}/${y}`;
    }

    // ─── CHIPS DE FECHAS ───────────────────────────────────────

    const fechas = window.mgFechas || [];
    const $tira = $('#mg-fechas');

    if ($tira.length && fechas.length) {
        fechas.forEach(function (f) {
            const partes = f.etiqueta.split(' ');
            const chip = $(`
                <button type="button" class="mg-fecha-chip" data-fecha="${f.valor}">
                    <div class="chip-dia">${partes[0]}</div>
                    <div class="chip-fecha">${partes[1] || ''}</div>
                </button>
            `);
            $tira.append(chip);
        });

        // Seleccionar la primera fecha automáticamente
        const $primer = $tira.find('.mg-fecha-chip').first();
        if ($primer.length) {
            seleccionarFecha($primer);
        }
    }

    function seleccionarFecha($chip) {
        $tira.find('.mg-fecha-chip').removeClass('activa');
        $chip.addClass('activa');
        fechaSeleccionada = $chip.data('fecha');
        cargarFranjas(fechaSeleccionada);
    }

    $tira.on('click', '.mg-fecha-chip', function () {
        seleccionarFecha($(this));
    });

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
            html += `
                <div class="franja-card${llena ? ' llena' : ''}"
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

        $('#reserva-fecha').val(fechaSeleccionada);
        $('#reserva-hora-inicio').val(horaInicioSeleccionada);
        $('#reserva-franja-display').text(decimalAHora(horaInicioSeleccionada));
        $('#reserva-fecha-display').text(formatearFecha(fechaSeleccionada));

        resetModal();
        new bootstrap.Modal(document.getElementById('modal-reserva')).show();
    });

    // ─── SUBMIT DEL FORMULARIO DE RESERVA ──────────────────────

    $('#btn-confirmar-reserva').on('click', function () {
        enviarReserva();
    });

    function enviarReserva() {
        const $btn = $('#btn-confirmar-reserva');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Enviando...');
        $('#form-reserva-error').text('');
        $('[data-field-error]').text('');

        $.ajax({
            url: '/reservas',
            method: 'POST',
            data: $('#form-reserva').serialize(),
            success: function (response) {
                $('#form-reserva').addClass('d-none');
                $('#modal-reserva-footer').addClass('d-none');
                $('#reserva-success').removeClass('d-none');
                $('#reserva-gestion-link').attr('href', response.url);
                if (response.google_calendar_url) {
                    $('#btn-gcal-modal').attr('href', response.google_calendar_url).removeClass('d-none');
                }
                if (fechaSeleccionada) { cargarFranjas(fechaSeleccionada); }
            },
            error: function (xhr) {
                $btn.prop('disabled', false).text('Confirmar reserva');
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors || {};
                    Object.keys(errors).forEach(function (campo) {
                        const $el = $('[data-field-error="' + campo + '"]');
                        if ($el.length) { $el.text(errors[campo][0]); }
                    });
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
        $('#btn-gcal-modal').addClass('d-none').attr('href', '#');
        $('#reserva-fecha').val(fechaSeleccionada);
        $('#reserva-hora-inicio').val(horaInicioSeleccionada);
    }

    document.getElementById('modal-reserva')?.addEventListener('hidden.bs.modal', resetModal);
});
