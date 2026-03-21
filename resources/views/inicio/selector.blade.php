@extends('layouts.public')

@section('title', 'Mis empresas | Slotix')

@section('content')
<div class="mg-container py-5">

    <div class="mg-reserva-titulo mb-4">
        <i class="bx bx-buildings me-2"></i>¿A qué empresa quieres acceder?
    </div>

    <div class="row g-3">
        @foreach($empresas as $empresa)
        <div class="col-12 col-sm-6 col-md-4">
            <form method="POST" action="{{ route('inicio.seleccionar') }}">
                @csrf
                <input type="hidden" name="empresa_id" value="{{ $empresa->id }}">
                <button type="submit" class="w-100 border-0 bg-transparent p-0 text-start">
                    <div class="mg-reserva-card h-100 d-flex align-items-center gap-3" style="cursor:pointer;">
                        @if($empresa->logo)
                            <img src="{{ asset('storage/' . $empresa->logo) }}" alt="{{ $empresa->nombre }}"
                                 style="width:48px; height:48px; object-fit:contain; border-radius:8px; flex-shrink:0;">
                        @else
                            <div style="width:48px; height:48px; border-radius:8px; background:var(--mg-dark-3);
                                        display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                <i class="bx bx-building" style="font-size:1.5rem; color:var(--mg-gold);"></i>
                            </div>
                        @endif
                        <div>
                            <div style="font-weight:600; color:var(--mg-text); font-size:1rem;">{{ $empresa->nombre }}</div>
                            @if($empresa->descripcion)
                                <div style="font-size:.8rem; color:var(--mg-text-muted); margin-top:2px;">{{ $empresa->descripcion }}</div>
                            @endif
                        </div>
                        <i class="bx bx-chevron-right ms-auto" style="color:var(--mg-text-muted); font-size:1.3rem;"></i>
                    </div>
                </button>
            </form>
        </div>
        @endforeach
    </div>

    <div class="text-center mt-4">
        <a href="{{ route('mis-reservas.index') }}" style="font-size:.85rem; color:var(--mg-text-muted); text-decoration:none;">
            <i class="bx bx-calendar me-1"></i>Ver todas mis reservas
        </a>
    </div>

</div>
@endsection
