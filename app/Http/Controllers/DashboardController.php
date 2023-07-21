<?php

namespace App\Http\Controllers;

use App\Models\AlunoAdmissao;
use App\Models\AnoLectivo;
use App\Models\Bolseiro;
use App\Models\Deposito;
use App\Models\Factura;
use App\Models\GradeCurricularAluno;
use App\Models\GrupoAcesso;
use App\Models\GrupoUtilizador;
use App\Models\LoginAcesso;
use App\Models\Mes;
use App\Models\MesTemp;
use App\Models\Pagamento;
use App\Models\TipoServico;
use App\Models\Turno;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function dashboard(Request $request)
    {
        $user = auth()->user();
        
        if($user->tipo_grupo->grupo->designacao == "Administrador"){
            
            $valor_deposito = Deposito::sum('valor_depositar');
            $totalPagamentos = Pagamento::where('estado', 1)->sum('valor_depositado');
            
        }else {
        
            $valor_deposito = Deposito::where('created_by', $user->codigo_importado)->sum('valor_depositar');
            $totalPagamentos = Pagamento::where('estado', 1)->where('fk_utilizador', $user->codigo_importado)->sum('valor_depositado');
        
        }
        
        
        $header = [
            "total_depositado" => $valor_deposito,
            'total_pagamento' => $totalPagamentos
        ];
        
        return Inertia::render('Dashboard', $header);
    }

}
