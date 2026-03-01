@extends('layouts.master')

@section('title')
    @lang('titulos.Usuarios')
@endsection

@section('content')
    <x-crud-datatable :config="$config">
        @include('users.partials.modal')
    </x-crud-datatable>
@endsection