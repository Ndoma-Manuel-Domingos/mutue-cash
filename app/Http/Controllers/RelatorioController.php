<?php

namespace App\Http\Controllers;

use App\Exports\DepositosExtratoExport;
use App\Exports\PagamentosExtratoExport;
use App\Models\AnoLectivo;
use App\Models\Caixa;
use App\Models\Deposito;
use App\Models\Grupo;
use App\Models\GrupoUtilizador;
use App\Models\Matricula;
use App\Models\MovimentoCaixa;
use App\Models\Pagamento;
use App\Models\PagamentoItems;
use App\Models\PreInscricao;
use App\Models\TipoServico;
use App\Models\Utilizador;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class RelatorioController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function fechoCaixaOperador(Request $request)
    {

        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        if($caixa && $caixa->bloqueio == 'Y'){
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
        
        $valor_deposito = 0;
        $totalPagamentos = 0;
        $condicoes = [];
        $user = auth()->user();

        if(!$request->ano_lectivo){
            $request->ano_lectivo = $ano->Codigo;
        }
        if($request->data_inicio){
            $request->data_inicio = $request->data_inicio;
        }else{
            $request->data_inicio = date("Y-m-d");
        }
        if($request->operador){
            $request->operador = $request->operador;
        }else{
            $request->operador = $request->operador > 0;
        }

        if(auth()->user()->hasRole(['Gestor de Caixa'])){
            /** */
            $data['items'] = Pagamento::when($request->data_inicio, function($query, $value){
                $query->whereDate('tb_pagamentos.Data', '>=', Carbon::createFromDate($value));
            })->when($request->data_final, function($query, $value){
                $query->whereDate('tb_pagamentos.Data', '<=', Carbon::createFromDate($value));
            })->when($request->operador, function($query, $value){
                $query->where('fk_utilizador', $value);
            })->when($request->ano_lectivo, function($query, $value){
                $query->where('tb_pagamentos.AnoLectivo', $value);
            })->when($request->servico_id, function($query, $value){
                $query->where('tb_pagamentosi.Codigo_Servico', $value);
            })->where('forma_pagamento', 6)
            ->join('factura', 'factura.Codigo', '=', 'tb_pagamentos.codigo_factura')
            ->join('factura_descricao', 'factura_descricao.id', '=', 'factura.codigo_descricao')
            ->leftjoin('tb_preinscricao', 'tb_pagamentos.Codigo_PreInscricao', '=', 'tb_preinscricao.Codigo')
            ->leftjoin('tb_admissao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
            ->leftjoin('tb_matriculas', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
            ->leftjoin('tb_cursos', 'tb_matriculas.Codigo_Curso', '=', 'tb_cursos.Codigo')
            ->leftjoin('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', '=', 'tb_pagamentos.Codigo')
            ->leftjoin('tb_tipo_servicos', 'tb_pagamentosi.Codigo_Servico', '=', 'tb_tipo_servicos.Codigo')
            ->orderBy('tb_pagamentos.Codigo', 'desc')
            ->select( 'tb_pagamentos.Codigo', 'Nome_Completo', 'Totalgeral', 'DataRegisto', 'tb_pagamentos.Data', 'tb_pagamentos.codigo_factura', 'tb_matriculas.Codigo AS matricula', 'tb_cursos.Designacao AS curso', 'tb_tipo_servicos.Descricao AS servico','factura_descricao.descricao')
            ->distinct('tb_pagamentos.Codigo')
            ->paginate(50)
            ->withQueryString();   
            /** */
         
            $valor_deposito = Deposito::when($request->data_inicio, function($query, $value){
                $query->whereDate('data_movimento', '>=', Carbon::createFromDate($value));
            })->when($request->data_final, function($query, $value){
                $query->whereDate('data_movimento', '<=', Carbon::createFromDate($value));
            })->when($request->operador, function($query, $value){
                $query->where('created_by', $value);
            })->when($request->ano_lectivo, function($query, $value){
                $query->where('ano_lectivo_id', $value);
            })
            ->sum('valor_depositar');
            
            /** */
            
            $totalPagamentos = Pagamento::when($request->data_inicio, function($query, $value){
                $query->whereDate('tb_pagamentos.Data', '>=', Carbon::createFromDate($value));
            })->when($request->data_final, function($query, $value){
                $query->whereDate('tb_pagamentos.Data', '<=', Carbon::createFromDate($value));
            })->when($request->operador, function($query, $value){
                $query->where('fk_utilizador', $value);
            })->when($request->ano_lectivo, function($query, $value){
                $query->where('tb_pagamentos.AnoLectivo', $value);
            })->when($request->servico_id, function($query, $value){
                $query->where('tb_pagamentosi.Codigo_Servico', $value);
            })
            ->join('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', '=', 'tb_pagamentos.Codigo')
            ->join('tb_tipo_servicos', 'tb_pagamentosi.Codigo_Servico', '=', 'tb_tipo_servicos.Codigo')
            ->where('tb_pagamentos.forma_pagamento', 6)
            ->where('tb_pagamentos.estado', 1)
            ->distinct(['tb_pagamentos.Codigo'])
            ->sum('tb_pagamentos.valor_depositado');

            $movimentos = MovimentoCaixa::when($request->data_inicio, function($query, $value){
                $query->whereDate('data_at', '>=', Carbon::createFromDate($value));
            })->when($request->data_final, function($query, $value){
                $query->whereDate('data_at', '<=', Carbon::createFromDate($value));
            })->when($request->operador, function($query, $value){
                $query->where('operador_id', $value);
            })->get();

        }
        
        if(auth()->user()->hasRole(['Supervisor']))
        {
            $request->data_inicio = date("Y-m-d");

            $data['items'] = Pagamento::when($request->data_inicio, function($query, $value){
                 $query->whereDate('tb_pagamentos.Data', '>=', Carbon::createFromDate($value));
            })->when($request->data_final, function($query, $value){
                 $query->whereDate('tb_pagamentos.Data', '<=', Carbon::createFromDate($value));
            })->when($request->operador, function($query, $value){
                $query->where('fk_utilizador', $value);
            })->when($request->ano_lectivo, function($query, $value){
                $query->where('tb_pagamentos.AnoLectivo', $value);
            })->when($request->servico_id, function($query, $value){
                $query->where('tb_pagamentosi.Codigo_Servico', $value);
            })
            ->where('forma_pagamento', 6)
            ->join('factura', 'factura.Codigo', '=', 'tb_pagamentos.codigo_factura')
            ->join('factura_descricao', 'factura_descricao.id', '=', 'factura.codigo_descricao')
            ->leftjoin('tb_preinscricao', 'tb_pagamentos.Codigo_PreInscricao', '=', 'tb_preinscricao.Codigo')
            ->leftjoin('tb_admissao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
            ->leftjoin('tb_matriculas', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
            ->leftjoin('tb_cursos', 'tb_matriculas.Codigo_Curso', '=', 'tb_cursos.Codigo')
            ->leftjoin('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', '=', 'tb_pagamentos.Codigo')
            ->leftjoin('tb_tipo_servicos', 'tb_pagamentosi.Codigo_Servico', '=', 'tb_tipo_servicos.Codigo')
            ->orderBy('tb_pagamentos.Codigo', 'desc')
            ->select( 'tb_pagamentos.Codigo', 'Nome_Completo', 'Totalgeral', 'DataRegisto', 'tb_pagamentos.Data', 'tb_matriculas.Codigo AS matricula', 'tb_cursos.Designacao AS curso', 'tb_tipo_servicos.Descricao AS servico','factura_descricao.descricao')
            ->distinct('tb_pagamentos.Codigo')
            ->paginate(15)
            ->withQueryString();    
            
            $valor_deposito = Deposito::when($request->data_inicio, function($query, $value){
                $query->whereDate('data_movimento', '>=', Carbon::createFromDate($value));
            })->when($request->data_final, function($query, $value){
                $query->whereDate('data_movimento', '<=', Carbon::createFromDate($value));
            })->when($request->operador, function($query, $value){
                $query->where('created_by', $value);
            })->when($request->ano_lectivo, function($query, $value){
                $query->where('ano_lectivo_id', $value);
            })
            ->where('created_by', $user->codigo_importado)
            ->sum('valor_depositar');
            
            $totalPagamentos = Pagamento::when($request->data_inicio, function($query, $value){
                $query->whereDate('tb_pagamentos.Data', '>=', Carbon::createFromDate($value));
            })->when($request->data_final, function($query, $value){
                $query->whereDate('tb_pagamentos.Data', '<=', Carbon::createFromDate($value));
            })->when($request->operador, function($query, $value){
                $query->where('fk_utilizador', $value);
            })->when($request->ano_lectivo, function($query, $value){
                $query->where('tb_pagamentos.AnoLectivo', $value);
            })->when($request->servico_id, function($query, $value){
                $query->where('tb_pagamentosi.Codigo_Servico', $value);
            })
            ->leftjoin('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', '=', 'tb_pagamentos.Codigo')
            ->leftjoin('tb_tipo_servicos', 'tb_pagamentosi.Codigo_Servico', '=', 'tb_tipo_servicos.Codigo')
            ->where('tb_pagamentos.forma_pagamento', 6)
            ->where('tb_pagamentos.estado', 1)
            ->sum('tb_pagamentos.valor_depositado');

            $movimentos = MovimentoCaixa::when($request->data_inicio, function($query, $value){
                $query->whereDate('data_at', '>=', Carbon::createFromDate($value));
            })->when($request->data_final, function($query, $value){
                $query->whereDate('data_at', '<=', Carbon::createFromDate($value));
            })
            ->when($request->operador, function($query, $value){
                $query->where('operador_id', $value);
            })->get();
        
        }
        
        if(auth()->user()->hasRole(['Operador Caixa']))
        {
            $request->data_inicio = date("Y-m-d");
            $data['items'] = Pagamento::when($request->data_inicio, function($query, $value){
                $query->whereDate('tb_pagamentos.Data', '>=', Carbon::createFromDate($value));
            })->when($request->data_final, function($query, $value){
                $query->whereDate('tb_pagamentos.Data', '<=', Carbon::createFromDate($value));
            })->when($request->operador, function($query, $value){
                $query->where('fk_utilizador', $value);
            })->when($request->ano_lectivo, function($query, $value){
                $query->where('tb_pagamentos.AnoLectivo', $value);
            })->when($request->servico_id, function($query, $value){
                $query->where('tb_pagamentosi.Codigo_Servico', $value);
            })
            ->where('forma_pagamento', 6)
            ->join('factura', 'factura.Codigo', '=', 'tb_pagamentos.codigo_factura')
            ->join('factura_descricao', 'factura_descricao.id', '=', 'factura.codigo_descricao')
            ->leftjoin('tb_preinscricao', 'tb_pagamentos.Codigo_PreInscricao', '=', 'tb_preinscricao.Codigo')
            ->leftjoin('tb_admissao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
            ->leftjoin('tb_matriculas', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
            ->leftjoin('tb_cursos', 'tb_matriculas.Codigo_Curso', '=', 'tb_cursos.Codigo')
            ->leftjoin('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', '=', 'tb_pagamentos.Codigo')
            ->leftjoin('tb_tipo_servicos', 'tb_pagamentosi.Codigo_Servico', '=', 'tb_tipo_servicos.Codigo')
            ->where('fk_utilizador', $user->codigo_importado)
            ->select( 'tb_pagamentos.Codigo', 'Nome_Completo', 'Totalgeral', 'DataRegisto', 'tb_pagamentos.Data', 'tb_matriculas.Codigo AS matricula', 'tb_cursos.Designacao AS curso', 'tb_tipo_servicos.Descricao AS servico','factura_descricao.descricao')
            ->orderBy('tb_pagamentos.Codigo', 'desc')
            ->distinct('tb_pagamentos.Codigo')
            ->paginate(15)
            ->withQueryString();    
            
     
            $valor_deposito = Deposito::when($request->data_inicio, function($query, $value){
                $query->whereDate('data_movimento', '>=', Carbon::createFromDate($value));
            })->when($request->data_final, function($query, $value){
                $query->whereDate('data_movimento', '<=', Carbon::createFromDate($value));
            })->when($request->operador, function($query, $value){
                $query->where('created_by', $value);
            })->when($request->ano_lectivo, function($query, $value){
                $query->where('ano_lectivo_id', $value);
            })
            ->where('created_by', $user->codigo_importado)
            ->sum('valor_depositar');
            
            $totalPagamentos = Pagamento::when($request->data_inicio, function($query, $value){
                $query->whereDate('tb_pagamentos.Data', '>=', Carbon::createFromDate($value));
            })->when($request->data_final, function($query, $value){
                $query->whereDate('tb_pagamentos.Data', '<=', Carbon::createFromDate($value));
            })->when($request->operador, function($query, $value){
                $query->where('fk_utilizador', $value);
            })->when($request->ano_lectivo, function($query, $value){
                $query->where('tb_pagamentos.AnoLectivo', $value);
            })->when($request->servico_id, function($query, $value){
                $query->where('tb_pagamentosi.Codigo_Servico', $value);
            })
            ->leftjoin('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', '=', 'tb_pagamentos.Codigo')
            ->leftjoin('tb_tipo_servicos', 'tb_pagamentosi.Codigo_Servico', '=', 'tb_tipo_servicos.Codigo')
            ->where('tb_pagamentos.forma_pagamento', 6)
            ->where('tb_pagamentos.estado', 1)
            ->where('tb_pagamentos.fk_utilizador', $user->codigo_importado)
            ->sum('tb_pagamentos.valor_depositado');


            $movimentos = MovimentoCaixa::when($request->data_inicio, function($query, $value){
                $query->whereDate('data_at', '>=', Carbon::createFromDate($value));
            })->when($request->data_final, function($query, $value){
                $query->whereDate('data_at', '<=', Carbon::createFromDate($value));
            })
            ->when($request->operador, function($query, $value){
                $query->where('operador_id', $value);
            })->where('status_final', 'pendente')
            // ->where('operador_id', $user->codigo_importado)
            ->get();
        
        }
            
        
        if(auth()->user()->hasRole(['Gestor de Caixa', 'Supervisor'])){
            $data['utilizadores'] = GrupoUtilizador::whereIn('fk_grupo', [$validacao->pk_grupo, $finans->pk_grupo, $tesous->pk_grupo])->orWhere('fk_utilizador', Auth::user()->pk_utilizador)->with('utilizadores')->get();
        }
        
        if(auth()->user()->hasRole(['Operador Caixa'])){
            $data['utilizadores'] = GrupoUtilizador::whereHas('utilizadores', function ($query) {
                $query->where('codigo_importado', auth()->user()->codigo_importado);
            })->whereIn('fk_grupo', [$validacao->pk_grupo, $finans->pk_grupo, $tesous->pk_grupo])->with('utilizadores')->get();
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


        // $data['valor_deposito'] = $valor_deposito;
        // $data['totalPagamentos'] = $totalPagamentos;
        // $data['total_arrecadado'] = ($valor_deposito+$totalPagamentos);

        $data['valor_deposito'] = $valor_arrecadado_depositos;
        $data['totalPagamentos'] = $valor_facturado_pagamento;
        $data['total_arrecadado'] = $valor_arrecadado_total;

        return Inertia::render('Relatorios/FechoCaixa/Operador', $data);
    }
    
    public function extratoDeposito(Request $request)
    {
        $user = auth()->user();
              
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect()->route('mc.bloquear-caixa');
        }
        
        $ano = AnoLectivo::where('status', '1')->first();

        $estudante = Matricula::with(['admissao.preinscricao'])->find($request->codigo_matricula);
        $candidato = PreInscricao::find($request->candidato_id);
        
        if($request->codigo_matricula!=NULL){
            if(blank($estudante)){
                return redirect()->back()->withErrors('Não foi possível encontrar o estudante com o nº : ' . $request->codigo_matricula.'! Informe o nº de candidatura ');
            }
        }elseif($request->candidato_id!=NULL){
            if(blank($candidato)){
                return redirect()->back()->withErrors('Não foi possível encontrar o estudante com o nº : ' . $request->candidato_id.'! Informe o nº de matricula caso tenha ');
            }
        }

        if($estudante){
            $request->codigo_matricula = $estudante->admissao->preinscricao->Codigo??NULL;
        }elseif($candidato){
            $request->codigo_matricula = $candidato->Codigo; 
        }
      
        if(auth()->user()->hasRole(['Gestor de Caixa'])){
            
            if($request->data_inicio){
                $request->data_inicio = $request->data_inicio;
            }else{
                $request->data_inicio = date("Y-m-d");
            }
            
            $data['items'] = Deposito::when($request->data_inicio, function($query, $value){
               $query->whereDate('data_movimento', '>=', Carbon::createFromDate($value));
            })->when($request->data_final, function($query, $value){
                $query->whereDate('data_movimento', '<=', Carbon::createFromDate($value));
            })->when($request->operador, function($query, $value){
                $query->where('created_by', $value);
            })
            ->when($request->codigo_matricula, function($query, $value){
               $query->where('Codigo_PreInscricao', $value);
           })
            ->with(['user', 'forma_pagamento', 'ano_lectivo', 'matricula.admissao.preinscricao', 'candidato'])
            ->orderBy('codigo', 'desc')
            ->paginate(15)
            ->withQueryString();
            
           $valor_deposito = Deposito::when($request->data_inicio, function($query, $value){
                $query->whereDate('data_movimento', '>=', Carbon::createFromDate($value));
           })->when($request->data_final, function($query, $value){
               $query->whereDate('data_movimento', '<=', Carbon::createFromDate($value));
           })->when($request->operador, function($query, $value){
               $query->where('created_by', $value);
           })
           ->when($request->codigo_matricula, function($query, $value) {
               $query->where('Codigo_PreInscricao', $value);
           })->sum('valor_depositar');
        
        }
        
        if(auth()->user()->hasRole(['Supervisor']))
        {
        
            if($request->data_inicio){
                $request->data_inicio = $request->data_inicio;
            }else{
                $request->data_inicio = date("Y-m-d");
            }
            
            if($request->operador_id){
                $request->operador_id = $request->operador_id;
            }else{
                $request->operador_id = Auth::user()->codigo_importado;
            }
        
            $data['items'] = Deposito::when($request->data_inicio, function($query, $value){
               $query->whereDate('data_movimento', '>=', Carbon::createFromDate($value));
            })->when($request->data_final, function($query, $value){
              $query->whereDate('data_movimento', '<=', Carbon::createFromDate($value));
            })->when($request->operador, function($query, $value){
                $query->where('created_by', $value);
            })
            ->when($request->codigo_matricula, function($query, $value){
                $query->where('Codigo_PreInscricao', $value);
            })
            ->with(['user', 'forma_pagamento', 'ano_lectivo', 'matricula.admissao.preinscricao', 'candidato'])
            ->orderBy('codigo', 'desc')
            ->paginate(15)
            ->withQueryString();
            
            $valor_deposito = Deposito::when($request->data_inicio, function($query, $value){
                 $query->whereDate('data_movimento', '>=', Carbon::createFromDate($value));
            })->when($request->data_final, function($query, $value){
               $query->whereDate('data_movimento', '<=', Carbon::createFromDate($value));
            })->when($request->operador, function($query, $value){
                $query->where('created_by', $value);
            })
            ->when($request->codigo_matricula, function($query, $value) {
                $query->where('Codigo_PreInscricao', $value);
            })
            ->sum('valor_depositar');
        }
        
        if(auth()->user()->hasRole(['Operador Caixa']))
        {
            if($request->data_inicio){
                $request->data_inicio = $request->data_inicio;
            }else{
                $request->data_inicio = date("Y-m-d");
            }
            
            $data['items'] = Deposito::when($request->data_inicio, function($query, $value){
                 $query->whereDate('data_movimento', '>=', Carbon::createFromDate($value));
            })->when($request->data_final, function($query, $value){
                $query->whereDate('data_movimento', '<=', Carbon::createFromDate($value));
            })->when($request->operador, function($query, $value){
                $query->where('created_by', $value);
            })
            ->when($request->codigo_matricula, function($query, $value){
                $query->where('Codigo_PreInscricao', $value);
            })
            ->where('created_by', $user->codigo_importado)
            ->with(['user', 'forma_pagamento', 'ano_lectivo', 'matricula.admissao.preinscricao', 'candidato'])
            ->orderBy('codigo', 'desc')
            ->paginate(15)
            ->withQueryString();
            
            $valor_deposito = Deposito::when($request->data_inicio, function($query, $value){
                 $query->whereDate('data_movimento', '>=', Carbon::createFromDate($value));
            })->when($request->data_final, function($query, $value){
                $query->whereDate('data_movimento', '<=', Carbon::createFromDate($value));
            })->when($request->operador, function($query, $value){
                $query->where('created_by', $value);
            })
            ->when($request->codigo_matricula, function($query, $value) {
                $query->where('Codigo_PreInscricao', $value);
            })
            ->where('created_by', $user->codigo_importado)
            ->sum('valor_depositar');
                       
        }
                 
        
        $data['valor_deposito'] = $valor_deposito;
        
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
        

        return Inertia::render('Relatorios/FechoCaixa/Extrato-Depositos', $data);
    
    }
    
    public function pdf_deposito(Request $request)
    {

        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect()->route('mc.bloquear-caixa');
        }

        $estudante = Matricula::with(['admissao.preinscricao'])->find($request->codigo_matricula);
        $candidato = PreInscricao::find($request->candidato_id);
        
        if($request->codigo_matricula!=NULL){
            if(blank($estudante)){
                return redirect()->back()->withErrors('Não foi possível encontrar o estudante com o nº : ' . $request->codigo_matricula.'! Informe o nº de candidatura ');
            }
        }elseif($request->candidato_id!=NULL){
            if(blank($candidato)){
                return redirect()->back()->withErrors('Não foi possível encontrar o estudante com o nº : ' . $request->candidato_id.'! Informe o nº de matricula caso tenha ');
            }
        }

        if($estudante){
            $request->codigo_matricula = $estudante->admissao->preinscricao->Codigo??NULL;
        }elseif($candidato){
            $request->codigo_matricula = $candidato->Codigo; 
        }

        $data['items'] = Deposito::when($request->data_inicio, function($query, $value){
            $query->where('created_at', '>=' ,Carbon::parse($value) );
        })->when($request->codigo_matricula, function($query, $value) {
            $query->where('Codigo_PreInscricao', $value);
        })->when($request->operador, function($query, $value){
            $query->where('created_by', $value);
        })->with(['user', 'forma_pagamento', 'ano_lectivo', 'matricula.admissao.preinscricao', 'candidato'])
        ->get();
        
        $data['matricula'] = Matricula::with(['admissao.preinscricao'])->find($request->codigo_matricula);
        
        
        $data['requests'] = $request->all('data_inicio', 'data_final');
        $data['operador'] =  Utilizador::where('codigo_importado', auth()->user()->codigo_importado)->first();
        
        $pdf = \App::make('dompdf.wrapper');
        $pdf->loadView('Relatorios.listagem-depositos-extratos', $data);
        $pdf->getDOMPdf()->set_option('isPhpEnabled', true);
        return $pdf->stream();
    }
    
      
    public function excel_deposito(Request $request)
    {        
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect()->route('mc.bloquear-caixa');
        }

        return Excel::download(new DepositosExtratoExport($request), 'lista-de-extratos-depositos.xlsx');
    }
    
    public function extratoPagamento(Request $request)
    {
            
        $user = auth()->user();


        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
        
        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect()->route('mc.bloquear-caixa');
        }

        $estudante = Matricula::with(['admissao.preinscricao'])->find($request->codigo_matricula);
        $candidato = PreInscricao::find($request->candidato_id);
        
        if($request->codigo_matricula!=NULL){
            if(blank($estudante)){
                return redirect()->back()->withErrors('Não foi possível encontrar o estudante com o nº : ' . $request->codigo_matricula.'! Informe o nº de candidatura ');
            }
        }elseif($request->candidato_id!=NULL){
            if(blank($candidato)){
                return redirect()->back()->withErrors('Não foi possível encontrar o estudante com o nº : ' . $request->candidato_id.'! Informe o nº de matricula caso tenha ');
            }
        }

        if($estudante){
            $request->codigo_matricula = $estudante->admissao->preinscricao->Codigo??NULL;
        }elseif($candidato){
            $request->codigo_matricula = $candidato->Codigo; 
        }

        if ($request->data_inicio) {
            $request->data_inicio = $request->data_inicio;
        } else {
            $request->data_inicio = date("Y-m-d");
        }

        // dd($request->codigo_matricula);

        $validacao = Grupo::where('designacao', "Validação de Pagamentos")->select('pk_grupo')->first();
        $admins = Grupo::where('designacao', 'Administrador')->select('pk_grupo')->first();
        $finans = Grupo::where('designacao', 'Area Financeira')->select('pk_grupo')->first();
        $tesous = Grupo::where('designacao', 'Tesouraria')->select('pk_grupo')->first();

        if(auth()->user()->hasRole(['Gestor de Caixa'])){

            $data['items'] = Pagamento::with('factura.matriculas.admissao.preinscricao', 'preinscricao.curso', 'operador_novos','operador_antigo','utilizadores')
                ->when($request->data_inicio, function ($query, $value) {
                    $query->where('created_at', '>=', Carbon::parse($value));
                })
                ->when($request->data_final, function ($query, $value) {
                    $query->where('created_at', '<=', Carbon::parse($value));
                })->when($request->codigo_matricula, function($query, $value) {
                    $query->where('Codigo_PreInscricao', $value);
                })->when($request->operador, function ($query, $value) {
                    $query->where('fk_utilizador', $value);
                })
                ->where('forma_pagamento', 6)
                ->orderBy('tb_pagamentos.Codigo', 'desc')
                ->paginate(15)
                ->withQueryString();

            $data['utilizadores'] = GrupoUtilizador::whereIn('fk_grupo', [$admins->pk_grupo, $validacao->pk_grupo, $finans->pk_grupo, $tesous->pk_grupo])->with('utilizadores')->get();
        } elseif(auth()->user()->hasRole(['Supervisor'])){
            $data['items'] = Pagamento::with('factura.matriculas.admissao.preinscricao', 'preinscricao.curso','operador_novos','operador_antigo','utilizadores')
                ->when($request->data_inicio, function ($query, $value) {
                    $query->where('created_at', '>=', Carbon::parse($value));
                })
                ->when($request->data_final, function ($query, $value) {
                    $query->where('created_at', '<=', Carbon::parse($value));
                })
                ->when($request->operador, function ($query, $value) {
                    $query->where('fk_utilizador', $value);
                })
                ->when($request->codigo_matricula, function($query, $value) {
                    $query->where('Codigo_PreInscricao', $value);
                })
                ->where('fk_utilizador', $user->codigo_importado)
                ->where('forma_pagamento', 6)
                ->orderBy('tb_pagamentos.Codigo', 'desc')
                ->paginate(15)
                ->withQueryString();

            $data['utilizadores'] = GrupoUtilizador::whereHas('utilizadores', function ($query) {
                $query->where('codigo_importado', auth()->user()->codigo_importado);
            })->whereIn('fk_grupo', [$validacao->pk_grupo, $finans->pk_grupo, $tesous->pk_grupo])->with('utilizadores')->get();
        }

        $data['ano_lectivos'] = AnoLectivo::orderBy('ordem', 'desc')->get();
        
        
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
        
        return Inertia::render('Relatorios/FechoCaixa/Extrato-Pagamentos', $data);
    }
    
    public function pdf(Request $request)
    {
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect()->route('mc.bloquear-caixa');
        }

        $estudante = Matricula::with(['admissao.preinscricao'])->find($request->codigo_matricula);
        $candidato = PreInscricao::find($request->candidato_id);
        
        if($request->codigo_matricula!=NULL){
            if(blank($estudante)){
                return redirect()->back()->withErrors('Não foi possível encontrar o estudante com o nº : ' . $request->codigo_matricula.'! Informe o nº de candidatura ');
            }
        }elseif($request->candidato_id!=NULL){
            if(blank($candidato)){
                return redirect()->back()->withErrors('Não foi possível encontrar o estudante com o nº : ' . $request->candidato_id.'! Informe o nº de matricula caso tenha ');
            }
        }

        if($estudante){
            $request->codigo_matricula = $estudante->admissao->preinscricao->Codigo??NULL;
        }elseif($candidato){
            $request->codigo_matricula = $candidato->Codigo; 
        }
        
        if($request->data_inicio){
            $request->data_inicio = $request->data_inicio;
        }else{
            $request->data_inicio = date("Y-m-d");
        }   
        $data['items'] = Pagamento::when($request->data_inicio, function($query, $value){
            $query->where('DataRegisto', '>=' ,Carbon::parse($value) );
        })
        // ->when($request->data_final, function($query, $value){
        //     $query->where('DataRegisto', '<=' ,Carbon::parse($value));
        // })
        ->when($request->codigo_matricula, function($query, $value) {
            $query->where('tb_pagamentos.Codigo_PreInscricao', $value);
        })->when($request->operador, function ($query, $value) {
            $query->where('fk_utilizador', $value);
        })
        ->leftjoin('factura', 'tb_pagamentos.codigo_factura', '=', 'factura.Codigo')
        ->leftjoin('tb_preinscricao', 'tb_pagamentos.Codigo_PreInscricao', '=', 'tb_preinscricao.Codigo')
        ->where('forma_pagamento', 6)
        ->with(['operador_novos'])
        ->get();
        
        $data['requests'] = $request->all('data_inicio', 'data_final');

        $data['ano_lectivo'] = AnoLectivo::where('Codigo', $request->ano_lectivo)->first();
        $data['operador'] =  Utilizador::where('codigo_importado', auth()->user()->codigo_importado)->first();

        
        $pdf = \App::make('dompdf.wrapper');
        $pdf->loadView('Relatorios.listagem-pagamentos-extrato', $data);
        $pdf->getDOMPdf()->set_option('isPhpEnabled', true);
        
        return $pdf->stream();

    }


    public function extratoDetalhesPagamento($id)
    {
        $pagamento = Pagamento::leftjoin('tb_preinscricao', 'tb_pagamentos.Codigo_PreInscricao', '=', 'tb_preinscricao.Codigo')
            ->leftjoin('tb_admissao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
            ->leftjoin('tb_matriculas', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
            ->leftjoin('tb_cursos', 'tb_matriculas.Codigo_Curso', '=', 'tb_cursos.Codigo')
            ->leftjoin('factura', 'tb_pagamentos.codigo_factura', '=', 'factura.Codigo')
            ->leftjoin('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', '=', 'tb_pagamentos.AnoLectivo')
            ->where('tb_pagamentos.estado', 1)
            ->orderBy('tb_pagamentos.Codigo', 'desc')
            ->select('factura.Codigo as fact_codigo', 'factura.ValorAPagar', 'factura.DataFactura', 'tb_pagamentos.AnoLectivo', 'tb_pagamentos.codigo_factura', 'tb_pagamentos.Codigo', 'tb_pagamentos.valor_depositado', 'tb_pagamentos.DataRegisto', 'tb_pagamentos.estado', 'tb_pagamentos.nome_documento', 'tb_pagamentos.updated_at', 'Nome_Completo', 'tb_pagamentos.Totalgeral', 'DataRegisto', 'tb_matriculas.Codigo AS matricula', 'tb_cursos.Designacao AS curso')
            ->findOrFail($id);

        if ($pagamento->AnoLectivo >= 2 and $pagamento->AnoLectivo <= 15) {
            $pagamento_itens = PagamentoItems::with('mes', 'servico')
                ->where('Codigo_Pagamento', $pagamento->Codigo)
                ->get();
        } else {
            $pagamento_itens = PagamentoItems::with('mes_temps', 'servico')
                ->where('Codigo_Pagamento', $pagamento->Codigo)
                ->get();
        }

        $data['pagamento'] = $pagamento;
        $data['items'] = $pagamento_itens;
        $data['operador'] =  Utilizador::where('codigo_importado', request()->operador ?? auth()->user()->codigo_importado)->first();

        
        $pdf = \App::make('dompdf.wrapper');
        $pdf->loadView('Relatorios.extrato-detalhes-pagamentos', $data);
        $pdf->getDOMPdf()->set_option('isPhpEnabled', true);
        return $pdf->stream();

        // return response()->json(['data' => $pagamento,'items' => $pagamento_itens], 200);
    }
    
    public function excel(Request $request)
    {
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect()->route('mc.bloquear-caixa');
        }
        
        return Excel::download(new PagamentosExtratoExport($request), 'lista-de-pagamentos-extrato.xlsx');
    }
    

}
