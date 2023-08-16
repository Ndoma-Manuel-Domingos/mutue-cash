<?php

namespace App\Http\Controllers;

use App\Exports\DepositosExport;
use App\Models\Caixa;
use App\Models\Deposito;
use App\Models\Grupo;
use App\Models\GrupoUtilizador;
use App\Models\Matricula;
use App\Models\MovimentoCaixa;
use App\Models\PreInscricao;
use App\Models\Utilizador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class DepositoController extends Controller
{
    use TraitHelpers;
    
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function index(Request $request)
    {
    
        $user = auth()->user();
        
                
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('created_by', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect()->route('mc.bloquear-caixa');
        }
                 
         // utilizadores validadores
         // utilizadores adiministrativos
         // utilizadores área financeira
         // utilizadores tesouraria
         $validacao = Grupo::where('designacao', "Validação de Pagamentos")->select('pk_grupo')->first();
         $admins = Grupo::where('designacao', 'Administrador')->select('pk_grupo')->first();
         $finans = Grupo::where('designacao', 'Area Financeira')->select('pk_grupo')->first();
         $tesous = Grupo::where('designacao', 'Tesouraria')->select('pk_grupo')->first();
        
        
        $total_depositado = 0;
       
        if($user->tipo_grupo->grupo->designacao == "Administrador"){
            
            $data['items'] = Deposito::when($request->data_inicio, function($query, $value){
                 $query->where('created_at', '>=' ,Carbon::parse($value) );
             })->when($request->data_final, function($query, $value){
                 $query->where('created_at', '<=' ,Carbon::parse($value));
             })->when($request->operador, function($query, $value){
                 $query->where('created_by', $value);
             })
             ->with(['user', 'forma_pagamento', 'ano_lectivo', 'matricula.admissao.preinscricao'])
             ->orderBy('codigo', 'desc')
             ->paginate(10)
             ->withQueryString();
             
            $valor_deposito = Deposito::when($request->data_inicio, function($query, $value){
                $query->where('created_at', '>=' ,Carbon::parse($value) );
            })->when($request->data_final, function($query, $value){
                $query->where('created_at', '<=' ,Carbon::parse($value));
            })->when($request->operador, function($query, $value){
                $query->where('created_by', $value);
            })->sum('valor_depositar');
    
            $data['utilizadores'] = GrupoUtilizador::whereIn('fk_grupo', [$validacao->pk_grupo, $finans->pk_grupo, $tesous->pk_grupo])->with('utilizadores')->get();
        
        }else {
                
            $data['items'] = Deposito::when($request->data_inicio, function($query, $value){
                $query->where('created_at', '>=' ,Carbon::parse($value) );
            })->when($request->data_final, function($query, $value){
                $query->where('created_at', '<=' ,Carbon::parse($value));
            })->when($request->operador, function($query, $value){
                $query->where('created_by', $value);
            })
            ->where('created_by', $user->codigo_importado)
            ->with(['user', 'forma_pagamento', 'ano_lectivo', 'matricula.admissao.preinscricao'])
            ->orderBy('codigo', 'desc')
            ->paginate(10)
            ->withQueryString();
            
            $valor_deposito = Deposito::when($request->data_inicio, function($query, $value){
                $query->where('created_at', '>=' ,Carbon::parse($value) );
            })->when($request->data_final, function($query, $value){
                $query->where('created_at', '<=' ,Carbon::parse($value));
            })->when($request->operador, function($query, $value){
                $query->where('created_by', $value);
            })
            ->where('created_by', $user->codigo_importado)
            ->sum('valor_depositar');
            
            $data['utilizadores'] = GrupoUtilizador::whereHas('utilizadores', function($query){
                $query->where('codigo_importado', auth()->user()->codigo_importado);
            })->whereIn('fk_grupo', [$validacao->pk_grupo, $finans->pk_grupo, $tesous->pk_grupo])->with('utilizadores')->get();
        }
        
        $data['total_depositado'] = $valor_deposito;
        
        return Inertia::render('Operacoes/Depositos/Index', $data);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'codigo_matricula' => 'required',
            'valor_a_depositar' => 'required|numeric',

        ], [
            'codigo_matricula.required' => "Codigo de matricula Invalido!",
            'valor_a_depositar.required' => "Valor a depositar Invalido!",
            'valor_a_depositar.numeric' => "Valor a depositar deve serve um valor númerico!",
        ]);
        
        $caixas = Caixa::where('created_by', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
        
        if(!$caixas){
            return response()->json([
                'message' => 'Deposito realizado com sucesso!',
            ], 401);
        }
        
        $movimento = MovimentoCaixa::where('caixa_id', $caixas->codigo)->where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
                
        $resultado = Matricula::where('tb_matriculas.Codigo', $request->codigo_matricula)
        ->join('tb_admissao', 'tb_matriculas.Codigo_Aluno', '=', 'tb_admissao.Codigo')
        ->join('tb_preinscricao', 'tb_admissao.pre_incricao', '=', 'tb_preinscricao.Codigo')
        ->select('tb_preinscricao.Codigo','tb_preinscricao.saldo_anterior','tb_preinscricao.saldo')
        ->first();
        
        $saldo_apos_movimento = $resultado->saldo + $request->valor_a_depositar;

        // registramos o deposito 
        $create = Deposito::create([
            'codigo_matricula_id' => $request->codigo_matricula,
            'canal_cominucacao_id' => 1,
            'valor_depositar' => $request->valor_a_depositar,
            'saldo_apos_movimento' => $saldo_apos_movimento,
            'forma_pagamento_id' => 6,
            'data_movimento' => date("Y-m-d"),
            'ano_lectivo_id' => $this->anoLectivoActivo(),
            'created_by' => Auth::user()->codigo_importado,
            'updated_by' => Auth::user()->codigo_importado,
        ]);
        
        // actualizamos os dados do aluno
        $preinscricao = PreInscricao::findOrFail($resultado->Codigo);
        $preinscricao->saldo_anterior = $preinscricao->saldo;
        $preinscricao->saldo += $request->valor_a_depositar;
        $preinscricao->update();
        
        
        $update = MovimentoCaixa::findOrFail($movimento->codigo);
        $update->valor_arrecadado_depositos = $update->valor_arrecadado_depositos + $request->valor_a_depositar;
        $update->valor_arrecadado_total = $update->valor_arrecadado_total + $request->valor_a_depositar;
        $update->update();
        
        //sucesso
        // return redirect()->back()->with();
        
        // Retorne a resposta em JSON
        return response()->json([
            'message' => 'Deposito realizado com sucesso!',
            'data' => $create
        ]);
        

    }
    
    public function pdf(Request $request)
    {
    
            
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('created_by', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect()->route('mc.bloquear-caixa');
        }

        $data['items'] = Deposito::when($request->data_inicio, function($query, $value){
            $query->where('created_at', '>=' ,Carbon::parse($value) );
        })->when($request->data_final, function($query, $value){
            $query->where('created_at', '<=' ,Carbon::parse($value));
        })
        ->when($request->operador, function($query, $value){
            $query->where('created_by', $value);
        })
        ->with(['user', 'forma_pagamento', 'ano_lectivo', 'matricula.admissao.preinscricao'])
        ->get();
        
        $data['requests'] = $request->all('data_inicio', 'data_final');
        $data['operador'] =  Utilizador::where('codigo_importado', $request->operador ?? auth()->user()->codigo_importado)->first();
        
        $pdf = \App::make('dompdf.wrapper');
        $pdf->loadView('Relatorios.listagem-depositos', $data);
        $pdf->getDOMPdf()->set_option('isPhpEnabled', true);
        return $pdf->stream();
    }
    
      
    public function excel(Request $request)
    {          
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('created_by', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect()->route('mc.bloquear-caixa');
        }

        return Excel::download(new DepositosExport($request), 'lista-de-depositos.xlsx');
    }
      
    public function imprimir(Request $request)
    {
               
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('created_by', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect()->route('mc.bloquear-caixa');
        }
        
        $data['item'] = Deposito::when($request->data_inicio, function($query, $value){
            $query->where('created_at', '>=' ,Carbon::parse($value) );
        })->when($request->data_final, function($query, $value){
            $query->where('created_at', '<=' ,Carbon::parse($value));
        })
        ->when($request->operador, function($query, $value){
            $query->where('created_by', $value);
        })
        ->with(['user', 'forma_pagamento', 'ano_lectivo', 'matricula.admissao.preinscricao'])
        ->findOrFail($request->codigo);
        
        $pdf = \App::make('dompdf.wrapper');
        $pdf->loadView('Relatorios.recibo', $data);
        $pdf->getDOMPdf()->set_option('isPhpEnabled', true);
        return $pdf->stream();
        
    }
    
    
    
}
