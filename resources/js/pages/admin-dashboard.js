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
    });

    calendar.render();
});
