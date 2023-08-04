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

    use TraitHelpers;
    
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function dashboard(Request $request)
    {
        $user = auth()->user();
        
        $request->ano_lectivo = $this->anoLectivoActivo();
        
        if($user->tipo_grupo->grupo->designacao == "Administrador"){
            
            $valor_deposito = Deposito::when($request->ano_lectivo, function($query, $value){
                $query->where("ano_lectivo_id" ,$value);
            })->when(!$request->data_inicio, function($query, $value){
                $query->where("data_movimento", "=", date("Y-m-d"));
            })->when($request->data_inicio, function($query, $value){
                $query->where("data_movimento", ">=",Carbon::parse($value));
            })->when($request->data_final, function($query, $value){
                $query->where("data_movimento", "<=",Carbon::parse($value));
            })
            ->where('data_movimento', '=', Carbon::parse(date('Y-m-d')))
            ->sum('valor_depositar');
            
            $totalPagamentos = Pagamento::when($request->ano_lectivo, function($query, $value){
                $query->where("AnoLectivo" ,$value);
            })->when(!$request->data_inicio, function($query, $value){
                $query->where("DataRegisto", "=",date('Y-m-d'));
            })->when($request->data_inicio, function($query, $value){
                $query->where("DataRegisto", ">=",Carbon::parse($value));
            })->when($request->data_final, function($query, $value){
                $query->where("DataRegisto", "<=",Carbon::parse($value));
            })
            ->where('estado', 1)
            ->where('forma_pagamento', 6)
            ->sum('valor_depositado');
            
        }else {
        
            $valor_deposito = Deposito::when($request->ano_lectivo, function($query, $value){
                $query->where("ano_lectivo_id" ,$value);
            })->when(!$request->data_inicio, function($query, $value){
                $query->where("data_movimento", "=", date("Y-m-d"));
            })->when($request->data_inicio, function($query, $value){
                $query->where("data_movimento", ">=",Carbon::parse($value));
            })->when($request->data_final, function($query, $value){
                $query->where("data_movimento", "<=",Carbon::parse($value));
            })
            ->where('created_by', $user->codigo_importado)
            ->sum('valor_depositar');
            
            $totalPagamentos = Pagamento::when($request->ano_lectivo, function($query, $value){
                $query->where("AnoLectivo" ,$value);
            })->when(!$request->data_inicio, function($query, $value){
                $query->where("DataRegisto", "=",date('Y-m-d'));
            })->when($request->data_inicio, function($query, $value){
                $query->where("DataRegisto", ">=",Carbon::parse($value));
            })->when($request->data_final, function($query, $value){
                $query->where("DataRegisto", "<=",Carbon::parse($value));
            })
            ->where('estado', 1)
            ->where('forma_pagamento', 6)
            ->where('fk_utilizador', $user->codigo_importado)
            ->sum('valor_depositado');
        
        }
        
        $header = [
            "total_depositado" => $valor_deposito,
            'total_pagamento' => $totalPagamentos,
            'ano_lectivo_activo_id' => $this->anoLectivoActivo(),
            
            "ano_lectivos" => AnoLectivo::where('status', '1')->get(),
            
        ];
        
        return Inertia::render('Dashboard', $header);
    }

}
