@extends('layouts.master')

@section('title')
    @lang('titulos.Permisos')
@endsection

@section('content')
    <x-crud-datatable :config="$config">
        @include('permissions.partials.modal')
    </x-crud-datatable>
@endsection
