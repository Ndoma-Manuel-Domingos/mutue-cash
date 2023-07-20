<?php

namespace App\Http\Controllers;

use App\Models\AnoLectivo;
use App\Models\Grupo;
use App\Models\GrupoUtilizador;
use App\Models\Pagamento;
use App\Models\TipoServico;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class RelatorioController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function fechoCaixaOperador(Request $request)
    {
        $user = auth()->user();
        
        $ano = AnoLectivo::where('status', '1')->first();
        
        if(!$request->ano_lectivo){
            $request->ano_lectivo = $ano->Codigo;
        }
        
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
        ->leftjoin('tb_preinscricao', 'tb_pagamentos.Codigo_PreInscricao', '=', 'tb_preinscricao.Codigo')
        ->leftjoin('tb_admissao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
        ->leftjoin('tb_matriculas', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
        ->leftjoin('tb_cursos', 'tb_matriculas.Codigo_Curso', '=', 'tb_cursos.Codigo')
        ->leftjoin('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', '=', 'tb_pagamentos.Codigo')
        ->leftjoin('tb_tipo_servicos', 'tb_pagamentosi.Codigo_Servico', '=', 'tb_tipo_servicos.Codigo')
        ->where('fk_utilizador', $user->codigo_importado)
        ->orderBy('tb_pagamentos.Codigo', 'desc')
        ->select('tb_pagamentos.Codigo', 'Nome_Completo', 'Totalgeral', 'DataRegisto', 'tb_matriculas.Codigo AS matricula', 'tb_cursos.Designacao AS curso', 'tb_tipo_servicos.Descricao AS servico')
        ->paginate(10)
        ->withQueryString();
        
        $data['ano_lectivos'] = AnoLectivo::orderBy('ordem', 'desc')->get();
        $data['servicos'] = TipoServico::when($request->ano_lectivo, function($query, $value){
            $query->where('codigo_ano_lectivo', $value);
        })->get();
         // utilizadores validadores
        // utilizadores adiministrativos
        // utilizadores área financeira
        // utilizadores tesouraria
        $validacao = Grupo::where('designacao', "Validação de Pagamentos")->select('pk_grupo')->first();
        $admins = Grupo::where('designacao', 'Administrador')->select('pk_grupo')->first();
        $finans = Grupo::where('designacao', 'Area Financeira')->select('pk_grupo')->first();
        $tesous = Grupo::where('designacao', 'Tesouraria')->select('pk_grupo')->first();

        $data['utilizadores'] = GrupoUtilizador::whereIn('fk_grupo', [$validacao->pk_grupo, $finans->pk_grupo, $tesous->pk_grupo])->with('utilizadores')->get();

        return Inertia::render('Relatorios/FechoCaixa/Operador', $data);
    }
    
    
    public function pdf()
    {
    
    }
    
    public function excel()
    {
    
    }
    

}
