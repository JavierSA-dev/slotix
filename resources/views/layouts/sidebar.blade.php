<!-- ========== Left Sidebar Start ========== -->
<div class="vertical-menu">

    <div data-simplebar class="h-100">

        <!--- Sidemenu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title" key="t-menu">@lang('translation.Menu')</li>

                <li>
                    <a href="/" class="waves-effect">
                        <i class="bx bx-home-circle"></i>
                        <span key="t-dashboards">@lang('translation.Dashboards')</span>
                    </a>
                </li>

                <li>
                    <a href="https://themesbrand.com/skote/layouts/index.html" target="_blank" class="waves-effect">
                    <span class="badge rounded-pill bg-danger float-end" key="t-hot">@lang('translation.hot')</span>
                        <i class="bx bx-layout"></i>
                        <span key="t-layouts">@lang('translation.Layouts')</span>
                    </a>                    
                </li>

                @can('viewAny', App\Models\User::class)
                <li class="menu-title" key="t-pages">@lang('translation.Administration')</li>

                <li>
                    <a href="/users" class="waves-effect">
                        <i class="bx bx-user-circle"></i>
                        <span key="t-authentication">@lang('translation.Users')</span>
                    </a>
                </li>
                @endcan

                @role('SuperAdmin')
                <li class="menu-title" key="t-pages">@lang('translation.Configuracion')</li>

                <li>
                    <a href="/roles" class="waves-effect">
                        <i class="bx bx-user-circle"></i>
                        <span key="t-authentication">@lang('translation.Roles')</span>
                    </a>                    
                </li>
                <li>
                    <a href="/permissions" class="waves-effect">
                        <i class="bx bx-user-circle"></i>
                        <span key="t-authentication">@lang('translation.Permisos')</span>
                    </a>
                    
                </li>

                <li>
                    <a href="javascript: void(0);" class="has-arrow waves-effect">
                        <i class="bx bx-share-alt"></i>
                        <span key="t-multi-level">@lang('translation.Multi_Level')</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="true">
                        <li><a href="javascript: void(0);" key="t-level-1-1">@lang('translation.Level_1.1')</a></li>
                        <li>
                            <a href="javascript: void(0);" class="has-arrow"
                                key="t-level-1-2">@lang('translation.Level_1.2')</a>
                            <ul class="sub-menu" aria-expanded="true">
                                <li><a href="javascript: void(0);" key="t-level-2-1">@lang('translation.Level_2.1')</a>
                                </li>
                                <li><a href="javascript: void(0);" key="t-level-2-2">@lang('translation.Level_2.2')</a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </li>
                @endrole

            </ul>
        </div>
        <!-- Sidebar -->
    </div>
</div>
<!-- Left Sidebar End -->
