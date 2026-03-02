<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PerfilController extends Controller
{
    public function show(): View
    {
        return view('perfil.index', ['user' => auth()->user()]);
    }
}
