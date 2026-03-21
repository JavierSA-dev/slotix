<?php

namespace App\Http\Controllers;

use App\Traits\ResuelveTemaCss;
use Illuminate\View\View;

class PerfilController extends Controller
{
    use ResuelveTemaCss;

    public function show(): View
    {
        $temaCss = $this->resolverTemaCss();

        return view('perfil.index', ['user' => auth()->user(), 'temaCss' => $temaCss]);
    }
}
