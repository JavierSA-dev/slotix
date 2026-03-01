@extends('layouts.master')

@section('title', 'Reservas')

@section('content')
    <x-crud-datatable :config="$config">
        {{-- Las reservas las crean los clientes, no hay modal de creación --}}
    </x-crud-datatable>
@endsection
