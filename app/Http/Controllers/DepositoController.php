<?php

namespace App\Http\Controllers;

use App\Exports\DepositosExport;
use App\Models\Acesso;
use App\Models\Caixa;
use App\Models\Deposito;
use App\Models\Grupo;
use App\Models\GrupoUtilizador;
use App\Models\Matricula;
use App\Models\MovimentoCaixa;
use App\Models\Paramento;
use App\Models\Paramentro;
use App\Models\PreInscricao;
use App\Models\Utilizador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect('/movimentos/bloquear-caixa');
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
             ->with(['user', 'forma_pagamento', 'ano_lectivo', 'matricula.admissao.preinscricao','candidato'])
             ->orderBy('codigo', 'desc')
             ->paginate(15)
             ->withQueryString();
             
            $valor_deposito = Deposito::when($request->data_inicio, function($query, $value){
                $query->whereDate('data_movimento', '>=', Carbon::createFromDate($value));
            })->when($request->data_final, function($query, $value){
                $query->whereDate('data_movimento', '<=', Carbon::createFromDate($value));
            })->when($request->operador, function($query, $value){
                $query->where('created_by', $value);
            })->sum('valor_depositar');
        
        }
       
        if(auth()->user()->hasRole(['Supervisor'])){
           
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
            ->with(['user', 'forma_pagamento', 'ano_lectivo', 'matricula.admissao.preinscricao','candidato'])
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
            ->sum('valor_depositar');
            
        }
       
        if(auth()->user()->hasRole(['Operador Caixa'])){
           
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
            ->where('status', 'pendente')
            ->where('caixa_id', $caixa->codigo ?? '')
            ->where('created_by', $user->codigo_importado)
            ->with(['caixa', 'user', 'forma_pagamento', 'ano_lectivo', 'matricula.admissao.preinscricao','candidato'])
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
            ->where('status', 'pendente')
            ->where('created_by', $user->codigo_importado)
            ->sum('valor_depositar');
            
        }
       
        if(auth()->user()->hasRole(['Gestor de Caixa', 'Supervisor'])){
            $data['utilizadores'] = GrupoUtilizador::whereIn('fk_grupo', [$validacao->pk_grupo, $finans->pk_grupo, $tesous->pk_grupo])->orWhere('fk_utilizador', Auth::user()->pk_utilizador)->with('utilizadores')->get();
        }
        
        if(auth()->user()->hasRole(['Operador Caixa'])){
            $data['utilizadores'] = GrupoUtilizador::whereHas('utilizadores', function ($query) {
                $query->where('codigo_importado', auth()->user()->codigo_importado);
            })->whereIn('fk_grupo', [$validacao->pk_grupo, $finans->pk_grupo, $tesous->pk_grupo])->with('utilizadores')->get();
        }
 
        
        $data['total_depositado'] = $valor_deposito ?? NULL;
        $data['valor_a_depositar_padrao'] = Paramentro::where('Designacao', "Mutue Cash")->where('estado', '1')->first();
        
        return Inertia::render('Operacoes/Depositos/Index', $data);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'codigo_matricula' => 'required',
            'valor_a_depositar' => 'required|numeric'

        ], [
            'codigo_matricula.required' => "Codigo de matricula Invalido!",
            'valor_a_depositar.required' => "Valor a depositar Invalido!",
            'valor_a_depositar.numeric' => "Valor a depositar deve serve um valor númerico!",
        ]);
        
        
        try {
            DB::beginTransaction();
            // Realizar operações de banco de dados aqui
              
            $browser = $_SERVER['HTTP_USER_AGENT'];
            $ip = $_SERVER['REMOTE_ADDR'];
            $rotaAtual = $_SERVER['REQUEST_URI'];
             
            $caixas = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
              
            if(!$caixas){
                  return response()->json([
                      'message' => 'Sem nenhum caixa aberto para realizar o deposito!',
                  ], 401);
            }
              
            $movimento = MovimentoCaixa::where('caixa_id', $caixas->codigo)->where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
                      
            $resultado = Matricula::where('tb_matriculas.Codigo', $request->codigo_matricula)
              ->join('tb_admissao', 'tb_matriculas.Codigo_Aluno', '=', 'tb_admissao.Codigo')
              ->join('tb_preinscricao', 'tb_admissao.pre_incricao', '=', 'tb_preinscricao.Codigo')
              ->select('tb_preinscricao.Codigo','tb_preinscricao.saldo_anterior','tb_preinscricao.saldo', 'tb_preinscricao.Nome_Completo')
              ->first();
              
            $saldo_apos_movimento = $resultado->saldo + $request->valor_a_depositar;
              
            $tipo_folha_impressao = $request->factura ?? 'Ticket';
              
              // registramos o deposito 
            $create = Deposito::create([
                  'codigo_matricula_id' => $request->codigo_matricula,
                  'Codigo_PreInscricao' => $resultado->Codigo,
                  'valor_depositar' => $request->valor_a_depositar,
                  'saldo_apos_movimento' => $saldo_apos_movimento,
                  'tipo_folha' => $tipo_folha_impressao,
                  'forma_pagamento_id' => 6,
                  'caixa_id' => $caixas->codigo,
                  'status' => 'pendente',
                  'data_movimento' => date("Y-m-d"),
                  'ano_lectivo_id' => $this->anoLectivoActivo(),
                  'created_by' => Auth::user()->codigo_importado,
                  'updated_by' => Auth::user()->codigo_importado
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
                      
            $aplicacao_name = ENV('APLICATION_NAME');
              
            // conta a creditar
            $subconta_cliente = "31.1.1.{$request->codigo_matricula}";
            //conta a debitar
            $subconta_servico = "62.1.5";
            //conta a caixa ou banco
            $subconta_caixa_banco = "45.1.5";
              
            $response = Http::post("http://10.10.50.37:8080/api/criar-debito", 
            [
                  'empresa_id' => 1,
                  'valor' => $request->valor_a_depositar,
                  'tipo_instituicao' => $aplicacao_name,
                  'subconta_cliente' => $subconta_cliente,
                  'subconta_caixa_banco' => $subconta_caixa_banco,
                  'subconta_servico' => $subconta_servico,
                  'designacao' => "Pagamento de Serviço Deposito",
            ]);        
              
            // realizar movimento com contas certas depois do debito --- END       
                      
            $valor = number_format($request->valor_a_depositar, 2, ',', '.');
                      
            $descricao = "No dia " . date('d') ." do mês de " . date('M') . " no ano de " . date("Y"). " o Senhor(a) " . Auth::user()->nome . " fez um deposito para o estudante de Nome: {$preinscricao->Nome_Completo} com o número da matrícula: {$request->codigo_matricula} no valor de {$valor} as "  . date('h') ." horas " . date('i') . " minutos e " . date("s") . " segundos";
                                      
            Acesso::create([
                  'designacao' => Auth::user()->nome ,
                  'descricao' => $descricao,
                  'ip_maquina' => $ip,
                  'browser' => $browser,
                  'rota_acessado' => $rotaAtual,
                  'nome_maquina' => NULL,
                  'utilizador_id' => Auth::user()->pk_utilizador,
            ]);
  
            // Se todas as operações foram bem-sucedidas, você pode fazer o commit
            DB::commit();
        } catch (\Exception $e) {
            // Caso ocorra algum erro, você pode fazer rollback para desfazer as operações
            DB::rollback();
            return response()->json($e->getMessage(), 201);
            // Você também pode tratar o erro de alguma forma, como registrar logs ou retornar uma mensagem de erro para o usuário.
        }

        // Retorne a resposta em JSON
        return response()->json([
            'message' => 'Deposito realizado com sucesso!',
            'data' => $create,
            'tipo_folha_impressao' => $tipo_folha_impressao
        ]);
        

    }
    
    public function edit($id)
    {
        if(!auth()->user()->can(['alterar deposito'])){
            return redirect()->back();
        }
    
        $deposito = Deposito::findOrFail($id);
     
        $preinscricao = Matricula::where('tb_matriculas.Codigo', $deposito->codigo_matricula_id)
        ->join('tb_cursos', 'tb_matriculas.Codigo_Curso', '=', 'tb_cursos.Codigo')
        ->join('tb_admissao', 'tb_matriculas.Codigo_Aluno', '=', 'tb_admissao.Codigo')
        ->join('tb_preinscricao', 'tb_admissao.pre_incricao', '=', 'tb_preinscricao.Codigo')
        ->select(
            'tb_matriculas.Codigo',
            'tb_preinscricao.Codigo AS codigo_preinscricao',
            'tb_preinscricao.Nome_Completo',
            'tb_preinscricao.Bilhete_Identidade',
            'tb_preinscricao.user_id',
            'tb_preinscricao.saldo',
            'tb_preinscricao.codigo_tipo_candidatura',
            'tb_cursos.Designacao'
        )->first();
        
        $data['deposito'] = $deposito;
        $data['preinscricao'] = $preinscricao;
        
        return Inertia::render('Operacoes/Depositos/Edit', $data);
    }
    
    public function update(Request $request)
    {
        $request->validate([
            'codigo_matricula' => 'required',
            'valor_a_depositar' => 'required|numeric'

        ], [
            'codigo_matricula.required' => "Codigo de matricula Invalido!",
            'valor_a_depositar.required' => "Valor a depositar Invalido!",
            'valor_a_depositar.numeric' => "Valor a depositar deve serve um valor númerico!",
        ]);
        
        $caixas = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
        
        if(!$caixas){
            return response()->json([
                'message' => 'Sem nenhum caixa aberto para realizar o deposito!',
            ], 401);
        }
        
        $browser = $_SERVER['HTTP_USER_AGENT'];
        $ip = $_SERVER['REMOTE_ADDR'];
        $rotaAtual = $_SERVER['REQUEST_URI'];
        
        $movimento = MovimentoCaixa::where('caixa_id', $caixas->codigo)->where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
                
        $deposito = Deposito::findOrFail($request->codigo);
        
        if($request->valor_a_depositar != $deposito->valor_depositar){
                     
            $preinscricao = PreInscricao::findOrFail($deposito->Codigo_PreInscricao);
            $preinscricao->saldo = $request->valor_a_depositar;
            $preinscricao->saldo_anterior = ($preinscricao->saldo_anterior - $deposito->valor_depositar) + $request->valor_a_depositar;
               
            $preinscricao->update();
        
        }
        
        if($request->valor_a_depositar != $deposito->valor_depositar){
            
            $update = MovimentoCaixa::findOrFail($movimento->codigo);
            
            $update->valor_arrecadado_depositos = ($update->valor_arrecadado_depositos - $deposito->valor_depositar) + $request->valor_a_depositar;
            $update->valor_arrecadado_total = ($update->valor_arrecadado_total - $deposito->valor_depositar) + $request->valor_a_depositar;
    
            $update->update();
        }
        
        if($request->valor_a_depositar != $deposito->valor_depositar){
         
            $deposito->valor_depositar = $request->valor_a_depositar;
            $deposito->saldo_apos_movimento = ($deposito->saldo_apos_movimento - $deposito->valor_depositar) + $request->valor_a_depositar;
            $deposito->tipo_folha = $request->factura;
            
            $deposito->update();
            
            
            $valor = number_format($deposito->valor_depositar, 2, ',', '.');
            $valor_actual = number_format($request->valor_a_depositar, 2, ',', '.');
                
            $descricao = "No dia " . date('d') ." do mês de " . date('M') . " no ano de " . date("Y"). " o Senhor(a) " . Auth::user()->nome . " fez uma actualização no deposito do estudante de Nome: {$preinscricao->Nome_Completo} com o número da matrícula: {$deposito->codigo_matricula_id} do valor {$valor} para o valor {$valor_actual} as "  . date('h') ." horas " . date('i') . " minutos e " . date("s") . " segundos";
            
            Acesso::create([
                'designacao' => Auth::user()->nome ,
                'descricao' => $descricao,
                'ip_maquina' => $ip,
                'browser' => $browser,
                'rota_acessado' => $rotaAtual,
                'nome_maquina' => NULL,
                'utilizador_id' => Auth::user()->pk_utilizador,
            ]);
            
            
        }
        
        return response()->json([
            'message' => 'Dados actualizados com sucesso!',
            'data' => $deposito
        ]);

    }  
    
    public function pdf(Request $request)
    {
            
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect('/movimentos/bloquear-caixa');
        }

        if($request->data_inicio){
            $request->data_inicio = $request->data_inicio;
        }else{
            $request->data_inicio = date("Y-m-d");
        }

        // if($request->operador){
        //     $request->operador = $request->operador;
        // }else{
        //     $request->operador = auth()->user()->codigo_importado;
        // }

        $data['items'] = Deposito::when($request->data_inicio, function($query, $value){
            $query->where('created_at', '>=' ,Carbon::parse($value) );
        })/*->when($request->data_final, function($query, $value){
            $query->where('created_at', '<=' ,Carbon::parse($value));
        })*/
        ->when($request->operador, function($query, $value){
            $query->where('created_by', $value);
        })->with(['user', 'forma_pagamento', 'ano_lectivo', 'matricula.admissao.preinscricao', 'candidato'])
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
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect('/movimentos/bloquear-caixa');
        }

        return Excel::download(new DepositosExport($request), 'lista-de-depositos.xlsx');
    }
      
    public function imprimir(Request $request)
    {
               
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect('/movimentos/bloquear-caixa');
        }
        
        $data['item'] = Deposito::when($request->data_inicio, function($query, $value){
            $query->where('created_at', '>=' ,Carbon::parse($value) );
        })->when($request->data_final, function($query, $value){
            $query->where('created_at', '<=' ,Carbon::parse($value));
        })
        ->when($request->operador, function($query, $value){
            $query->where('created_by', $value);
        })
        ->with(['user', 'forma_pagamento', 'ano_lectivo', 'matricula.admissao.preinscricao','candidato'])
        ->findOrFail($request->codigo);
        
        $pdf = \App::make('dompdf.wrapper');
        $pdf->loadView('Relatorios.recibo', $data);
        $pdf->getDOMPdf()->set_option('isPhpEnabled', true);
        return $pdf->stream();
        
    }
    
    
    public function ticket(Request $request)
    {
        $data['item'] = Deposito::when($request->data_inicio, function($query, $value){
            $query->where('created_at', '>=' ,Carbon::parse($value) );
        })->when($request->data_final, function($query, $value){
            $query->where('created_at', '<=' ,Carbon::parse($value));
        })
        ->when($request->operador, function($query, $value){
            $query->where('created_by', $value);
        })
        ->with(['user', 'forma_pagamento', 'ano_lectivo', 'matricula.admissao.preinscricao','candidato'])
        ->findOrFail($request->codigo);
        
        
        $pdf = \App::make('dompdf.wrapper');
        $pdf->loadView('Relatorios.ticket-deposito', $data);
        $pdf->getDOMPdf()->set_option('isPhpEnabled', true);
        return $pdf->stream();
        
    }
    
    public function depositosUltimosSeisMeses(Request $request)
    {
        $user = auth()->user();
                
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect('/movimentos/bloquear-caixa');
        }
        
        $data['items'] = Deposito::when($request->data_inicio, function($query, $value){
            $query->whereDate('data_movimento', '>=', Carbon::createFromDate($value));
        })->when($request->data_final, function($query, $value){
            $query->whereDate('data_movimento', '<=', Carbon::createFromDate($value));
         })->when($request->operador, function($query, $value){
             $query->where('created_by', $value);
         })
         ->with(['user', 'forma_pagamento', 'ano_lectivo', 'matricula.admissao.preinscricao','candidato'])
         ->orderBy('codigo', 'desc')
         ->paginate(15)
         ->withQueryString();

        return Inertia::render('Operacoes/Depositos/Index-mensal', $data);
    }        
}
