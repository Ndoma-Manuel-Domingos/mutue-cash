<?php

namespace App\Http\Controllers;

use App\Models\AnoLectivo;
use App\Models\Caixa;
use App\Models\Deposito;
use App\Models\Grupo;
use App\Models\GrupoUtilizador;
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
            return redirect('/movimentos/bloquear-caixa');
        }

        $movimentos = [];
        $condicoes = [];
        
        if(auth()->user()->hasRole(['Gestor de Caixa'])){
            
            if($request->data_inicio){
                $request->data_inicio = $request->data_inicio;
            }else{
                $request->data_inicio = date("Y-m-d");
            }
            if($request->operador_id){
                $request->operador_id = array_push($condicoes ,['operador_id',$request->operador_id]);
            }else{
                $request->operador_id = array_push($condicoes ,['operador_id','>',0]); 
            }
                        
            $movimentos = MovimentoCaixa::when($request->data_inicio, function($query, $value){
                $query->whereDate('data_at', '>=', Carbon::createFromDate($value));
            })->when($request->data_final, function($query, $value){
                $query->whereDate('data_at', '<=', Carbon::createFromDate($value));
            })->when($request->operador_id, function($query, $value) use($condicoes){
                $query->where($condicoes);
            })->get();
       

        }
        
        if(auth()->user()->hasRole(['Supervisor']))
        {

            if($request->data_inicio){
                $request->data_inicio = $request->data_inicio;
            }else{
                $request->data_inicio = date("Y-m-d");
            }
            if($request->operador_id){
                $request->operador_id = array_push($condicoes ,['operador_id',$request->operador_id]);
            }else{
                $request->operador_id = array_push($condicoes ,['operador_id','>',0]); 
            }
            
            $movimentos = MovimentoCaixa::when($request->data_inicio, function($query, $value){
                $query->whereDate('data_at', '>=', Carbon::createFromDate($value));
            })->when($request->data_final, function($query, $value){
                $query->whereDate('data_at', '<=', Carbon::createFromDate($value));
            })
            ->when($request->operador_id, function($query, $value) use($condicoes){
                $query->where($condicoes);
            })->get();

        }
        
        if(auth()->user()->hasRole(['Operador Caixa']))
        {
            if($request->data_inicio){
                $request->data_inicio = $request->data_inicio;
            }else{
                $request->data_inicio = date("Y-m-d");
            }
            
            $movimentos = MovimentoCaixa::when($request->data_inicio, function($query, $value){
                $query->whereDate('data_at', '>=', Carbon::createFromDate($value));
            })->when($request->data_final, function($query, $value){
                $query->whereDate('data_at', '<=', Carbon::createFromDate($value));
            })->where('status_final', 'pendente')->where('operador_id', Auth::user()->codigo_importado)->get();
        }
            
        
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
        
        $valor_arrecadado_depositos = 0;
        $valor_facturado_pagamento = 0;
        $valor_arrecadado_total = 0;
        $valor_arrecadado_pagamento = 0;
                
        foreach($movimentos as $movimento) {
            $valor_arrecadado_depositos += $movimento->valor_arrecadado_depositos;
            $valor_facturado_pagamento += $movimento->valor_facturado_pagamento;
            $valor_arrecadado_pagamento += $movimento->valor_arrecadado_pagamento;
        }
        
        $valor_arrecadado_total = $valor_arrecadado_depositos + $valor_facturado_pagamento;
        
        $validacao = Grupo::where('designacao', "Validação de Pagamentos")->select('pk_grupo')->first();
        $admins = Grupo::where('designacao', 'Administrador')->select('pk_grupo')->first();
        $finans = Grupo::where('designacao', 'Area Financeira')->select('pk_grupo')->first();
        $tesous = Grupo::where('designacao', 'Tesouraria')->select('pk_grupo')->first();
       
        if(auth()->user()->hasRole(['Gestor de Caixa', 'Supervisor'])){
            $data['utilizadores'] = GrupoUtilizador::whereIn('fk_grupo', [$validacao->pk_grupo, $finans->pk_grupo, $tesous->pk_grupo])->orWhere('fk_utilizador', Auth::user()->pk_utilizador)->with('utilizadores')->get();
        }
        
        if(auth()->user()->hasRole(['Operador Caixa'])){
            $data['utilizadores'] = GrupoUtilizador::whereHas('utilizadores', function ($query) {
                $query->where('codigo_importado', auth()->user()->codigo_importado);
            })->whereIn('fk_grupo', [$validacao->pk_grupo, $finans->pk_grupo, $tesous->pk_grupo])->with('utilizadores')->get();
        }
        
        try {
            $header = [
                "valor_arrecadado_depositos" => $valor_arrecadado_depositos,
                "valor_facturado_pagamento" => $valor_facturado_pagamento,
                "valor_arrecadado_pagamento" => $valor_arrecadado_pagamento,
                "valor_arrecadado_total" => $valor_arrecadado_total,
                "utilizadores" => $data['utilizadores'] ?? null,
                
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
                "valor_arrecadado_pagamento" => $valor_arrecadado_pagamento,
                "valor_arrecadado_total" => $valor_arrecadado_total,
                "utilizadores" => $data['utilizadores'] ?? null,
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
