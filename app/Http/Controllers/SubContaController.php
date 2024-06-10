<?php

namespace App\Http\Controllers;

use App\Services\TraitChavesEmpresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class SubContaController extends Controller
{
    use TraitChavesEmpresa;
    
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function index(Request $request)
    {
        
        $aplicacao_name = ENV('APLICATION_NAME'); //ropria
        
        $response = Http::get("http://10.10.50.37:8080/api/listar-subconta?tipo_aplicacao={$aplicacao_name}");
    
        if ($response->successful()) {
            // $data = $response->json();
            $data = $response->json(null, false);
            // Processe $data conforme necessário
        } else {
            // Log de erro e depuração
            Log::error('Erro ao acessar API', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            dd('Erro na solicitação HTTP', $response->status(), $response->body());
        }
 
        $data['sub_contas'] = $data;
        
        return Inertia::render('SubContas/Index', $data);
    }
    
    
    public function create()
    {
        $response = Http::get("http://10.10.50.37:8080/api/listar-contas?empresa_id=1");
        
        if ($response->successful()) {
            // $data = $response->json();
            $data = $response->json(null, false);
            // Processe $data conforme necessário
        } else {
            // Log de erro e depuração
            Log::error('Erro ao acessar API', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            dd('Erro na solicitação HTTP', $response->status(), $response->body());
        }
        
        $data['contas'] = $data;
                
        // $data['subcontas'] = SubConta::with(['empresa', 'conta'])->orderBy('id', 'asc')->get();
    
        return Inertia::render('SubContas/Create', $data);
    }

    public function store(Request $request)
    {
    
        $aplicacao_name = ENV('APLICATION_NAME');
        
        $response = Http::post("http://10.10.50.37:8080/api/criar-subconta", 
            [
                'empresa_id'=>1,
                'conta_id' => $request->conta_id,
                'tipo'=> $request->tipo ,
                'numero'=> $request->numero,
                'descricao'=> $request->designacao,
                'designacao'=> $request->designacao,
                'tipo_instituicao'=> $aplicacao_name
            ]);
            
        if ($response->successful()) {
            $user = $response->object();
            // Process the $user object as needed
            // dd($user);
        } else {
            // Log de erro e depuração
            Log::error('Erro ao criar usuário', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            dd('Erro na solicitação HTTP', $response->status(), $response->body());
        }
  
        return redirect()->back();
     
    }
    
    public function edit($id)
    {
        $response = Http::get("http://10.10.50.37:8080/api/editar-subconta/{$id}");
        
        if ($response->successful()) {
            // $data = $response->json();
            $data = $response->json(null, false);
            // Processe $data conforme necessário
        } else {
            // Log de erro e depuração
            Log::error('Erro ao acessar API', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            dd('Erro na solicitação HTTP', $response->status(), $response->body());
        }
        
        $response_contas = Http::get("http://10.10.50.37:8080/api/listar-contas?empresa_id=1");
        
        if ($response_contas->successful()) {
            // $data = $response->json();
            $data_contas = $response_contas->json(null, false);
            // Processe $data conforme necessário
        } else {
            // Log de erro e depuração
            Log::error('Erro ao acessar API', [
                'status' => $response_contas->status(),
                'body' => $response_contas->body(),
            ]);
            dd('Erro na solicitação HTTP', $response_contas->status(), $response_contas->body());
        }
        
        $data['contas'] = $data_contas;
        $data['subconta'] = $data;
       
        return Inertia::render('SubContas/Edit', $data);
    }

    public function update(Request $request, $id)
    {
        $response = Http::put("http://10.10.50.37:8080/api/update-subconta/{$id}", [
            'empresa_id' => 1,
            'conta_id' => $request->conta_id,
            'tipo' => $request->tipo ,
            'numero' => $request->numero,
            'estado' => $request->estado,
            'descricao' => $request->designacao,
            'designacao' => $request->designacao,
        ]);
        
        if ($response->successful()) {
            $user = $response->object();
            // Process the $user object as needed
        } else {
            // Log de erro e depuração
            Log::error('Erro ao atualizar usuário', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            dd('Erro na solicitação HTTP', $response->status(), $response->body());
        }
        
        return redirect()->back();
    }

}
