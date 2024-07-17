<?php

namespace App\Http\Controllers;

use App\Exports\ListagemTodosMovimentoExport;
use App\Jobs\JobValidacaoCaixaOperadorNotificacao;
use App\Models\Caixa;
use App\Models\Deposito;
use App\Models\Grupo;
use App\Models\GrupoUtilizador;
use App\Models\MovimentoCaixa;
use App\Models\Pagamento;
use App\Models\User;
use App\Models\Utilizador;
use App\Notifications\AberturaCaixaNotification;
use App\Notifications\FechoCaixaNotification;
use App\Notifications\RejeicaoNotification;
use App\Notifications\ValicaoSucessoNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class MovimentoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
        
    public function diariosOperador()
    {
        $user = auth()->user();
        
        $notifactions = $user->notifications; 
        
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
        
        $movimento = null;
        
        if($caixa){
            $movimento = MovimentoCaixa::with('operador', 'caixa')
            ->where('caixa_id', $caixa->codigo)
            ->where('operador_id', $caixa->operador_id)
            ->where('status', 'aberto')
            ->first();
        }
        
        
        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect('/movimentos/bloquear-caixa');
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
        
        
        return Inertia::render('Operacoes/Movimentos/Diaro-Operador', $header);
    }    
    
    public function caixasAbertos()
    {
        $user = auth()->user();
        
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
        
        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect('/movimentos/bloquear-caixa');
            
        }
        
        $data_inicio = date("Y-m-d");
        
        $movimentos = MovimentoCaixa::with(['operador_created', 'operador', 'caixa'])->where('status', 'aberto')->where('created_at', '>=' ,Carbon::parse($data_inicio))->get();
           
    
        $header = [
            "movimentos" => $movimentos,
            "operador" => $user
        ];
        
        
        return Inertia::render('Operacoes/Movimentos/Caixas-Abertos', $header);
    } 
        
    public function abertura()
    {
    
        $user = auth()->user();
        
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
        
        $movimento = null;
        
        if($caixa){
            $movimento = MovimentoCaixa::with('operador', 'caixa')
            ->where('caixa_id', $caixa->codigo)
            ->where('operador_id', Auth::user()->codigo_importado)
            ->where('status', 'aberto')
            ->first();
        }
        
        
        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect('/movimentos/bloquear-caixa');
        }
        
        $caixas = Caixa::where('status', 'fechado')->get();
        
        $ultimo_movimento = MovimentoCaixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'fechado')->where('status_admin', 'validado')->latest()->first();
                
        $utilizadores = User::whereIn('user_pertence', ['Cash','Finance-Cash', 'Todos'])
        ->with('roles')
        // ->whereIn('pk_utilizador', $array_utilizadores)
        ->where('active_state', 1)
        ->get();
    
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
        
        $verificar = Caixa::where('operador_id', $request->operador_id)->where('status', 'aberto')->first();
        
        $caixa = Caixa::findOrFail($request->caixa_id);
      
        if(filled($verificar)){
        
            $caixa_aberto = $verificar ? Caixa::findOrFail($verificar->caixa_id) : null;

            return redirect()->back()->with('error', 'o operador que pretendes associar o '.$caixa->nome.', já está associado ao '.$caixa_aberto->nome.' que não foi ainda encerrado');
        }else {
            
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
                'data_at' => date("Y-m-d"),
                'created_by' => Auth::user()->codigo_importado,
                'updated_by' => $request->operador_id,
                'deleted_by' => $request->operador_id,
            ]);
            
            $caixa->status = "aberto";
            $caixa->operador_id = $request->operador_id;
            $caixa->created_by = Auth::user()->codigo_importado;
            $caixa->code = $this->gerarNumeroUnico();
            $caixa->update();
            
            $user->notify(new AberturaCaixaNotification($create));
              
            return redirect()->back();
        }

      
    }
    
    public function fechoAdmin(Request $request)
    {
        $movimento = MovimentoCaixa::find($request->url_caixa_fecho);
    
        $caixa = Caixa::find($movimento->caixa_id);
       
        $header = [
            "caixa" => $caixa,
            "movimento" => $movimento,
            "operador" => Utilizador::where('codigo_importado', Auth::user()->codigo_importado)->first()
        ];
        
        return Inertia::render('Operacoes/Movimentos/Fecho', $header);
    }
        
    public function fecho(Request $request)
    {

        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect('/movimentos/bloquear-caixa');
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
    
        $user = auth()->user();
        
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
        $movimento->status_final = "concluido";
        $movimento->data_fecho = date("Y-m-d");
        $movimento->observacao = $request->observacao;
        $movimento->update();
        
        $caixa = Caixa::findOrFail($movimento->caixa_id);
        $caixa->status = "fechado";
        $caixa->created_by = NULL;
        $caixa->operador_id = NULL;
        $caixa->code = NULL;
        $caixa->update();
        
        $depositos = Deposito::where('status', 'pendente')->where('caixa_id', $movimento->caixa_id)->where('created_by', $user->codigo_importado)->get();
        $pagamentos = Pagamento::where('status_pagamento', 'pendente')->where('caixa_id', $movimento->caixa_id)->where('fk_utilizador', $user->codigo_importado)->get();
        
        foreach($depositos as $deposito){
            $update = Deposito::findOrFail($deposito->codigo);
            $update->status = 'concluido';
            $update->update();
        }
        
        foreach($pagamentos as $pagamento){
            $update = Pagamento::findOrFail($pagamento->Codigo);
            $update->status_pagamento = 'concluido';
            $update->update();
        }
        
        $user->notify(new FechoCaixaNotification($movimento));
        
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
            return redirect('/movimentos/bloquear-caixa');
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
            return redirect('/movimentos/bloquear-caixa');
        }
        
        $validacao = Grupo::where('designacao', "Validação de Pagamentos")->select('pk_grupo')->first();
        $admins = Grupo::where('designacao', 'Administrador')->select('pk_grupo')->first();
        $finans = Grupo::where('designacao', 'Area Financeira')->select('pk_grupo')->first();
        $tesous = Grupo::where('designacao', 'Tesouraria')->select('pk_grupo')->first();
        
        $caixas = Caixa::get();
                    
        if(auth()->user()->hasRole(['Gestor de Caixa']))
        {
            if($request->data_inicio){
                $request->data_inicio = $request->data_inicio;
            }else{
                $request->data_inicio = date('Y-m-d');
            }
            
            $movimentos = MovimentoCaixa::when($request->data_inicio, function($query, $value){
                $query->whereDate('data_at', '>=', Carbon::createFromDate($value));
            })
            ->when($request->data_final, function($query, $value){
                $query->whereDate('data_at', '<=', Carbon::createFromDate($value));
            })
            ->when($request->operador, function($query, $value){
                $query->where('operador_id', $value);
            })
            ->when($request->caixa_id, function($query, $value){
                $query->where('caixa_id', $value);
            })
            ->with(['operador', 'caixa'])
            ->where('status', 'fechado')
            ->orderBy('codigo', 'desc')
            ->paginate(10)
            ->withQueryString();        
        }
        
        if(auth()->user()->hasRole(['Operador Caixa', 'Supervisor']))
        {
        
            if($request->data_inicio){
                $request->data_inicio = $request->data_inicio;
            }else{
                $request->data_inicio = date('Y-m-d');
            }
                
            $movimentos = MovimentoCaixa::when($request->data_inicio, function($query, $value){
                $query->whereDate('data_at', '>=', Carbon::createFromDate($value));
            })
            ->when($request->data_final, function($query, $value){
                $query->whereDate('data_at', '<=', Carbon::createFromDate($value));
            })
            ->when($request->operador, function($query, $value){
                $query->where('operador_id', $value);
            })
            ->when($request->caixa_id, function($query, $value){
                $query->where('caixa_id', $value);
            })
            ->with(['operador', 'caixa'])
            ->where('status', 'fechado')
            ->orderBy('codigo', 'desc')
            ->paginate(10)
            ->withQueryString(); 
        }
            
               
        if(auth()->user()->hasRole(['Gestor de Caixa', 'Supervisor'])){
            $utilizadores = GrupoUtilizador::whereIn('fk_grupo', [$validacao->pk_grupo, $finans->pk_grupo, $tesous->pk_grupo])->orWhere('fk_utilizador', Auth::user()->pk_utilizador)->with('utilizadores')->get();
        }
        
        if(auth()->user()->hasRole(['Operador Caixa'])){
            $utilizadores = GrupoUtilizador::whereHas('utilizadores', function ($query) {
                $query->where('codigo_importado', auth()->user()->codigo_importado);
            })->whereIn('fk_grupo', [$validacao->pk_grupo, $finans->pk_grupo, $tesous->pk_grupo])->with('utilizadores')->get();
        }
            
    
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
        // $user = auth()->user();   
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect('/movimentos/bloquear-caixa');
        }

        $movimento = MovimentoCaixa::findOrFail($id);
        $user = User::where('codigo_importado', $movimento->operador_id)->first();
        $pessoa = DB::table('tb_pessoa')->where('pk_pessoa', json_decode($user->ref_pessoa,true)['pk']??Null)->first();
        $dados_caixa = Caixa::where('operador_id', $movimento->operador_id)->first();
        
        try {
            $movimento->status_admin = 'validado';
            $movimento->data_validacao = date("Y-m-d");
            $movimento->operador_admin_id = Auth::user()->codigo_importado;
            $movimento->update();
        } catch (\Throwable $th) {
            DB::rollBack();
            return Response()->json($th->getMessage(), 201);
        }
        
        try {
            //code...
            $user->notify(new ValicaoSucessoNotification($movimento));
        } catch (\Throwable $th) {
            DB::rollBack();
            return Response()->json($th->getMessage(), 201);
        }

        try {
            $dados['email'] = $pessoa->email;
            $dados['nome_user']= $user->nome;
            $dados['caixa']= $dados_caixa->nome;
            $dados['data_abertura_caixa']= $dados_caixa->created_at;
            $dados['data_fecho_caixa']= $dados_caixa->updated_at;
            $dados['data_validacao']= $movimento->updated_at;
            $dados['descricao']= 'Validado';
            $dados['admin']= $movimento->operador_admin;
            $dados['ano']= date('Y');
            $dados['assunto']= 'Validação do Caixa do Operador';
            $dados['linkLogin'] = getenv('APP_URL');

            JobValidacaoCaixaOperadorNotificacao::dispatch($dados)->delay(now()->addSecond('5'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return Response()->json($th->getMessage(), 201);
        }
        
        return response()->json($movimento);
        
    }
    
    public function cancelarFechoCaixaAdmin($id, $motivo)
    {
        // $user = auth()->user();
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect('/movimentos/bloquear-caixa');
        }

        $movimento = MovimentoCaixa::findOrFail($id);
        $user = User::where('codigo_importado', $movimento->operador_id)->first();
        $pessoa = DB::table('tb_pessoa')->where('pk_pessoa', json_decode($user->ref_pessoa,true)['pk']??Null)->first();
        $dados_caixa = Caixa::where('operador_id', $movimento->operador_id)->first();
        
        try {
            $movimento->status_admin = 'nao validado';
            $movimento->motivo_rejeicao = $motivo;
            $movimento->data_validacao = date("Y-m-d");
            $movimento->operador_admin_id = Auth::user()->codigo_importado;
            $movimento->update();
        } catch (\Throwable $th) {
            DB::rollBack();
            return Response()->json($th->getMessage(), 201);
        }
        
        try {
            //code...
            $user->notify(new RejeicaoNotification($movimento));
        } catch (\Throwable $th) {
            DB::rollBack();
            return Response()->json($th->getMessage(), 201);
        }

        try {
            $dados['email'] = $pessoa->email;
            $dados['nome_user']= $user->nome;
            $dados['caixa']= $dados_caixa->nome;
            $dados['data_abertura_caixa']= $dados_caixa->created_at;
            $dados['data_fecho_caixa']= $dados_caixa->updated_at;
            $dados['data_validacao']= $movimento->updated_at;
            $dados['movimento']= $movimento;
            $dados['descricao']= 'Rejeitado/Cancelado';
            $dados['moti']= 'Rejeitado/Cancelado';
            $dados['ano']= date('Y');
            $dados['assunto']= 'Validação do Caixa do Operador';
            $dados['linkLogin'] = getenv('APP_URL');

            JobValidacaoCaixaOperadorNotificacao::dispatch($dados)->delay(now()->addSecond('5'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return Response()->json($th->getMessage(), 201);
        }
        
        return response()->json($movimento);
    }
    
    public function confirmarSenhaAdmin($password)
    {
        
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect('/movimentos/bloquear-caixa');
        }
        
        $user = User::where('userName', Auth::user()->userName)->where('password', md5($password))
        ->where('active_state', 1)
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
            return redirect('/movimentos/bloquear-caixa');
        }
        
        return Excel::download(new ListagemTodosMovimentoExport($request), 'listagem-de-todos-movimentos.xlsx');
    }
    
    public function pdf(Request $request)
    {
        $user = auth()->user();
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect('/movimentos/bloquear-caixa');
        }
        
        $data['items'] = MovimentoCaixa::when($request->data_inicio, function($query, $value){
            $query->whereDate('data_at', '>=', Carbon::createFromDate($value));
        })
        ->when($request->data_final, function($query, $value){
            $query->whereDate('data_at', '<=', Carbon::createFromDate($value));
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
        
        if($request->operador_id){
            $data['assinatura'] = User::where('codigo_importado', $request->operador_id)->first();
        }else{
            $data['assinatura'] = $user;
        }
        
        $data['operador'] = User::where('codigo_importado', $request->operador_id)->first();
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
        //     return redirect('/movimentos/bloquear-caixa');
        // }
        
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
        
        if($caixa){
            $update = Caixa::findOrFail($caixa->codigo);
            $update->bloqueio = 'Y';
            $update->update();
        }else{
            return redirect('/dashboard');
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
            
                return redirect('/dashboard');
            }else{
                return redirect('/movimentos/bloquear-caixa');
            }
            
        }else{
            return redirect('/movimentos/bloquear-caixa');
        }
   
    }
    
    
    function registrarSaidas(Request $request)
    {

        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
    
        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect('/movimentos/bloquear-caixa');
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
    
        return Inertia::render('Operacoes/Movimentos/RegistrarSaida', $header);
    }    
    
    
    function gerarNumeroUnico() {
        $numero = mt_rand(1000, 9999); // Gera um número aleatório entre 1000 e 9999
        return $numero;
    }
}
