- En el modal de reservas del admin poder adminsitrar las reservas, cambiar estado, datos, mover reservar, crear nuevas, añadir a google calendar
- Que el admin no se vaya nunca a la vista de ver reserva que es la del usuario, siempre se abre un modal con lo datos cargados
- Cada vez que hay una reserva nueva, se cambia la fecha o se cancela se avisa a los usuarios con rol admin/superadmin y al usuario de la reserva afectada, esto tiene que ser email que se hagan siempre a lo mejor usar ReservaService
- Crear una campanita para el admin en la barra de arriba que le llegue si hay una nueva reserva, se cancela o se cambia la fecha de una reserva, etc. (notificaciones en tiempo real con Laravel Echo y Pusher), para que ya no salgan pendientes hay que marcar la notificación como vista
- Arreglar el modal de admin  en vista semana que no se ve nada bien, añadir vista de dia
- En el modal de admin las reservas cambia de color dependiendo del estado
- Ahora mismo al hacer click en una reserva se abre un modal solo para visualizar los datos, modificar ese para que sirvan para editar datos, cambiar estado, mover reserva, etc. y que se vayan guardando los cambios sin salir del modal
- Desde el fullcalendar del admin poder cambiar las fechas de las reservas arrastrándolas, al hacer esto se abre un modal para confirmar el cambio de fecha y que se puedan añadir notas al cambio, etc. y que se guarde el cambio y si notificar al cliete


quiero que haya temas en la aplicación digamos que el que usa ahora mismo el minigolf es el tema Neón que se ve en la imagen, quiero que haya tema clásico y tema pastel y cada empresa tendrá un tema que podra cambiar el mismo o el superadmin al dar de alta