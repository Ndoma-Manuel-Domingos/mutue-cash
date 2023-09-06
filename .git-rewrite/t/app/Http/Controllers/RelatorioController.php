<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class RelatorioController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function fechoCaixaOperador(Request $request)
    {
        $user = auth()->user();
        
        $data['items'] = "";

        return Inertia::render('Relatorios/FechoCaixa/Operador', $data);
    }

}
