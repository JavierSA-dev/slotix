/**
 * admin-dashboard.js
 * FullCalendar para la vista del panel de administración.
 */

import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import esLocale from '@fullcalendar/core/locales/es';

document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('admin-calendar');
    if (!calendarEl) return;

    const eventsUrl = calendarEl.dataset.eventsUrl;

    const calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, timeGridPlugin],
        locale: esLocale,
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek',
        },
        buttonText: {
            today: 'Hoy',
            month: 'Mes',
            week: 'Semana',
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
        eventClick: function (info) {
            const p = info.event.extendedProps;

            document.getElementById('modal-ev-nombre').textContent = info.event.title;
            document.getElementById('modal-ev-fecha').textContent = p.fecha_fmt || '';
            document.getElementById('modal-ev-hora').textContent = (p.hora_inicio_fmt || '') + ' – ' + (p.hora_fin_fmt || '');
            document.getElementById('modal-ev-personas').textContent = p.personas || '';
            document.getElementById('modal-ev-email').textContent = p.email || '';
            document.getElementById('modal-ev-telefono').textContent = p.telefono || '–';
            document.getElementById('modal-ev-notas').textContent = p.notas || '–';

            const estadoEl = document.getElementById('modal-ev-estado');
            estadoEl.textContent = p.estado ? p.estado.charAt(0).toUpperCase() + p.estado.slice(1) : '';
            estadoEl.className = 'pill-label pill-label-' + (p.estado === 'confirmada' ? 'primary' : 'warning');

            const linkVer = document.getElementById('modal-ev-link');
            if (p.token) {
                linkVer.href = '/reservas/' + p.token;
                linkVer.classList.remove('d-none');
            } else {
                linkVer.classList.add('d-none');
            }

            new bootstrap.Modal(document.getElementById('modal-evento-detalle')).show();
        },
    });

    calendar.render();
});
