<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Services\TraitChavesEmpresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use phpseclib\Crypt\RSA;

class ContaController extends Controller
{
    use TraitChavesEmpresa;
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
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
        
        return Inertia::render('Contas/Index', $data);
    }
}
