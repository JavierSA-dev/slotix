/**
 * admin-dashboard.js
 * FullCalendar para la vista del panel de administración.
 */

import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import esLocale from '@fullcalendar/core/locales/es';

document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('admin-calendar');
    if (!calendarEl) return;

    const eventsUrl = calendarEl.dataset.eventsUrl;
    const reservasUrl = calendarEl.dataset.reservasUrl;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // ─── Estado drag & drop ──────────────────────────────────────
    let pendingDrop = null;

    // ─── Helpers de estado ───────────────────────────────────────
    const estadoClases = {
        pendiente: 'btn-warning',
        confirmada: 'btn-success',
        cancelada: 'btn-danger',
    };

    function setEstadoActivo(estado) {
        document.getElementById('modal-ev-estado').value = estado;
        document.querySelectorAll('.btn-estado-toggle').forEach(btn => {
            const s = btn.dataset.estado;
            btn.className = 'btn btn-sm btn-estado-toggle';
            if (s === estado) {
                btn.classList.add(estadoClases[s] || 'btn-secondary');
            } else {
                btn.classList.add('btn-outline-secondary');
            }
        });
    }

    function buildGCalUrl(nombre, fecha, horaInicio, horaFin, personas, token) {
        const d = fecha.replace(/-/g, '');
        const hi = horaInicio.replace(':', '');
        const hf = horaFin.replace(':', '');
        const title = encodeURIComponent('Reserva ' + nombre);
        const details = encodeURIComponent('Personas: ' + personas + ' | Ref: ' + token);
        return `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${title}&dates=${d}T${hi}00/${d}T${hf}00&details=${details}`;
    }

    function showAlert(elId, msg, type) {
        const el = document.getElementById(elId);
        el.className = 'alert alert-' + type;
        el.textContent = msg;
        el.classList.remove('d-none');
    }

    function hideAlert(elId) {
        document.getElementById(elId).classList.add('d-none');
    }

    // ─── Abrir modal con datos de la reserva ─────────────────────
    function abrirModalReserva(reservaId) {
        hideAlert('modal-ev-alert');

        fetch(reservasUrl + '/' + reservaId, {
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        })
            .then(r => r.json())
            .then(data => {
                document.getElementById('modal-ev-id').value = data.id;
                document.getElementById('modal-ev-titulo').textContent = data.nombre;
                document.getElementById('modal-ev-nombre').value = data.nombre;
                document.getElementById('modal-ev-email').value = data.email;
                document.getElementById('modal-ev-telefono').value = data.telefono || '';
                document.getElementById('modal-ev-personas').value = data.num_personas;
                document.getElementById('modal-ev-fecha').value = data.fecha;
                document.getElementById('modal-ev-hora-inicio').value = data.hora_inicio;
                document.getElementById('modal-ev-notas').value = data.notas || '';
                document.getElementById('modal-ev-notas-admin').value = data.notas_admin || '';
                setEstadoActivo(data.estado);

                const gcalBtn = document.getElementById('modal-ev-gcal');
                if (data.token) {
                    gcalBtn.href = buildGCalUrl(data.nombre, data.fecha, data.hora_inicio, data.hora_fin, data.num_personas, data.token);
                    gcalBtn.classList.remove('d-none');
                } else {
                    gcalBtn.classList.add('d-none');
                }

                new bootstrap.Modal(document.getElementById('modal-evento-detalle')).show();
            });
    }

    // ─── Guardar cambios del modal ────────────────────────────────
    document.getElementById('btn-guardar-reserva').addEventListener('click', function () {
        const id = document.getElementById('modal-ev-id').value;
        const btn = this;
        btn.disabled = true;
        hideAlert('modal-ev-alert');

        const body = new FormData(document.getElementById('form-reserva-calendario'));
        body.append('estado', document.getElementById('modal-ev-estado').value);
        body.append('_method', 'PATCH');

        fetch(reservasUrl + '/' + id, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: body,
        })
            .then(async r => {
                const json = await r.json();
                if (!r.ok) throw new Error(json.message || 'Error al guardar.');
                return json;
            })
            .then(json => {
                showAlert('modal-ev-alert', json.message, 'success');
                calendar.refetchEvents();
            })
            .catch(err => showAlert('modal-ev-alert', err.message, 'danger'))
            .finally(() => { btn.disabled = false; });
    });

    // ─── Botones de estado ────────────────────────────────────────
    document.querySelectorAll('.btn-estado-toggle').forEach(btn => {
        btn.addEventListener('click', () => setEstadoActivo(btn.dataset.estado));
    });

    // ─── Modal mover reserva (drag & drop) ───────────────────────
    document.getElementById('btn-mover-confirmar').addEventListener('click', function () {
        if (!pendingDrop) return;
        const { reservaId, nuevaFecha, nuevaHoraInicio, notasActuales } = pendingDrop;
        const notas = document.getElementById('mover-notas').value;
        const btn = this;
        btn.disabled = true;
        hideAlert('mover-alert');

        const body = new FormData();
        body.append('_method', 'PATCH');

        // Primero cargamos datos actuales de la reserva y actualizamos solo fecha/hora
        fetch(reservasUrl + '/' + reservaId, {
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        })
            .then(r => r.json())
            .then(data => {
                const payload = new FormData();
                payload.append('_method', 'PATCH');
                payload.append('nombre', data.nombre);
                payload.append('email', data.email);
                payload.append('telefono', data.telefono || '');
                payload.append('num_personas', data.num_personas);
                payload.append('estado', data.estado);
                payload.append('fecha', nuevaFecha);
                payload.append('hora_inicio', nuevaHoraInicio);
                payload.append('notas', data.notas || '');
                const notaAdmin = notas
                    ? (data.notas_admin ? data.notas_admin + '\n[Cambio de fecha] ' + notas : '[Cambio de fecha] ' + notas)
                    : (data.notas_admin || '');
                payload.append('notas_admin', notaAdmin);

                return fetch(reservasUrl + '/' + reservaId, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: payload,
                });
            })
            .then(async r => {
                const json = await r.json();
                if (!r.ok) throw new Error(json.message || 'Error al mover.');
                return json;
            })
            .then(() => {
                pendingDrop.revert = false;
                bootstrap.Modal.getInstance(document.getElementById('modal-confirmar-mover')).hide();
                calendar.refetchEvents();
            })
            .catch(err => {
                showAlert('mover-alert', err.message, 'danger');
                pendingDrop.revertFunc();
            })
            .finally(() => { btn.disabled = false; });
    });

    document.getElementById('btn-mover-cancelar').addEventListener('click', function () {
        if (pendingDrop) {
            pendingDrop.revertFunc();
            pendingDrop = null;
        }
        bootstrap.Modal.getInstance(document.getElementById('modal-confirmar-mover')).hide();
    });

    document.getElementById('modal-confirmar-mover').addEventListener('hidden.bs.modal', function () {
        if (pendingDrop && pendingDrop.revert !== false) {
            pendingDrop.revertFunc();
        }
        pendingDrop = null;
        document.getElementById('mover-notas').value = '';
        hideAlert('mover-alert');
    });

    // ─── Calendario ───────────────────────────────────────────────
    const calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        locale: esLocale,
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay',
        },
        buttonText: {
            today: 'Hoy',
            month: 'Mes',
            week: 'Semana',
            day: 'Día',
        },
        events: {
            url: eventsUrl,
            method: 'GET',
            failure: function () {
                console.error('Error al cargar eventos del calendario.');
            },
        },
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        },
        height: 'auto',
        dayMaxEvents: 4,
        editable: true,
        eventDurationEditable: false,

        eventClick: function (info) {
            abrirModalReserva(info.event.extendedProps.reserva_id || info.event.id);
        },

        eventDrop: function (info) {
            const p = info.event.extendedProps;
            const fechaOriginal = info.oldEvent.start;
            const fechaNueva = info.event.start;

            const fmt = d => d.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
            const fmtHora = d => d.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', hour12: false });
            const toIsoDate = d => d.toISOString().slice(0, 10);
            const toHora = d => fmtHora(d);

            pendingDrop = {
                reservaId: p.reserva_id || info.event.id,
                nuevaFecha: toIsoDate(fechaNueva),
                nuevaHoraInicio: p.hora_inicio_fmt,
                revertFunc: info.revert,
                revert: true,
            };

            document.getElementById('mover-nombre').textContent = info.event.title;
            document.getElementById('mover-fecha-original').textContent = fmt(fechaOriginal);
            document.getElementById('mover-hora-original').textContent = p.hora_inicio_fmt + ' – ' + p.hora_fin_fmt;
            document.getElementById('mover-fecha-nueva').textContent = fmt(fechaNueva);
            document.getElementById('mover-hora-nueva').textContent = p.hora_inicio_fmt + ' – ' + p.hora_fin_fmt;

            new bootstrap.Modal(document.getElementById('modal-confirmar-mover')).show();
        },
    });

    calendar.render();
});
