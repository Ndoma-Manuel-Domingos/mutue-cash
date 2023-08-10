<?php

namespace App\Http\Controllers;

use App\Models\AnoLectivo;
use App\Models\Caixa;
use App\Models\Deposito;
use App\Models\Grupo;
use App\Models\GrupoUtilizador;
use App\Models\Pagamento;
use App\Models\TipoServico;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class RelatorioController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function fechoCaixaOperador(Request $request)
    {
            
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('created_by', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        if($caixa->bloqueio == 'Y'){
            return redirect()->route('mc.bloquear-caixa');
        }
    
    
        $user = auth()->user();
     
        $ano = AnoLectivo::where('status', '1')->first();
        
                 // utilizadores validadores
        // utilizadores adiministrativos
        // utilizadores área financeira
        // utilizadores tesouraria
        $validacao = Grupo::where('designacao', "Validação de Pagamentos")->select('pk_grupo')->first();
        $admins = Grupo::where('designacao', 'Administrador')->select('pk_grupo')->first();
        $finans = Grupo::where('designacao', 'Area Financeira')->select('pk_grupo')->first();
        $tesous = Grupo::where('designacao', 'Tesouraria')->select('pk_grupo')->first();
        
        if(!$request->ano_lectivo){
            $request->ano_lectivo = $ano->Codigo;
        }
        
        $valor_deposito = 0;
        $totalPagamentos = 0;
        
        if($request->data_inicio){
            $request->data_inicio = $request->data_inicio;
        }else{
            $request->data_inicio = date("Y-m-d");
        }
        
        
        if($user->tipo_grupo->grupo->designacao == "Administrador"){
        
            /** */
            $data['items'] = Pagamento::when($request->data_inicio, function($query, $value){
                $query->where('created_at', '>=' ,Carbon::parse($value) );
            })->when($request->data_final, function($query, $value){
                $query->where('created_at', '<=' ,Carbon::parse($value));
            })->when($request->operador, function($query, $value){
                $query->where('fk_utilizador', $value);
            })->when($request->ano_lectivo, function($query, $value){
                $query->where('tb_pagamentos.AnoLectivo', $value);
            })->when($request->servico_id, function($query, $value){
                $query->where('tb_pagamentosi.Codigo_Servico', $value);
            })
            ->where('forma_pagamento', 6)
            ->leftjoin('tb_preinscricao', 'tb_pagamentos.Codigo_PreInscricao', '=', 'tb_preinscricao.Codigo')
            ->leftjoin('tb_admissao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
            ->leftjoin('tb_matriculas', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
            ->leftjoin('tb_cursos', 'tb_matriculas.Codigo_Curso', '=', 'tb_cursos.Codigo')
            ->leftjoin('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', '=', 'tb_pagamentos.Codigo')
            ->leftjoin('tb_tipo_servicos', 'tb_pagamentosi.Codigo_Servico', '=', 'tb_tipo_servicos.Codigo')
            ->orderBy('tb_pagamentos.Codigo', 'desc')
            ->select( 'tb_pagamentos.Codigo', 'Nome_Completo', 'Totalgeral', 'DataRegisto', 'tb_matriculas.Codigo AS matricula', 'tb_cursos.Designacao AS curso', 'tb_tipo_servicos.Descricao AS servico')
            ->paginate(7)
            ->withQueryString();   
            /** */
            
            $valor_deposito = Deposito::when($request->data_inicio, function($query, $value){
                $query->where('data_movimento', '>=' , Carbon::parse($value) );
            })->when($request->data_final, function($query, $value){
                $query->where('data_movimento', '<=' , Carbon::parse($value));
            })->when($request->operador, function($query, $value){
                $query->where('created_by', $value);
            })->when($request->ano_lectivo, function($query, $value){
                $query->where('ano_lectivo_id', $value);
            })
            ->sum('valor_depositar');
            
            /** */
            
            $totalPagamentos = Pagamento::when($request->data_inicio, function($query, $value){
                $query->where('created_at', '>=' ,Carbon::parse($value) );
            })->when($request->data_final, function($query, $value){
                $query->where('created_at', '<=' ,Carbon::parse($value));
            })->when($request->operador, function($query, $value){
                $query->where('fk_utilizador', $value);
            })->when($request->ano_lectivo, function($query, $value){
                $query->where('tb_pagamentos.AnoLectivo', $value);
            })->when($request->servico_id, function($query, $value){
                $query->where('tb_pagamentosi.Codigo_Servico', $value);
            })
            ->where('forma_pagamento', 6)
            ->where('estado', 1)
            // ->where('fk_utilizador', $user->codigo_importado)
            ->sum('valor_depositado');
            
            /** */
    
            $data['utilizadores'] = GrupoUtilizador::whereIn('fk_grupo', [$validacao->pk_grupo, $finans->pk_grupo, $tesous->pk_grupo])->orWhere('fk_utilizador', Auth::user()->pk_utilizador)->with('utilizadores')->get();
            
        }else {
            
            $data['items'] = Pagamento::when($request->data_inicio, function($query, $value){
                $query->where('created_at', '>=' ,Carbon::parse($value) );
            })->when($request->data_final, function($query, $value){
                $query->where('created_at', '<=' ,Carbon::parse($value));
            })->when($request->operador, function($query, $value){
                $query->where('fk_utilizador', $value);
            })->when($request->ano_lectivo, function($query, $value){
                $query->where('tb_pagamentos.AnoLectivo', $value);
            })->when($request->servico_id, function($query, $value){
                $query->where('tb_pagamentosi.Codigo_Servico', $value);
            })
            ->where('forma_pagamento', 6)
            ->leftjoin('tb_preinscricao', 'tb_pagamentos.Codigo_PreInscricao', '=', 'tb_preinscricao.Codigo')
            ->leftjoin('tb_admissao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
            ->leftjoin('tb_matriculas', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
            ->leftjoin('tb_cursos', 'tb_matriculas.Codigo_Curso', '=', 'tb_cursos.Codigo')
            ->leftjoin('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', '=', 'tb_pagamentos.Codigo')
            ->leftjoin('tb_tipo_servicos', 'tb_pagamentosi.Codigo_Servico', '=', 'tb_tipo_servicos.Codigo')
            ->where('fk_utilizador', $user->codigo_importado)
            ->orderBy('tb_pagamentos.Codigo', 'desc')
            ->select('tb_pagamentos.Codigo', 'Nome_Completo', 'Totalgeral', 'DataRegisto', 'tb_matriculas.Codigo AS matricula', 'tb_cursos.Designacao AS curso', 'tb_tipo_servicos.Descricao AS servico')
            ->paginate(7)
            ->withQueryString();    
            
            $data['utilizadores'] = GrupoUtilizador::whereHas('utilizadores', function($query){
                $query->where('codigo_importado', auth()->user()->codigo_importado);
            })->whereIn('fk_grupo', [$validacao->pk_grupo, $finans->pk_grupo, $tesous->pk_grupo])->with('utilizadores')->get();
            
            
            $valor_deposito = Deposito::when($request->data_inicio, function($query, $value){
                $query->where('data_movimento', '>=' ,Carbon::parse($value) );
            })->when($request->data_final, function($query, $value){
                $query->where('data_movimento', '<=' ,Carbon::parse($value));
            })->when($request->operador, function($query, $value){
                $query->where('created_by', $value);
            })->when($request->ano_lectivo, function($query, $value){
                $query->where('ano_lectivo_id', $value);
            })
            ->where('created_by', $user->codigo_importado)
            ->sum('valor_depositar');
            
            $totalPagamentos = Pagamento::when($request->data_inicio, function($query, $value){
                $query->where('created_at', '>=' ,Carbon::parse($value) );
            })->when($request->data_final, function($query, $value){
                $query->where('created_at', '<=' ,Carbon::parse($value));
            })->when($request->operador, function($query, $value){
                $query->where('fk_utilizador', $value);
            })->when($request->ano_lectivo, function($query, $value){
                $query->where('tb_pagamentos.AnoLectivo', $value);
            })->when($request->servico_id, function($query, $value){
                $query->where('tb_pagamentosi.Codigo_Servico', $value);
            })
            ->where('forma_pagamento', 6)
            ->where('estado', 1)
            ->where('fk_utilizador', $user->codigo_importado)
            ->sum('valor_depositado');

        }
        
        $lista_geral = [];
        
        $pagamentos = Pagamento::where('forma_pagamento', 6)->get();
        $depositos = Deposito::limit(6)->get();
        
        foreach ($pagamentos as $pagamento) {
            $lista_geral[] = [
                'operador' => $pagamento->Utilizador,
                'type' => 'pagamento'
            ];
        }
        
        foreach ($depositos as $deposito) {
            $lista_geral[] = [
                'operador' => $deposito->created_by,
                'type' => 'deposito'
            ];
        }
        
        
        $data['ano_lectivos'] = AnoLectivo::orderBy('ordem', 'desc')->get();
        $data['servicos'] = TipoServico::when($request->ano_lectivo, function($query, $value){
            $query->where('codigo_ano_lectivo', $value);
        })->get();


        $data['valor_deposito'] = $valor_deposito;
        $data['totalPagamentos'] = $totalPagamentos;

        return Inertia::render('Relatorios/FechoCaixa/Operador', $data);
    }
    
    public function extratoDeposito(Request $request)
    {
        $user = auth()->user();
              
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('created_by', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        if($caixa->bloqueio == 'Y'){
            return redirect()->route('mc.bloquear-caixa');
        }
        
        $ano = AnoLectivo::where('status', '1')->first();
                
        if($request->data_inicio){
            $request->data_inicio = $request->data_inicio;
        }else{
            $request->data_inicio = date("Y-m-d");
        }
        

        if($user->tipo_grupo->grupo->designacao == "Administrador"){
            
            $data['items'] = Deposito::when($request->data_inicio, function($query, $value){
                 $query->where('created_at', '>=' ,Carbon::parse($value) );
             })->when($request->data_final, function($query, $value){
                 $query->where('created_at', '<=' ,Carbon::parse($value));
             })->when($request->operador, function($query, $value){
                 $query->where('created_by', $value);
             })
             ->when($request->codigo_matricula, function($query, $value){
                $query->where('codigo_matricula_id', $value);
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
            })
            ->when($request->codigo_matricula, function($query, $value){
                $query->where('codigo_matricula_id', $value);
            })
            ->sum('valor_depositar');
    
        
        }else {
                
            $data['items'] = Deposito::when($request->data_inicio, function($query, $value){
                $query->where('created_at', '>=' ,Carbon::parse($value) );
            })->when($request->data_final, function($query, $value){
                $query->where('created_at', '<=' ,Carbon::parse($value));
            })->when($request->operador, function($query, $value){
                $query->where('created_by', $value);
            })
            ->when($request->codigo_matricula, function($query, $value){
                $query->where('codigo_matricula_id', $value);
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
            ->when($request->codigo_matricula, function($query, $value){
                $query->where('codigo_matricula_id', $value);
            })
            ->where('created_by', $user->codigo_importado)
            ->sum('valor_depositar');
            
        }
        
        $data['valor_deposito'] = $valor_deposito;
        

        return Inertia::render('Relatorios/FechoCaixa/Extrato-Depositos', $data);
    
    }
    
    public function extratoPagamento(Request $request)
    {
            
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('created_by', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        if($caixa->bloqueio == 'Y'){
            return redirect()->route('mc.bloquear-caixa');
        }
        
        $user = auth()->user();
        
        $ano = AnoLectivo::where('status', '1')->first();
        
        
        if($request->data_inicio){
            $request->data_inicio = $request->data_inicio;
        }else{
            $request->data_inicio = date("Y-m-d");
        }        
        
        if($user->tipo_grupo->grupo->designacao == "Administrador"){
        
            $data['items'] = Pagamento::when($request->data_inicio, function($query, $value){
                $query->where('DataRegisto', '>=' ,Carbon::parse($value) );
            })->when($request->data_final, function($query, $value){
                $query->where('DataRegisto', '<=' ,Carbon::parse($value));
            })->when($request->codigo_matricula, function($query, $value){
                $query->where('factura.CodigoMatricula', $value);
            })
            ->leftjoin('factura', 'tb_pagamentos.codigo_factura', '=', 'factura.Codigo')
            ->where('forma_pagamento', 6)
            ->paginate(7)
            ->withQueryString();

        }else {
        
            $data['items'] = Pagamento::when($request->data_inicio, function($query, $value){
                $query->where('DataRegisto', '>=' ,Carbon::parse($value) );
            })->when($request->data_final, function($query, $value){
                $query->where('DataRegisto', '<=' ,Carbon::parse($value));
            })->when($request->codigo_matricula, function($query, $value){
                $query->where('factura.CodigoMatricula', $value);
            })
            ->where('fk_utilizador', $user->codigo_importado)
            ->leftjoin('factura', 'tb_pagamentos.codigo_factura', '=', 'factura.Codigo')
            ->where('forma_pagamento', 6)
            ->paginate(7)
            ->withQueryString();
            
        }

        

        return Inertia::render('Relatorios/FechoCaixa/Extrato-Pagamentos', $data);
    }
    
    
    public function pdf()
    {
                
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('created_by', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        if($caixa->bloqueio == 'Y'){
            return redirect()->route('mc.bloquear-caixa');
        }
    }
    
    public function excel()
    {
                
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('created_by', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        if($caixa->bloqueio == 'Y'){
            return redirect()->route('mc.bloquear-caixa');
        }
    }
    

}
