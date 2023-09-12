<?php

namespace App\Http\Controllers;

use App\Models\AnoLectivo;
use App\Models\Caixa;
use App\Models\Deposito;
use App\Models\MovimentoCaixa;
use App\Models\Pagamento;
use App\Models\User;
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

        $movimentos = null;
        
        if(auth()->user()->hasRole(['Gestor de Caixa'])){
            
            if($request->data_inicio){
                $request->data_inicio = $request->data_inicio;
            }else{
                $request->data_inicio = date("Y-m-d");
            }
            
            $movimentos = MovimentoCaixa::when($request->data_inicio, function($query, $value){
                $query->where("created_at", ">=", Carbon::parse($value));
            })->when($request->data_final, function($query, $value){
                $query->where("created_at", "<=", Carbon::parse($value));
            })
            ->get();

        }
        
        if(auth()->user()->hasRole(['Supervisor']))
        {
            if($request->data_inicio){
                $request->data_inicio = $request->data_inicio;
            }else{
                $request->data_inicio = date("Y-m-d");
            }
        
            $movimentos = MovimentoCaixa::when($request->data_inicio, function($query, $value){
                $query->where("created_at", ">=", Carbon::parse($value));
            })->when($request->data_final, function($query, $value){
                $query->where("created_at", "<=", Carbon::parse($value));
            })
            //->where('operador_id', $user->codigo_importado)
            ->get();

        }
        
        if(auth()->user()->hasRole(['Operador Caixa']))
        {
            if($request->data_inicio){
                $request->data_inicio = $request->data_inicio;
            }else{
                $request->data_inicio = date("Y-m-d");
            }
        
            $movimentos = MovimentoCaixa::when($request->data_inicio, function($query, $value){
                $query->where("created_at", ">=", Carbon::parse($value));
            })->when($request->data_final, function($query, $value){
                $query->where("created_at", "<=", Carbon::parse($value));
            })
            ->where('status_final', 'pendente')
            ->where('operador_id', $user->codigo_importado)
            ->get();
        
        
        }
            
        
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
        
        $valor_arrecadado_depositos = 0;
        $valor_facturado_pagamento = 0;
        $valor_arrecadado_total = 0;
                
        foreach($movimentos as $movimento) {
            $valor_arrecadado_depositos = $valor_arrecadado_depositos + $movimento->valor_arrecadado_depositos;
            $valor_facturado_pagamento = $valor_facturado_pagamento + $movimento->valor_facturado_pagamento;
            $valor_arrecadado_total = $valor_arrecadado_total + $movimento->valor_arrecadado_total - ($movimento->valor_abertura) ;
        }
       
        
        try {
            $header = [
                "valor_arrecadado_depositos" => $valor_arrecadado_depositos,
                "valor_facturado_pagamento" => $valor_facturado_pagamento,
                "valor_arrecadado_total" => $valor_arrecadado_total,
                
                'caixa' => $caixa,
                'ano_lectivo_activo_id' => $this->anoLectivoActivo(),
                
                "ano_lectivos" => AnoLectivo::where('status', '1')->get(),
                
                "ano_lectivos" => $user->roles()->get(),
                "usuario" => $user
            ];
            //code...
        } catch (\Throwable $th) {
            $header = [
                "valor_arrecadado_depositos" => $valor_arrecadado_depositos,
                "valor_facturado_pagamento" => $valor_facturado_pagamento,
                "valor_arrecadado_total" => $valor_arrecadado_total,
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
