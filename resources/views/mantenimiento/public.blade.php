@extends('layouts.public')

@section('title', 'En mantenimiento')

@section('content')
<div class="d-flex flex-column align-items-center justify-content-center text-center py-5" style="min-height: 60vh;">
    <i class="bx bx-wrench mb-4" style="font-size: 4rem; color: var(--mg-gold);"></i>
    <h2 class="mb-2" style="color: var(--mg-text);">Estamos haciendo mejoras</h2>
    <p class="mb-0" style="color: var(--mg-text-muted); max-width: 420px;">
        La web de reservas está temporalmente en mantenimiento.<br>
        Por favor, inténtalo de nuevo en unos minutos.
    </p>
</div>
@endsection
