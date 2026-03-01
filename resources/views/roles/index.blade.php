@extends('layouts.master')

@section('title')
    @lang('titulos.Roles')
@endsection

@section('content')
    <x-crud-datatable :config="$config">
        @include('roles.partials.modal')
    </x-crud-datatable>
@endsection
