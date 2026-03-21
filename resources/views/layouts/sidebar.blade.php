<!-- ========== Left Sidebar Start ========== -->
<div class="vertical-menu">
    <div data-simplebar class="h-100">
        <div id="sidebar-menu">
            <ul class="metismenu list-unstyled" id="side-menu">

                {{-- PANEL ADMIN --}}
                @hasanyrole('SuperAdmin|Admin')
                <li class="menu-title">Gestión</li>

                <li>
                    <a href="/admin" class="waves-effect">
                        <i class="bx bx-home-circle"></i>
                        <span>Panel principal</span>
                    </a>
                </li>

                @if(isset($modulosActivos) && $modulosActivos->contains('reservas'))
                <li>
                    <a href="/admin/reservas" class="waves-effect">
                        <i class="bx bx-calendar-check"></i>
                        <span>Reservas</span>
                    </a>
                </li>

                <li>
                    <a href="/admin/horario" class="waves-effect">
                        <i class="bx bx-time-five"></i>
                        <span>Horario y aforo</span>
                    </a>
                </li>
                @endif

                @role('Admin')
                <li>
                    <a href="{{ route('admin.mi-empresa.index') }}" class="waves-effect">
                        <i class="bx bx-buildings"></i>
                        <span>Mi empresa</span>
                    </a>
                </li>
                @endrole
                @endhasanyrole

                {{-- USUARIO AUTENTICADO (solo usuarios sin rol admin) --}}
                @auth
                @unlessrole('SuperAdmin|Admin')
                <li class="menu-title">Mi cuenta</li>

                <li>
                    <a href="/mis-reservas" class="waves-effect">
                        <i class="bx bx-list-ul"></i>
                        <span>Mis reservas</span>
                    </a>
                </li>
                @endunlessrole
                @endauth

                {{-- ADMINISTRACIÓN (solo SuperAdmin) --}}
                @role('SuperAdmin')
                <li class="menu-title">Administración</li>

                <li>
                    <a href="/admin/empresas" class="waves-effect">
                        <i class="bx bx-buildings"></i>
                        <span>Empresas</span>
                    </a>
                </li>

                <li>
                    <a href="{{ route('admin.demos.index') }}" class="waves-effect">
                        <i class="bx bx-play-circle"></i>
                        <span>Demos</span>
                    </a>
                </li>

                <li>
                    <a href="/users" class="waves-effect">
                        <i class="bx bx-user-circle"></i>
                        <span>Usuarios</span>
                    </a>
                </li>

                <li>
                    <a href="/roles" class="waves-effect">
                        <i class="bx bx-shield-quarter"></i>
                        <span>Roles</span>
                    </a>
                </li>

                <li>
                    <a href="/permissions" class="waves-effect">
                        <i class="bx bx-key"></i>
                        <span>Permisos</span>
                    </a>
                </li>
                @endrole

            </ul>
        </div>
    </div>
</div>
<!-- Left Sidebar End -->
