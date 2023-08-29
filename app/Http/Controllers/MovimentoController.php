<?php

namespace App\Http\Controllers;

use App\Exports\ListagemTodosMovimentoExport;
use App\Models\Caixa;
use App\Models\Grupo;
use App\Models\GrupoUtilizador;
use App\Models\MovimentoCaixa;
use App\Models\User;
use App\Models\Utilizador;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class MovimentoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
        
    public function abertura()
    {
        $user = auth()->user();
        
        $movimento = MovimentoCaixa::with('operador', 'caixa')
        ->where('operador_id', Auth::user()->codigo_importado)
        ->where('status', 'aberto')
        ->first();
        
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
        
        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect()->route('mc.bloquear-caixa');
        }
        
        $caixas = Caixa::where('status', 'fechado')->get();
        
        $ultimo_movimento = MovimentoCaixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'fechado')->where('status_admin', 'validado')->latest()->first();
        
        $validacao = Grupo::where('designacao', "Validação de Pagamentos")->select('pk_grupo')->first();
        $admins = Grupo::where('designacao', 'Administrador')->select('pk_grupo')->first();
        $finans = Grupo::where('designacao', 'Area Financeira')->select('pk_grupo')->first();
        $tesous = Grupo::where('designacao', 'Tesouraria')->select('pk_grupo')->first();

        $utilizadores = GrupoUtilizador::whereIn('fk_grupo', [$validacao->pk_grupo, $finans->pk_grupo, $tesous->pk_grupo])->orWhere('fk_utilizador', Auth::user()->pk_utilizador)->with('utilizadores')->get();
    
        $header = [
            "caixas" => $caixas,
            "movimento" => $movimento,
            "ultimo_movimento" => $ultimo_movimento,
            "utilizadores" => $utilizadores,
            "operador" => $user
        ];
        
        return Inertia::render('Operacoes/Movimentos/Abertura', $header);
    }
    
    public function aberturaStore(Request $request)
    {

        $request->validate([
            'caixa_id' => 'required',
            'operador_id' => 'required',
            'valor_inicial' => 'required|numeric',
        ], [
            'caixa_id.required' => "Caixa Invalido!",
            'operador_id.required' => "Operador Invalido!",
            'valor_inicial.required' => "Valor de abertura invalido!",
            'valor_inicial.numeric' => "Valor da abertura do caixa deve serve um valor númerico!",
        ]);
        
        $user = auth()->user();
        
        if($user->codigo_importado == null){
            $user->update(['codigo_importado' => $user->pk_utilizador]);
        }
        
        // $verificar = MovimentoCaixa::where('operador_id', $request->operador_id Auth::user()->codigo_importado)
        $verificar = MovimentoCaixa::where('operador_id', $request->operador_id)
        ->where('status', 'aberto')
        ->first();
        
        if(!$verificar){
        
            $caixa = Caixa::findOrFail($request->caixa_id);
            
            $create = MovimentoCaixa::create([
                'caixa_id' => $caixa->codigo,
                'operador_id' => $request->operador_id,
                'operador_admin_id' => NULL,
                'valor_abertura' => $request->valor_inicial,
                'valor_arrecadado_total' => $request->valor_inicial,
                'valor_arrecadado_depositos' => 0,
                'valor_arrecadado_pagamento' => 0,
                'status' => 'aberto',
                'status_admin' => 'pendente',
                'created_by' => Auth::user()->codigo_importado,
                'updated_by' => $request->operador_id,
                'deleted_by' => $request->operador_id,
            ]);
            
            $caixa->status = "aberto";
            $caixa->operador_id = $request->operador_id;
            $caixa->created_by = Auth::user()->codigo_importado;
            $caixa->code = $this->gerarNumeroUnico();
            $caixa->update();
            
            return redirect()->back();
        }
       
        
        return response()->json([
            'message' => 'Não foi possíve fazer abertura do caixa!',
        ]);

    }
    
    public function fecho()
    {
    
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect()->route('mc.bloquear-caixa');
        }

        $movimento = null;
        
        if($caixa){
            $movimento = MovimentoCaixa::where('caixa_id', $caixa->codigo)
            ->where('operador_id', Auth::user()->codigo_importado)
            ->where('status', 'aberto')
            ->first();
        }
        
        $header = [
            "caixa" => $caixa,
            "movimento" => $movimento,
            "operador" => Utilizador::where('codigo_importado', Auth::user()->codigo_importado)->first()
        ];
        
        return Inertia::render('Operacoes/Movimentos/Fecho', $header);
    }
    
    public function fechoStore(Request $request)
    {
        $request->validate([
            'operador_id' => 'required',
            'caixa_id' => 'required',
            'valor_depositado' => 'required',
            'valor_pagamento' => 'required',
            'valor_abertura' => 'required',
        ], [
            'operador_id.required' => "Operador Invalido!",
            'operador_id.required' => "Operador Invalido!",
            'valor_depositado.required' => "Valor Invalido!",
            'valor_pagamento.required' => "Valor Invalido!",
            'valor_abertura.required' => "Valor Invalido!",
        ]);
        
        $movimento = MovimentoCaixa::findOrFail($request->movimento_id);
        
        $movimento->valor_abertura = $movimento->valor_abertura;
        $movimento->valor_arrecadado_total = $movimento->valor_arrecadado_total;
        $movimento->valor_arrecadado_depositos = $movimento->valor_arrecadado_depositos;
        $movimento->valor_arrecadado_pagamento = $movimento->valor_arrecadado_pagamento;
        $movimento->status = "fechado";
        $movimento->observacao = $request->observacao;
        $movimento->update();
        
        $caixa = Caixa::findOrFail($movimento->caixa_id);
        $caixa->status = "fechado";
        $caixa->created_by = NULL;
        $caixa->operador_id = NULL;
        $caixa->code = NULL;
        $caixa->update();
        
        // Retorne a resposta em JSON
        return response()->json([
            'message' => 'Caixa fechado com sucesso!',
            'data' => $movimento
        ]);
        
    }
    
    public function imprimir(Request $request)
    {
        
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect()->route('mc.bloquear-caixa');
        }
        
        $data['movimento'] = MovimentoCaixa::findOrFail($request->codigo);
        
        $pdf = \App::make('dompdf.wrapper');
        $pdf->loadView('Relatorios.lista-movimentos', $data);
        $pdf->getDOMPdf()->set_option('isPhpEnabled', true);
        return $pdf->stream();
    }
    
    public function validarFechoCaixa(Request $request)
    {
        
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect()->route('mc.bloquear-caixa');
        }
        
        $validacao = Grupo::where('designacao', "Validação de Pagamentos")->select('pk_grupo')->first();
        $admins = Grupo::where('designacao', 'Administrador')->select('pk_grupo')->first();
        $finans = Grupo::where('designacao', 'Area Financeira')->select('pk_grupo')->first();
        $tesous = Grupo::where('designacao', 'Tesouraria')->select('pk_grupo')->first();
        
        $utilizadores = GrupoUtilizador::whereIn('fk_grupo', [$admins->pk_grupo, $validacao->pk_grupo, $finans->pk_grupo, $tesous->pk_grupo])
        ->with('utilizadores')
        ->get();
        
        $caixas = Caixa::get();
        
        if($request->data_inicio){
            $request->data_inicio = $request->data_inicio;
        }else{
            $request->data_inicio = date('Y-m-d');
        }
            
        $movimentos = MovimentoCaixa::when($request->data_inicio, function($query, $value){
            $query->where('updated_at', '>=' ,Carbon::parse($value) );
        })
        ->when($request->data_final, function($query, $value){
            $query->where('updated_at', '<=' ,Carbon::parse($value));
        })
        ->when($request->operador, function($query, $value){
            $query->where('operador_id', $value);
        })
        ->when($request->caixa_id, function($query, $value){
            $query->where('caixa_id', $value);
        })
        ->with(['operador', 'caixa'])->where('status', 'fechado')
        ->orderBy('codigo', 'desc')
        ->paginate(10)
        ->withQueryString();
    
        $header = [
            "items" => $movimentos,
            "caixas" => $caixas,
            "utilizadores" => $utilizadores,
            "operador" => Utilizador::where('codigo_importado', Auth::user()->codigo_importado)->first()
        ];
    
        return Inertia::render('Operacoes/Movimentos/ValidarFechoCaixa', $header);
    }
    
    public function validarFechoCaixaAdmin($id)
    {
    
        
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect()->route('mc.bloquear-caixa');
        }
        
        $movimento = MovimentoCaixa::findOrFail($id);
        $movimento->status_admin = 'validado';
        $movimento->operador_admin_id = Auth::user()->codigo_importado;
        $movimento->update();
        
        return response()->json($movimento);
        
    }
    
    public function cancelarFechoCaixaAdmin($id, $motivo)
    {
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect()->route('mc.bloquear-caixa');
        }
        
        $movimento = MovimentoCaixa::findOrFail($id);
        $movimento->status_admin = 'nao validado';
        $movimento->motivo_rejeicao = $motivo;
        $movimento->operador_admin_id = Auth::user()->codigo_importado;
        $movimento->update();
        
        return response()->json($movimento);
    }
    
    public function confirmarSenhaAdmin($password)
    {
        
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect()->route('mc.bloquear-caixa');
        }
        
        $user = User::where('userName', Auth::user()->userName)
        ->where('password', md5($password))
        ->first();
        
        if($user) {
            $data = [
                'ok' => true,
            ];
            return response()->json($data);
        }
    }
          
    public function excel(Request $request)
    {
        
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect()->route('mc.bloquear-caixa');
        }
        
        return Excel::download(new ListagemTodosMovimentoExport($request), 'listagem-de-todos-movimentos.xlsx');
    }
    
    public function pdf(Request $request)
    {
        
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect()->route('mc.bloquear-caixa');
        }
        
        $data['items'] = MovimentoCaixa::when($request->data_inicio, function($query, $value){
            $query->where('created_at', '>=' ,Carbon::parse($value) );
        })
        ->when($request->data_final, function($query, $value){
            $query->where('created_at', '<=' ,Carbon::parse($value));
        })
        ->when($request->operador_id, function($query, $value){
            $query->where('operador_id', $value);
        })
        ->when($request->caixa_id, function($query, $value){
            $query->where('caixa_id', $value);
        })
        ->with(['operador', 'caixa'])
        ->orderBy('codigo', 'desc')
        ->get();
        
        $data['requests'] = $request->all('data_inicio', 'data_final');
        $data['operador'] = User::where('codigo_importado',$request->operador_id)->first();
        $data['caixa'] = Caixa::find($request->caixa_id);
        
        $pdf = \App::make('dompdf.wrapper');
        $pdf->loadView('Relatorios.listagem-todos-movimentos', $data);
        $pdf->getDOMPdf()->set_option('isPhpEnabled', true);
        return $pdf->stream();
    }
    
    
    public function bloquearCaixa()
    {
        // $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        // if($caixa->bloqueio == 'N'){
        //     return redirect()->route('mc.bloquear-caixa');
        // }
        
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
        
        if($caixa){
            $update = Caixa::findOrFail($caixa->codigo);
            $update->bloqueio = 'Y';
            $update->update();
        }else{
            return redirect()->route('mc.dashboard');
        }
        
        $data['caixa'] = $caixa;

        return Inertia::render('Relatorios/FechoCaixa/BloqueioCaixa', $data);
    
    }
    
    
    public function bloquearCaixaStore(Request $request)
    {
        $request->validate([
            'caixa_id' => 'required',
            'code' => 'required',

        ], [
            'caixa_id.required' => "Caixa Invalido!",
            'code.required' => "Codigo Invalido invalido!",
        ]);
    
        $caixa = Caixa::findOrFail($request->caixa_id);
        
        if($caixa){
            
            if($caixa->code == $request->code){
                $caixa->bloqueio = 'N';
                $caixa->update();
            
                return redirect()->route('mc.dashboard');
            }else{
                return redirect()->route('mc.bloquear-caixa');
            }
            
        }else{
            return redirect()->route('mc.bloquear-caixa');
        }
   
    }
    
    
    function gerarNumeroUnico() {
        $numero = mt_rand(1000, 9999); // Gera um número aleatório entre 1000 e 9999
        return $numero;
    }
}
