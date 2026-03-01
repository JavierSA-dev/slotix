/**
 * reservas-public.js
 * Lógica de la vista pública de reservas del Minigolf Córdoba.
 * Usa Flatpickr para la selección de fecha.
 */

import flatpickr from 'flatpickr';
import { Spanish } from 'flatpickr/dist/l10n/es.js';

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

    // ─── FLATPICKR ─────────────────────────────────────────────

    const calendarEl = document.getElementById('mg-calendar');
    if (!calendarEl) return;

    // dias_semana PHP: 0=Lun, 1=Mar, ..., 6=Dom
    // JS getDay():     0=Dom, 1=Lun, ..., 6=Sab
    // Conversión: phpDay = jsDay === 0 ? 6 : jsDay - 1
    const diasHabiles = window.mgDiasHabiles || [0, 1, 2, 3, 4, 5, 6];

    const fp = flatpickr(calendarEl, {
        locale: Spanish,
        inline: true,
        minDate: 'today',
        dateFormat: 'Y-m-d',
        disable: [
            function (date) {
                const jsDay = date.getDay();
                const phpDay = jsDay === 0 ? 6 : jsDay - 1;
                return !diasHabiles.includes(phpDay);
            }
        ],
        onChange: function (selectedDates, dateStr) {
            if (dateStr) {
                fechaSeleccionada = dateStr;
                cargarFranjas(dateStr);
            }
        }
    });

    // Auto-seleccionar hoy o el primer día hábil disponible
    let diaInicial = new Date();
    diaInicial.setHours(0, 0, 0, 0);
    for (let i = 0; i < 60; i++) {
        const jsDay = diaInicial.getDay();
        const phpDay = jsDay === 0 ? 6 : jsDay - 1;
        if (diasHabiles.includes(phpDay)) {
            fp.setDate(diaInicial, true);
            break;
        }
        diaInicial.setDate(diaInicial.getDate() + 1);
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
                if (fechaSeleccionada) { cargarFranjas(fechaSeleccionada); }
            },
            error: function (xhr) {
                $btn.prop('disabled', false).text('Enviar solicitud');
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
        $('#btn-confirmar-reserva').prop('disabled', false).text('Enviar solicitud');
        $('#reserva-fecha').val(fechaSeleccionada);
        $('#reserva-hora-inicio').val(horaInicioSeleccionada);
    }

    document.getElementById('modal-reserva')?.addEventListener('hidden.bs.modal', resetModal);
});
