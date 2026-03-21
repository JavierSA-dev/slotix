<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <title>@yield('title', 'Panel') | {{ isset($empresaMaster) && $empresaMaster ? $empresaMaster->nombre : 'Panel' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Panel de administración · {{ config('app.name') }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- App favicon -->
    <base href="{{ url('/') }}/">
    <link rel="shortcut icon" href="{{ URL::asset('build/images/favicon.ico') }}">
    @stack('style')
    @include('layouts.head-css')
    @if(isset($temaCss) && $temaCss)
    <style>{!! $temaCss !!}</style>
    @endif
</head>

@section('body')
    <body data-sidebar="dark" data-layout-mode="light">
@show
    <!-- Begin page -->
    <div id="layout-wrapper">
        @include('layouts.topbar')
        @include('layouts.sidebar')
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    @yield('content')
                </div>
                <!-- container-fluid -->
            </div>
            <!-- End Page-content -->
            @include('layouts.footer')
        </div>
        <!-- end main content-->
    </div>
    <!-- END layout-wrapper -->

    <!-- Right Sidebar -->
    {{-- @include('layouts.right-sidebar') --}}
    <!-- /Right-bar -->

    <!-- JAVASCRIPT -->
    @include('layouts.vendor-scripts')
</body>

</html>
