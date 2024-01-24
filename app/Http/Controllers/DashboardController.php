<?php

namespace App\Http\Controllers;

use App\Models\AnoLectivo;
use App\Models\Caixa;
use App\Models\Deposito;
use App\Models\Grupo;
use App\Models\GrupoUtilizador;
use App\Models\MovimentoCaixa;
use App\Models\Pagamento;
use App\Models\PagamentoItems;
use App\Models\TipoServico;
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
            
            // $operador_id = $request->operador_id;
            // if($operador_id){
            //     $operador_id = array_push($condicoes ,['operador_id',$operador_id]);
            // }else{
            //     $operador_id = array_push($condicoes ,['operador_id','>',0]); 
            // }
            // dd($operador_id);
                        
            $movimentos = MovimentoCaixa::when($request->data_inicio, function($query, $value){
                $query->whereDate('data_at', '>=', Carbon::createFromDate($value));
            })->when($request->data_final, function($query, $value){
                $query->whereDate('data_at', '<=', Carbon::createFromDate($value));
            })->when($request->operador_id, function($query, $value){
                $query->where('operador_id', $value);
            })->get();
       

        }
        
        if(auth()->user()->hasRole(['Supervisor']))
        {

            if($request->data_inicio){
                $request->data_inicio = $request->data_inicio;
            }else{
                $request->data_inicio = date("Y-m-d");
            }
            
            // $operador_id = $request->operador_id;
            
            // if($operador_id){
            //     $operador_id = array_push($condicoes ,['operador_id',$operador_id]);
            // }else{
            //     $operador_id = array_push($condicoes ,['operador_id','>',0]); 
            // }
            
            $movimentos = MovimentoCaixa::when($request->data_inicio, function($query, $value){
                $query->whereDate('data_at', '>=', Carbon::createFromDate($value));
            })->when($request->data_final, function($query, $value){
                $query->whereDate('data_at', '<=', Carbon::createFromDate($value));
            })
            ->when($request->operador_id, function($query, $value){
                $query->where('operador_id', $value);
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
            $data['utilizadores'] = GrupoUtilizador::whereIn('fk_grupo', [$validacao->pk_grupo, $finans->pk_grupo, $tesous->pk_grupo])
            ->orWhere('fk_utilizador', Auth::user()->pk_utilizador)
            ->with('utilizadores')
            ->get();
        }
        
        if(auth()->user()->hasRole(['Operador Caixa'])){
            $data['utilizadores'] = GrupoUtilizador::whereHas('utilizadores', function ($query) {
                $query->where('codigo_importado', auth()
                ->user()->codigo_importado);
            })->whereIn('fk_grupo', [$validacao->pk_grupo, $finans->pk_grupo, $tesous->pk_grupo])
            ->with('utilizadores')
            ->get();
        }
        
        
        // dd($data['utilizadores']);
        
        if($request->operador_id){
            $request->operador_id = $request->operador_id;
        }else{
            $request->operador_id = "";
        }
          
        // Obtém a data atual
        $dataAtual = Carbon::now();

        // Calcula a data há seis meses atrás
        $dataSeisMesesAtras = $dataAtual->subMonths(6);
         
        // Query para obter os últimos pagamentos nos últimos seis meses e somar os valores
        $ultimosPagamentos = Pagamento::when($request->operador_id, function($query, $value){
            $query->where('fk_utilizador', $value);
        })->whereIn('estado', [1])
            ->where('forma_pagamento', 6)
            ->where('Data', '>=', $dataSeisMesesAtras)
            ->selectRaw('DATE_FORMAT(Data, "%Y-%m") AS mes, SUM(valor_depositado) AS total')
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();
            
        $ultimosDepositos = Deposito::when($request->operador_id, function($query, $value){
            $query->where('created_by', $value);
        })->where('data_movimento', '>=', $dataSeisMesesAtras)
            ->selectRaw('DATE_FORMAT(data_movimento, "%Y-%m") AS mes, SUM(valor_depositar) AS total')
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();
            
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
                "usuario" => $user,
                "ultimosPagamentos" => $ultimosPagamentos,
                "ultimosDepositos" => $ultimosDepositos,
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
                "usuario" => $user,
                "ultimosPagamentos" => $ultimosPagamentos,
                "ultimosDepositos" => $ultimosDepositos,
            ];
        }
        

        return Inertia::render('Dashboard', $header);
    }
    
    public function pagamentoUltimosSeisMeses(Request $request)
    {
    }

}
