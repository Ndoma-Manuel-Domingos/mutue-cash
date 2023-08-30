<?php

namespace App\Http\Controllers;

use App\Models\AnoLectivo;
use App\Models\Caixa;
use App\Models\Deposito;
use App\Models\Pagamento;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

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
        
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
        
        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect()->route('mc.bloquear-caixa');
        }

        
        if(auth()->user()->hasRole(['Gestor de Caixa'])){
            $valor_deposito = Deposito::when($request->ano_lectivo, function($query, $value){
                $query->where("ano_lectivo_id" ,$value);
            })
            ->when($request->data_inicio, function($query, $value){
                $query->where("data_movimento", ">=",Carbon::parse($value));
            })->when($request->data_final, function($query, $value){
                $query->where("data_movimento", "<=",Carbon::parse($value));
            })
            ->where('data_movimento', '=', Carbon::parse(date('Y-m-d')))
            ->sum('valor_depositar');
            
            $totalPagamentos = Pagamento::when($request->ano_lectivo, function($query, $value){
                $query->where("AnoLectivo" ,$value);
            })
            ->when($request->data_inicio, function($query, $value){
                $query->where("DataRegisto", ">=",Carbon::parse($value));
            })->when($request->data_final, function($query, $value){
                $query->where("DataRegisto", "<=",Carbon::parse($value));
            })
            ->where('estado', 1)
            ->where('forma_pagamento', 6)
            ->sum('valor_depositado');
        }
        
        if(auth()->user()->hasRole(['Operador Caixa', 'Supervisor']))
        {
            if($request->data_inicio){
                $request->data_inicio = $request->data_inicio;
            }else{
                $request->data_inicio = date("Y-m-d");
            }
        
            $valor_deposito = Deposito::when($request->ano_lectivo, function($query, $value){
                $query->where("ano_lectivo_id" ,$value);
            })
            ->when($request->data_inicio, function($query, $value){
                $query->where("data_movimento", ">=",Carbon::parse($value));
            })->when($request->data_final, function($query, $value){
                $query->where("data_movimento", "<=",Carbon::parse($value));
            })
            ->where('created_by', $user->codigo_importado)
            ->sum('valor_depositar');
            
            $totalPagamentos = Pagamento::when($request->ano_lectivo, function($query, $value){
                $query->where("AnoLectivo" ,$value);
            })
            ->when($request->data_inicio, function($query, $value){
                $query->where("DataRegisto", ">=",Carbon::parse($value));
            })->when($request->data_final, function($query, $value){
                $query->where("DataRegisto", "<=",Carbon::parse($value));
            })
            ->where('estado', 1)
            ->where('forma_pagamento', 6)
            ->where('fk_utilizador', $user->codigo_importado)
            ->sum('valor_depositado');  
        }
            
        
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
        
        try {
            $header = [
                "total_depositado" => $valor_deposito,
                'total_pagamento' => $totalPagamentos,
                'caixa' => $caixa,
                'ano_lectivo_activo_id' => $this->anoLectivoActivo(),
                
                "ano_lectivos" => AnoLectivo::where('status', '1')->get(),
                
                "ano_lectivos" => $user->roles()->get(),
                "usuario" => $user
            ];
            //code...
        } catch (\Throwable $th) {
            $header = [
                "total_depositado" => $valor_deposito ?? Null,
                'total_pagamento' => $totalPagamentos ?? Null,
                'caixa' => $caixa ?? Null,
                'ano_lectivo_activo_id' => $this->anoLectivoActivo(),
                
                "ano_lectivos" => AnoLectivo::where('status', '1')->get(),
                
                "ano_lectivos" => $user->roles()->get(),
                "usuario" => $user
            ];
        }
        
        return Inertia::render('Dashboard', $header);
    }

}
