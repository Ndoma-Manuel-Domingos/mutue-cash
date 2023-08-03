<?php

namespace App\Http\Controllers;

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

class MovimentoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
        
    public function abertura()
    {
        $movimento = MovimentoCaixa::with('operador', 'caixa')->where('operador_id', Auth::user()->codigo_importado)
        ->where('status', 'aberto')
        ->first();
        
        $caixas = Caixa::where('status', 'fechado')->get();
        
        $ultimo_movimento = MovimentoCaixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'fechado')->where('status_admin', 'validado')->latest()->first();
    
        $header = [
            "caixas" => $caixas,
            "movimento" => $movimento,
            "ultimo_movimento" => $ultimo_movimento,
        ];
        
        return Inertia::render('Operacoes/Movimentos/Abertura', $header);
    }
    
    public function aberturaStore(Request $request)
    {
        $request->validate([
            'caixa_id' => 'required',
            'valor_inicial' => 'required|numeric',

        ], [
            'caixa_id.required' => "Caixa Invalido!",
            'valor_inicial.required' => "Valor de abertura invalido!",
            'valor_inicial.numeric' => "Valor da abertura do caixa deve serve um valor númerico!",
        ]);
        
        $verificar = MovimentoCaixa::where('operador_id', Auth::user()->codigo_importado)
        ->where('status', 'aberto')
        ->first();
        
        if(!$verificar){
        
            $caixa = Caixa::findOrFail($request->caixa_id);
            
            $create = MovimentoCaixa::create([
                'caixa_id' => $caixa->codigo,
                'operador_id' => Auth::user()->codigo_importado,
                'operador_admin_id' => NULL,
                'valor_abertura' => $request->valor_inicial,
                'valor_arrecadado_total' => $request->valor_inicial,
                'valor_arrecadado_depositos' => 0,
                'valor_arrecadado_pagamento' => 0,
                'status' => 'aberto',
                'status_admin' => 'pendente',
                'created_by' => Auth::user()->codigo_importado,
                'updated_by' => Auth::user()->codigo_importado,
                'deleted_by' => Auth::user()->codigo_importado,
            ]);
            
            $caixa->status = "aberto";
            $caixa->created_by = Auth::user()->codigo_importado;
            $caixa->update();
            
            return redirect()->back();
        }
       
        
        return response()->json([
            'message' => 'Não foi possíve fazer abertura do caixa!',
        ]);

    }
    
    public function fecho()
    {
        $caixas = Caixa::where('created_by', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
        
        $movimento = null;
        
        if($caixas){
            $movimento = MovimentoCaixa::where('caixa_id', $caixas->codigo)
            ->where('operador_id', Auth::user()->codigo_importado)
            ->where('status', 'aberto')
            ->first();
        }
        
        $header = [
            "caixa" => $caixas,
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
            'valor_depositado' => 'required|numeric',
            'valor_pagamento' => 'required|numeric',
            'valor_abertura' => 'required|numeric',
        ], [
            'operador_id.required' => "Operador Invalido!",
            'operador_id.required' => "Operador Invalido!",
            'valor_depositado.required' => "Valor Invalido!",
            'valor_depositado.numeric' => "Valor deve serve um valor númerico!",
            'valor_pagamento.required' => "Valor Invalido!",
            'valor_pagamento.numeric' => "Valor deve serve um valor númerico!",
            'valor_abertura.required' => "Valor Invalido!",
            'valor_abertura.numeric' => "Valor deve serve um valor númerico!",
        ]);
        
        $movimento = MovimentoCaixa::findOrFail($request->movimento_id);
        
        $movimento->valor_abertura = $movimento->valor_abertura;
        $movimento->valor_arrecadado_total = $movimento->valor_arrecadado_total;
        $movimento->valor_arrecadado_depositos = $movimento->valor_arrecadado_depositos;
        $movimento->valor_arrecadado_pagamento = $movimento->valor_arrecadado_pagamento;
        $movimento->status = "fechado";
        $movimento->update();
        
        $caixa = Caixa::findOrFail($movimento->caixa_id);
        $caixa->status = "fechado";
        $caixa->created_by = NULL;
        $caixa->update();
        
        // Retorne a resposta em JSON
        return response()->json([
            'message' => 'Caixa fechado com sucesso!',
            'data' => $movimento
        ]);
        
    }
    
    public function imprimir(Request $request)
    {
        
    }
    
    public function validarFechoCaixa(Request $request)
    {
    
        $validacao = Grupo::where('designacao', "Validação de Pagamentos")->select('pk_grupo')->first();
        $admins = Grupo::where('designacao', 'Administrador')->select('pk_grupo')->first();
        $finans = Grupo::where('designacao', 'Area Financeira')->select('pk_grupo')->first();
        $tesous = Grupo::where('designacao', 'Tesouraria')->select('pk_grupo')->first();
        
        $utilizadores = GrupoUtilizador::whereIn('fk_grupo', [$admins->pk_grupo, $validacao->pk_grupo, $finans->pk_grupo, $tesous->pk_grupo])
        ->with('utilizadores')
        ->get();
            
        $movimentos = MovimentoCaixa::when($request->data_inicio, function($query, $value){
            $query->where('created_at', '>=' ,Carbon::parse($value) );
        })->when($request->data_final, function($query, $value){
            $query->where('created_at', '<=' ,Carbon::parse($value));
        })->when($request->operador, function($query, $value){
            $query->where('operador_id', $value);
        })->with(['operador', 'caixa'])->where('status', 'fechado')
        ->orderBy('codigo', 'desc')
        ->paginate(10)
        ->withQueryString();
        
        $header = [
            "items" => $movimentos,
            "utilizadores" => $utilizadores,
            "operador" => Utilizador::where('codigo_importado', Auth::user()->codigo_importado)->first()
        ];
    
        return Inertia::render('Operacoes/Movimentos/ValidarFechoCaixa', $header);
    }
    
    public function validarFechoCaixaAdmin($id)
    {
        $movimento = MovimentoCaixa::findOrFail($id);
        $movimento->status_admin = 'validado';
        $movimento->operador_admin_id = Auth::user()->codigo_importado;
        $movimento->update();
        
        return response()->json($movimento);
        
    }
    
    public function cancelarFechoCaixaAdmin($id)
    {
        $movimento = MovimentoCaixa::findOrFail($id);
        $movimento->status_admin = 'nao validado';
        $movimento->operador_admin_id = Auth::user()->codigo_importado;
        $movimento->update();
        
        return response()->json($movimento);
    }
    
    public function confirmarSenhaAdmin($password)
    {
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

        
}
