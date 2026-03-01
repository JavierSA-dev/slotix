@extends('layouts.master-without-nav')

@section('title')
    Confirm Password
@endsection

@section('css')
    <!-- owl.carousel css -->
    <link rel="stylesheet" href="{{ URL::asset('build/libs/owl.carousel/assets/owl.carousel.min.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('build/libs/owl.carousel/assets/owl.theme.default.min.css') }}">
@endsection

@section('body')

    <body class="auth-body-bg">
    @endsection

    @section('content')

        <div>
            <div class="container-fluid p-0">
                <div class="row g-0">

                    <div class="col-xl-9">
                        <div class="auth-full-bg pt-lg-5 p-4">
                            <div class="w-100">
                                <div class="bg-overlay"></div>
                                
                            </div>
                        </div>
                    </div>
                    <!-- end col -->

                    <div class="col-xl-3">
                        <div class="auth-full-page-content p-md-5 p-4">
                            <div class="w-100">

                                <div class="d-flex flex-column h-100">
                                    <div class="mb-4 mb-md-5">
                                        <a href="/index" class="d-block auth-logo">
                                            <img src="{{ URL::asset('build/images/logo-dark.png') }}" alt="" height="18"
                                                class="auth-logo-dark">
                                            <img src="{{ URL::asset('build/images/logo-light.png') }}" alt="" height="18"
                                                class="auth-logo-light">
                                        </a>
                                    </div>
                                    <div class="my-auto">

                                        <div>
                                            <h5 class="text-primary"> Confirmar Contraseña</h5>
                                            <p class="text-muted">Vamos a confirmar tu contraseña</p>
                                        </div>

                                        <div class="mt-4">
                                            <form class="form-horizontal" method="POST"
                                                action="{{ route('password.confirm') }}">
                                                @csrf

                                                <div class="mb-3">
                                                    <label for="userpassword">Contraseña <span class="text-danger">*</span></label>
                                                    <input type="password"
                                                        class="form-control @error('password') is-invalid @enderror"
                                                        name="password" id="userpassword" placeholder="Introduce contraseña">
                                                    @error('password')
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                    @enderror
                                                </div>

                                                <div class="text-end">
                                                    <button class="btn btn-primary w-md waves-effect waves-light"
                                                        type="submit">Confirmar Contraseña</button>
                                                </div>

                                            </form>
                                            <div class="mt-5 text-center">
                                                <p>¿La has recordado? <a href="{{ url('login') }}"
                                                        class="font-weight-medium text-primary"> Valídate aquí</a> </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-4 mt-md-5 text-center">
                                        <p class="mb-0">© <script>
                                                document.write(new Date().getFullYear())

                                            </script> Skote. Crafted with <i class="mdi mdi-heart text-danger"></i> by
                                            Themesbrand</p>
                                    </div>
                                </div>


                            </div>
                        </div>
                    </div>
                    <!-- end col -->
                </div>
                <!-- end row -->
            </div>
            <!-- end container-fluid -->
        </div>

    @endsection

    @section('script')
        <!-- owl.carousel js -->
        <script src="{{ URL::asset('build/libs/owl.carousel/owl.carousel.min.js') }}"></script>
        <!-- auth-2-carousel init -->
        <script src="{{ URL::asset('build/js/pages/auth-2-carousel.init.js') }}"></script>
    @endsection
