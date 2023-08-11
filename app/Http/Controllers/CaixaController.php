<?php

namespace App\Http\Controllers;

use App\Exports\ListagemTodosMovimentoExport;
use App\Models\Caixa;
use App\Models\Grupo;
use App\Models\GrupoUtilizador;
use App\Models\User;
use App\Models\Utilizador;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class CaixaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
        
    public function index()
    {
        $caixas = Caixa::with('operador')->get();
        
        return Inertia::render('Operacoes/Caixa/Index', $caixas);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required',
        ], [
            'nome.required' => "A designação do Caixa é Obrigatório!",
        ]);
        
        try {
            $create = Caixa::create([
                'nome' => $request->nome,
                'nome' => 'fechado',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Não foi possível registar este caixa!',
            ]);
        }
       
        return redirect()->back();
    }


    public function update(Request $request)
    {
        $request->validate([
            'nome' => 'required',
        ], [
            'nome.required' => "A designação do Caixa é Obrigatório!",
        ]);
        
        try {
            $create = Caixa::create([
                'nome' => $request->nome,
                'nome' => 'fechado',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Não foi possível registar este caixa!',
            ]);
        }
       
        return redirect()->back();

    }
    
          
    public function excel(Request $request)
    {
        return Excel::download(new ListagemTodosMovimentoExport($request), 'listagem-de-todos-movimentos.xlsx');
    }
    
    public function pdf(Request $request)
    {
        $data['items'] = Caixa::when($request->data_inicio, function($query, $value){
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
    
}
