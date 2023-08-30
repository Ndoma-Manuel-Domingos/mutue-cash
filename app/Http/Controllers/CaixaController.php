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
use Illuminate\Support\Facades\DB;

class CaixaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
        
    public function index()
    {
        $data['items'] = Caixa::with('operador_que_abriu')->paginate(15);

        $data['total_geral'] = Caixa::with('operador')->count();
        
        return Inertia::render('Operacoes/Caixas/Index', $data);
    }

    public function show($id)
    {
        $caixa = Caixa::find($id);
        
        return response()->json($caixa, 200);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|unique:tb_caixas,nome,except,codigo',
        ], [
            'nome.required' => "A designação do Caixa é Obrigatório!",
            'nome.unique' => "A designação do Caixa que informou já se encontra registado!",
        ]);

        DB::beginTransaction();
        
        try {
            $create = Caixa::create([
                'nome' => strtoupper($request->nome),
                'status' => 'fechado',
                'code' => NULL,
                'bloqueio' => 'N',
                'operador_id' => NULL,
                'created_by' => auth()->user()->codigo_importado,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => 'Não foi possível registar este caixa!',
            ]);
        }

        DB::commit();
       
        return response()->json([
            'message' => 'Operação realizada com sucesso!',
            'data' => $create
        ]);
    }


    public function update(Request $request)
    {

        $caixa = Caixa::find($request->codigo);
        
        $request->validate([
            'nome' => 'required',
        ], [
            'nome.required' => "A designação do Caixa é Obrigatório!",
        ]);

        DB::beginTransaction();
        
        try {
            $update = $caixa->update([
                'nome' => strtoupper($request->nome),
                'status' => $request->status,
                'code' => $request->code,
                'bloqueio' => $request->bloqueio,
                'operador_id' => NULL,
                'created_by' => auth()->user()->codigo_importado,
                'updated_at' => Carbon::now()
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['message' => 'Não foi possível editar este caixa! - '.$th->getMessage()]);
        }

        DB::commit();
       
        return response()->json([
            'message' => 'Operação realizada com sucesso!',
            'data' => $update
        ]);
    }


    public function destroy($id)
    {
        $caixa = Caixa::find($id);

        DB::beginTransaction();

        try {
            $delete=$caixa->delete();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Não foi possível editar este caixa!']);
        }

        DB::commit();

        return response()->json([
            'message' => 'Operação realizada com sucesso!',
            'data' => $delete
        ]);
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
