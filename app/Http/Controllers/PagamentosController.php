<?php

namespace App\Http\Controllers;

use App\Exports\PagamentosExport;
use App\Models\FormaPagamento;
use App\Models\Preinscricao;
use App\Models\Factura;
use App\Models\FacturaItem;
use App\Models\Matricula;
use App\Models\Pagamento;
use App\Models\PagamentoPorReferencia;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;
use App\Repositories\AlunoRepository;
use Keygen\Keygen;
use App\Services\DividaService;
use App\Http\Controllers\Divida\ControloDivida;
use PDF;
use App\Http\Controllers\Extenso;
use App\Services\DescontoService;
use App\Services\BolsaService;
use App\Services\PagamentoService;
use App\Services\FaturaService;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

use App\Http\Controllers\ClassesAuxiliares\anoAtual;
use App\Models\AnoLectivo;
use App\Models\Caixa;
use App\Models\Grupo;
use App\Models\GrupoUtilizador;
use App\Models\MovimentoCaixa;
use App\Models\PagamentoItems;
use App\Models\CandidatoProva;
use App\Models\ControloValidacaoPagamento;
use App\Models\Utilizador;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class PagamentosController extends Controller
{
    use TraitHelpers;

    public $extenso;
    public $descontoService;
    public $bolsaService;
    public $dividaService;
    public $codigo_factura_em_curso = null;
    public $saldo_actual_estudante = null;
    public $anoAtualPrincipal;
    public $alunoRepository;
    public $divida;
    public $pagamentoService;
    public $faturaService;
    public $parametro_uma;

    public function __construct()
    {
        $this->middleware('auth');
        $this->alunoRepository = new  AlunoRepository();
        $this->anoAtualPrincipal = new anoAtual();
        $this->dividaService = new DividaService();
        $this->divida = new ControloDivida();
        $this->descontoService = new DescontoService();
        $this->bolsaService = new BolsaService();
        $this->extenso = new  Extenso();
        $this->parametro_uma = new ParametroUmaController();
        $this->pagamentoService = new PagamentoService();
        $this->faturaService = new FaturaService();
        
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();

        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect()->route('mc.bloquear-caixa');
        }

        $validacao = Grupo::where('designacao', "Validação de Pagamentos")->select('pk_grupo')->first();
        $admins = Grupo::where('designacao', 'Administrador')->select('pk_grupo')->first();
        $finans = Grupo::where('designacao', 'Area Financeira')->select('pk_grupo')->first();
        $tesous = Grupo::where('designacao', 'Tesouraria')->select('pk_grupo')->first();


        if(auth()->user()->hasRole(['Gestor de Caixa'])){

            if($request->data_inicio){
                $request->data_inicio = $request->data_inicio;
            }else{
                $request->data_inicio = date("Y-m-d");
            }
            
            $data['items'] = Pagamento::with('factura.matriculas.admissao.preinscricao', 'preinscricao.curso', 'operador_novos','operador_antigo','utilizadores')
                ->when($request->data_inicio, function ($query, $value) {
                    $query->whereDate('Data', '>=', Carbon::createFromDate($value));
                })
                ->when($request->data_final, function ($query, $value) {
                    $query->whereDate('Data', '<=', Carbon::createFromDate($value));
                })
                ->when($request->operador, function ($query, $value) {
                    $query->where('fk_utilizador', $value);
                })
                ->when($request->ano_lectivo, function ($query, $value) {
                    $query->where('AnoLectivo', $value);
                })
                ->where('forma_pagamento', 6)
                ->orderBy('tb_pagamentos.Codigo', 'desc')
                ->paginate(20)
                ->withQueryString();

        }

        if(auth()->user()->hasRole(['Supervisor'])){

            if($request->operador){
                $request->operador = $request->operador;
            }else{
                $request->operador = $user->codigo_importado;
            }

            if ($request->data_inicio) {
                $request->data_inicio = $request->data_inicio;
            } else {
                $request->data_inicio = date("Y-m-d");
            }

            $data['items'] = Pagamento::with('factura.matriculas.admissao.preinscricao', 'preinscricao.curso','operador_novos','operador_antigo','utilizadores')
            ->when($request->data_inicio, function ($query, $value) {
                $query->whereDate('Data', '>=', Carbon::createFromDate($value));
            })
            ->when($request->data_final, function ($query, $value) {
                $query->whereDate('Data', '<=', Carbon::createFromDate($value));
            })
            ->when($request->operador, function ($query, $value) {
                $query->where('fk_utilizador', $value);
            })
            ->when($request->ano_lectivo, function ($query, $value) {
                $query->where('AnoLectivo', $value);
            })
            ->where('forma_pagamento', 6)
            ->orderBy('tb_pagamentos.Codigo', 'desc')
            ->paginate(15)
            ->withQueryString();
        
        }

        if(auth()->user()->hasRole(['Operador Caixa'])){

            if ($request->data_inicio) {
                $request->data_inicio = $request->data_inicio;
            } else {
                $request->data_inicio = date("Y-m-d");
            }

            $data['items'] = Pagamento::with('factura.matriculas.admissao.preinscricao', 'preinscricao.curso','operador_novos','operador_antigo','utilizadores')
            ->when($request->data_inicio, function ($query, $value) {
                $query->whereDate('Data', '>=', Carbon::createFromDate($value));
            })
            ->when($request->data_final, function ($query, $value) {
                $query->whereDate('Data', '<=', Carbon::createFromDate($value));
            })
            ->when($request->operador, function ($query, $value) {
                $query->where('fk_utilizador', $value);
            })
            ->when($request->ano_lectivo, function ($query, $value) {
                $query->where('AnoLectivo', $value);
            })
            ->where('fk_utilizador', $user->codigo_importado)
            ->where('status_pagamento', 'pendente')
            ->where('forma_pagamento', 6)
            ->whereIn('estado', [1,2])
            ->orderBy('tb_pagamentos.Codigo', 'desc')
            ->paginate(15)
            ->withQueryString();

        }


        if(auth()->user()->hasRole(['Gestor de Caixa', 'Supervisor'])){
            $data['utilizadores'] = GrupoUtilizador::whereIn('fk_grupo', [$validacao->pk_grupo, $finans->pk_grupo, $tesous->pk_grupo])->orWhere('fk_utilizador', Auth::user()->pk_utilizador)->with('utilizadores')->get();
        }

        if(auth()->user()->hasRole(['Operador Caixa'])){
            $data['utilizadores'] = GrupoUtilizador::whereHas('utilizadores', function ($query) {
                $query->where('codigo_importado', auth()->user()->codigo_importado);
            })->whereIn('fk_grupo', [$validacao->pk_grupo, $finans->pk_grupo, $tesous->pk_grupo])->with('utilizadores')->get();
        }

        $data['ano_lectivos'] = AnoLectivo::orderBy('ordem', 'desc')->get();

        return Inertia::render('Operacoes/Pagamentos/Index', $data);
    }

    public function pdf(Request $request)
    {
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();

        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect()->route('mc.bloquear-caixa');
        }

        $ano = AnoLectivo::where('status', '1')->first();

        if($request->data_inicio){
            $request->data_inicio = $request->data_inicio;
        }else{
            $request->data_inicio = date("Y-m-d");
        }

        $data['items'] = Pagamento::when($request->data_inicio, function ($query, $value) {
            $query->where('created_at', '>=', Carbon::parse($value));
        })
        // ->when($request->data_final, function ($query, $value) {
        //     $query->where('created_at', '<', Carbon::parse($value));
        // })
        ->when($request->operador, function ($query, $value) {
            $query->where('fk_utilizador', $value);
        })
        ->when($request->ano_lectivo, function ($query, $value) {
            $query->where('AnoLectivo', $value);
        })
        ->with('factura.matriculas.admissao.preinscricao', 'preinscricao.curso','operador_novos','operador_antigo','utilizadores')
        ->where('forma_pagamento', 6)
        ->orderBy('tb_pagamentos.Codigo', 'desc')
        ->get();

        $data['requests'] = $request->all('data_inicio', 'data_final');

        $data['ano_lectivo'] = AnoLectivo::where('Codigo', $request->ano_lectivo)->first();
        $data['operador'] =  Utilizador::where('codigo_importado', $request->operador ?? auth()->user()->codigo_importado)->first();

        $pdf = \App::make('dompdf.wrapper');
        $pdf->loadView('Relatorios.listagem-pagamentos', $data);
        $pdf->getDOMPdf()->set_option('isPhpEnabled', true);
        return $pdf->stream();
    }


    public function excel(Request $request)
    {
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();

        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect()->route('mc.bloquear-caixa');
        }

        return Excel::download(new PagamentosExport($request), 'lista-de-pagamentos.xlsx');
    }


    public function detalhes($id)
    {
        $pagamento = Pagamento::leftjoin('tb_preinscricao', 'tb_pagamentos.Codigo_PreInscricao', '=', 'tb_preinscricao.Codigo')
            ->leftjoin('tb_admissao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
            ->leftjoin('tb_matriculas', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
            ->leftjoin('tb_cursos', 'tb_matriculas.Codigo_Curso', '=', 'tb_cursos.Codigo')
            ->leftjoin('factura', 'tb_pagamentos.codigo_factura', '=', 'factura.Codigo')
            ->leftjoin('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', '=', 'tb_pagamentos.AnoLectivo')
            ->where('tb_pagamentos.estado', 1)
            ->orderBy('tb_pagamentos.Codigo', 'desc')
            ->select('factura.Codigo as fact_codigo', 'factura.ValorAPagar', 'factura.DataFactura', 'tb_pagamentos.AnoLectivo', 'tb_pagamentos.codigo_factura', 'tb_pagamentos.Codigo', 'tb_pagamentos.valor_depositado', 'tb_pagamentos.DataRegisto', 'tb_pagamentos.estado', 'tb_pagamentos.nome_documento', 'tb_pagamentos.updated_at', 'Nome_Completo', 'tb_pagamentos.Totalgeral', 'tb_pagamentos.feito_com_reserva', 'DataRegisto', 'tb_matriculas.Codigo AS matricula', 'tb_cursos.Designacao AS curso')
            ->where('tb_pagamentos.Codigo',$id)
            ->first();

        if ($pagamento->AnoLectivo >= 2 and $pagamento->AnoLectivo <= 15) {
            $pagamento_itens = PagamentoItems::with('mes', 'servico')
                ->where('Codigo_Pagamento', $pagamento->Codigo)
                ->get();
        } else {
            $pagamento_itens = PagamentoItems::with('mes_temps', 'servico')
                ->where('Codigo_Pagamento', $pagamento->Codigo)
                ->get();
        }

        return response()->json([
            'data' => $pagamento,
            'items' => $pagamento_itens
        ], 200);
    }


    public function invalida($id)
    {
    
        $user = auth()->user();

        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();

        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect()->route('mc.bloquear-caixa');
        }
        
        $caixas = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
        
        if(!$caixas){
            return response()->json([
                'message' => 'Sem nenhum caixa aberto para realizar o deposito!',
            ], 401);
        }
        
        // 
        $pagamento = Pagamento::findOrFail($id);
        // 
        $factura = Factura::findOrFail($pagamento->codigo_factura);
        
        if($factura->codigo_descricao != 5){
            
            $pagamento->estado = 3;
            $pagamento->update();
            
            $factura->estado = 3;
            $factura->update();
            
        }else 
        
        // factura de negociação
        if($factura->codigo_descricao == 5){
            
            $total_pagar = 0;
            
            $pagamentos = Pagamento::where('codigo_factura', $pagamento->codigo_factura)->get();
            
            if(count($pagamentos) >= 2){
            
                $pagamento->estado = 3;
                $pagamento->update();
                
                foreach($pagamentos as $pag){
                    $total_pagar = $total_pagar + $pag->valor_depositado;
                }
                
                $factura->estado = 3;
                $factura->update();
                $factura->ValorEntregue = $total_pagar;
            }else{
                $pagamento->estado = 3;
                $pagamento->update();
                
                $factura->estado = 3;
                $factura->update();
                $factura->ValorEntregue = 0;
            }
            
        }
                
        $movimento = MovimentoCaixa::where('caixa_id', $caixa->codigo)->where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();

        $update = MovimentoCaixa::findOrFail($movimento->codigo);
        if($update->valor_arrecadado_pagamento - $pagamento->valor_depositado < 0){
            $update->valor_arrecadado_pagamento = 0;
        }else{
            $update->valor_arrecadado_pagamento = $update->valor_arrecadado_pagamento - $pagamento->valor_depositado;
        }
        
        if($update->valor_facturado_pagamento - $pagamento->valor_depositado < 0){
            $update->valor_facturado_pagamento = 0;
        }else{
            $update->valor_facturado_pagamento = $update->valor_facturado_pagamento - $pagamento->valor_depositado;
        }
        
        if($update->valor_arrecadado_total - $pagamento->valor_depositado < 0){
            $update->valor_arrecadado_total = 0;
        }else{
            $update->valor_arrecadado_total = $update->valor_arrecadado_total - $pagamento->valor_depositado;
        }
        
        $update->update();
        
        return response()->json([
            'message' => "Pagamento invalidado com sucesso!"
        ], 200);
        
    }

    public function create(Request $request)
    {

        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();

        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect()->route('mc.bloquear-caixa');
        }

        $user = auth()->user();

        $data['forma_pagamentos'] = FormaPagamento::where('status', 1)->get();

        return Inertia::render('Operacoes/Pagamentos/Create', $data);
    }


    public function getTodasReferencias(Request $request, $codigo_matricula)
    {

        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();

        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect()->route('mc.bloquear-caixa');
        }

        $aluno = Matricula::with(['admissao.preinscricao'])->findOrFail($codigo_matricula);

        $referencias = DB::table('factura')
            ->leftjoin('factura_items', 'factura_items.CodigoFactura', '=', 'factura.Codigo')
            ->leftjoin('tb_pagamentos', 'tb_pagamentos.codigo_factura', '=', 'factura.Codigo')
            ->join('tb_matriculas', 'tb_matriculas.Codigo', 'factura.CodigoMatricula')
            ->join('tb_admissao', 'tb_admissao.Codigo', '=', 'tb_matriculas.Codigo_Aluno')
            ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
            ->leftJoin('inscricao_avaliacoes', 'inscricao_avaliacoes.codigo_factura', '=', 'factura.Codigo')->select(
                DB::Raw('any_value(factura.Referencia) as referencia'),
                DB::Raw('any_value(factura.Codigo) as codigo_fatura'),
                DB::Raw('any_value(factura.estado) as estado_factura'),
                DB::Raw('any_value(factura_items.mes_temp_id) as mes_temp'),
                DB::Raw('any_value(inscricao_avaliacoes.codigo) as recurso'),
                DB::Raw('any_value(inscricao_avaliacoes.codigo_tipo_avaliacao) as codigo_tipo_avaliacao'),
                DB::Raw('any_value(tb_pagamentos.valor_depositado) as valor'),
                DB::Raw('any_value(tb_pagamentos.totalgeral) as totalgeral'),
                DB::Raw('any_value(tb_pagamentos.codigo_factura) as pag'),
                DB::Raw('any_value(factura.codigo_descricao) as tipo_fatura')
            )
            ->where('tb_preinscricao.user_id', $aluno->admissao->preinscricao->user_id)
            ->where('factura.corrente', 1)
            ->where('factura.estado', '!=', 3)
            ->where('factura.estado', '!=', 1)
            //->having('valor','<','totalgeral')
            ->groupBy('factura.Codigo')
            ->orderBy('factura.Codigo', 'desc')
            ->get();

        return response()->json($referencias);
    }

    public function faturaByReference(Request $request)
    {

        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();

        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect()->route('mc.bloquear-caixa');
        }

        $codigo_fatura = $request->get('codigo_fatura');

        $negociacao = DB::table('negociacao_dividas')->where('codigo_fatura', $codigo_fatura)->first();

        $fatura['metadeValorPagar'] = 0;
        $fatura['fatura'] = DB::table('factura')
            ->join('factura_items', 'factura_items.CodigoFactura', '=', 'factura.Codigo')
            ->leftjoin('tb_pagamentos', 'tb_pagamentos.codigo_factura', 'factura.Codigo')
            ->select(
                'factura.Codigo',
                'factura.ValorAPagar',
                'factura.ValorEntregue',
                'tb_pagamentos.valor_depositado',
                'factura.estado as estado_factura',
                'factura.codigo_descricao as descricao_factura',
                'factura.ano_lectivo as ano_lectivo',
                'factura.CodigoMatricula as CodigoMatricula'
            )
            ->where('factura.Codigo', $codigo_fatura)
            ->first();

        $fatura['disabled'] = 0;
        if ($fatura['fatura'] && $fatura['fatura']->descricao_factura == 5 && !$this->divida->pagouNegociacao($fatura['fatura']->CodigoMatricula)) {
            $fatura['metadeValorPagar'] = number_format(($negociacao->primeiroValorApagar), 2, '.', '');
            $fatura['tipo_negociacao_id'] = $negociacao->tipo_negociacao_id;
        } elseif ($fatura['fatura'] && $fatura['fatura']->descricao_factura == 5 && $this->divida->pagouNegociacao($fatura['fatura']->CodigoMatricula)) {
            $fatura['disabled'] = 1;
            $fatura['metadeValorPagar'] = number_format(($negociacao->valorRestante), 2, '.', '');
            $fatura['tipo_negociacao_id'] = $negociacao->tipo_negociacao_id;
        }

        if ($fatura['fatura'] && $fatura['fatura']->descricao_factura == 5) {

            $pagou = DB::table('negociacao_dividas')
                ->join('factura', 'factura.Codigo', '=', 'negociacao_dividas.codigo_fatura')
                ->join('tb_pagamentos', 'tb_pagamentos.codigo_factura', '=', 'factura.Codigo')
                ->join('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', '=', 'tb_pagamentos.Codigo')
                ->where('factura.Codigo', $fatura['fatura']->Codigo)
                ->where('tb_pagamentos.estado', 1)->select('negociacao_dividas.valor_divida', 'negociacao_dividas.primeiroValorApagar')->first();
            if ($pagou) {
                $fatura['disabled'] = 1;
            }
        }

        $fatura['valor_depositado'] = DB::table('factura')
            ->leftjoin('tb_pagamentos', 'tb_pagamentos.codigo_factura', 'factura.Codigo')
            ->select(DB::raw('sum(tb_pagamentos.valor_depositado) as valor_depositado'))
            ->where('factura.Codigo', $codigo_fatura)
            ->whereIn('tb_pagamentos.estado', [0, 1]) // retirei novamente
            ->first();

        $fatura['itens'] = DB::table('factura_items')
            ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', '=', 'factura_items.CodigoProduto')
            ->select(DB::raw('count(*) as qtd,sum(factura_items.Total) as soma,ANY_VALUE(tb_tipo_servicos.Descricao) as servico'))
            ->where('CodigoFactura', $codigo_fatura)
            ->groupBy('factura_items.CodigoFactura')
            ->get();

        $fatura['extenso'] = $this->valor_por_extenso($fatura['fatura']->ValorAPagar);

        return response()->json($fatura);
    }

    public function aplicarMultaMes($data, $mes_id)
    {

        $resultado = null;

        try {
            $ano = DB::table('mes_temp')->where('id', $mes_id)->first()->ano_lectivo;
            $mesesPagar = $this->mesesPagar($data, 1, $mes = 0, $ano);

            $mesesPagar1 = $mesesPagar->where('codigo', $mes_id);

            if ($mesesPagar1) {
                return $mesesPagar1->first();
            } else {
                return $resultado;
            }
        } catch (\Throwable $th) {
            return $resultado;
        }

        return $resultado;
    }

    public function salvarPagamentosDiversos(Request $request, $codigo_matricula)
    {
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();

        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect()->route('mc.bloquear-caixa');
        }

        $caixas = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
        if (!filled($caixas)) {
            $result['message'] = 'Por valor! faça abertura do caixa para efectuar o pagamento.';
            return response()->json($result, 201);
        }
        
        $aluno = Matricula::with(['admissao.preinscricao'])->findOrFail($codigo_matricula);
        $id = $aluno->admissao->preinscricao->Codigo;
        $codigo = $aluno->admissao->preinscricao->Codigo;
        $data = json_decode($request->pagamento, true);
        $fonte = json_decode($request->fonte, true);
        $switch_troco = json_decode($request->switch_troco, true);
        $codigoDaFatura = json_decode($request->codigo_fatura, true);
            
        $saldo_novo = DB::table('tb_preinscricao')
            ->where('tb_preinscricao.Codigo', $codigo)
            ->select('saldo', 'saldo_anterior')->first();

        $saldo_anterior = $saldo_novo->saldo_anterior;
        $saldo_novo = $saldo_novo->saldo;
        
        $data['forma_pagamento'] = 6;

        if ($fonte == 2) {
            $codigoDaFatura = $this->codigo_factura_em_curso; // codigo da factura gerada aqui no backend
            $saldo_novo = $this->saldo_actual_estudante;
            if (!$codigoDaFatura) {
                return Response()->json("Ocorreu um erro(cf)", 201);
            }
        }
        
        $tamanho = 0;

        if (sizeOf($data) > 0) {
            $tamanho = sizeOf($data);
        }

        //$stra = trim(preg_replace('/\s+/','', $str));
        $data['N_Operacao_Bancaria'] = rand(0, $codigoDaFatura) . time();

        $total_sem_multa = 0;
        $total_fatura_sem_multa = 0;
        $total_multa_fatura = 0;
        $multaItem = 0;
        $valorComdesconto = 0;
        $anoCorrente = $this->anoLectivoActivo();

        $fatura_paga = DB::table('factura')
            ->join('tb_matriculas', 'tb_matriculas.Codigo', 'factura.CodigoMatricula')
            ->join('tb_admissao', 'tb_admissao.Codigo', '=', 'tb_matriculas.Codigo_Aluno')
            ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
            ->leftJoin('tb_pagamentos', 'tb_pagamentos.codigo_factura', '=', 'factura.Codigo')
            ->where('factura.Codigo', $codigoDaFatura)
            ->where('tb_preinscricao.Codigo', $codigo)
            ->select('tb_matriculas.Codigo as matricula', 'factura.Codigo as codigo', 'tb_pagamentos.valor_depositado', 'factura.ValorAPagar as ValorAPagar', 'factura.codigo_descricao', 'factura.Codigo', 'factura.ValorEntregue as ValorEntregue', 'factura.estado as estado_factura', 'factura.ano_lectivo as ano_factura')
            ->first();
            
      
        if ($fatura_paga && ($saldo_novo >= $fatura_paga->ValorAPagar) && ($fonte == 1) /*&& ($data['valor_depositado'] < $fatura_paga->ValorAPagar || $data['valor_depositado'] < ($fatura_paga->ValorAPagar - $fatura_paga->ValorEntregue))*/) {  //Saldo maior que 1, Ndongala
            $response['mensagem'] = "Pagamento enviado com sua Reserva disponível! Por favor verifique a factura gerada pelo sistema";
            try {
                $this->salvarPagamentoComSaldo($request, $fatura_paga->codigo, $aluno->admissao->preinscricao->user_id);
            } catch (\Illuminate\Database\QueryException $e) {
                DB::rollback();
                return Response()->json($e->getMessage());
            }
        }
        
 
        $Somapagamentos = DB::table('factura')->join('tb_matriculas', 'tb_matriculas.Codigo', 'factura.CodigoMatricula')
            ->join('tb_admissao', 'tb_admissao.Codigo', '=', 'tb_matriculas.Codigo_Aluno')
            ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
            ->join('tb_pagamentos', 'tb_pagamentos.codigo_factura', '=', 'factura.Codigo')
            ->where('factura.Codigo', $codigoDaFatura)->where('tb_preinscricao.Codigo', $codigo)
            ->select('tb_pagamentos.valor_depositado as valor_depositado', 'factura.ValorAPagar as ValorAPagar', 'factura.codigo_descricao', 'factura.Codigo', 'factura.ValorEntregue as ValorEntregue', 'factura.estado as estado_factura', 'factura.ano_lectivo as ano_factura')
            ->get();  
         

        $total = $Somapagamentos->sum('valor_depositado');
        
        
        if ($fatura_paga && $Somapagamentos && $total >= $fatura_paga->ValorAPagar && $fonte == 1) {
            return response()->json("Ja efectuou o pagamento da fatura referida!", 201);
        } elseif ($fatura_paga && $fatura_paga->ValorEntregue >= $fatura_paga->ValorAPagar && $fonte == 1) {
            return response()->json("Ja efectuou o pagamento da fatura referida!", 201);
        } else {
            $servico_mensal = DB::table('factura_items')->join('factura', 'factura_items.CodigoFactura', '=', 'factura.Codigo')
                ->join('tb_matriculas', 'tb_matriculas.Codigo', 'factura.CodigoMatricula')
                ->join('tb_admissao', 'tb_admissao.Codigo', '=', 'tb_matriculas.Codigo_Aluno')
                ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
                ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', '=', 'factura_items.CodigoProduto')
                ->select('*', 'factura.codigo_descricao as tipo_factura', 'factura.ValorAPagar as ValorAPagar', 'factura.estado as estado_factura', 'factura.TotalPreco as TotalPreco', 'factura.Codigo as codigo_fatura')
                ->where('factura.Codigo', $codigoDaFatura)
                ->where('tb_preinscricao.Codigo', $codigo)
                ->where('tb_tipo_servicos.TipoServico', 'Mensal')
                ->first();

            $valorFatura = DB::table('factura_items')
                ->join('factura', 'factura_items.CodigoFactura', '=', 'factura.Codigo')
                ->join('tb_matriculas', 'tb_matriculas.Codigo', 'factura.CodigoMatricula')
                ->join('tb_admissao', 'tb_admissao.Codigo', '=', 'tb_matriculas.Codigo_Aluno')
                ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
                ->select('*', 'factura.codigo_descricao as tipo_factura', 'factura.ValorAPagar as ValorAPagar', 'factura.estado as estado_factura', 'factura.TotalPreco as TotalPreco', 'factura.Codigo as codigo_fatura', 'factura.ano_lectivo as ano_factura', 'factura.ValorEntregue as ValorEntregue')
                ->where('factura.Codigo', $codigoDaFatura)
                ->where('tb_preinscricao.Codigo', $codigo)
                ->first();

            $candidatura1 = DB::table('tb_preinscricao')->where('tb_preinscricao.Codigo', $codigo)->select()->first();


            if (isset($data['N_Operacao_Bancaria2']) && ($data['N_Operacao_Bancaria'] == $data['N_Operacao_Bancaria2'])) {
                return response()->json("Digitou dois números de operações bancárias iguais!", 201);
            }

            if ($data['valor_depositado'] <= 0) {
                return response()->json("O valor introduzido não é permitido para realizar a operação!", 201);
            }
            //dd($valorFatura->ValorAPagar,$data['valor_depositado'],$saldo->saldo);

            if ($valorFatura->ValorEntregue <= 0 && number_format(($data['valor_depositado'] + $saldo_novo), 2, '.', '') < $valorFatura->ValorAPagar && ($valorFatura->tipo_factura == 3 || $valorFatura->tipo_factura == 6 || $valorFatura->tipo_factura == 7 || $valorFatura->tipo_factura == 8)) {
                return response()->json("O valor introduzido não é permitido para realizar a operação! Seleccionou uma factura de serviço diferente de propina", 201);
            }

            if (($valorFatura->ValorEntregue > 0 && number_format((($data['valor_depositado'] + $saldo_novo) + $valorFatura->ValorEntregue), 2, '.', '') < $valorFatura->ValorAPagar) && ($valorFatura->tipo_factura == 3 || $valorFatura->tipo_factura == 6 || $valorFatura->tipo_factura == 7 || $valorFatura->tipo_factura == 8)) {
                return response()->json("O valor introduzido não é permitido para realizar a operação! Seleccionou uma factura de serviço diferente de propina", 201);
            }
    
            if ($data['valor_depositado'] > 2000000 && $candidatura1->codigo_tipo_candidatura == 1) {
                return response()->json("O valor introduzido não é permitido para realizar a operação!", 201);
            } else {
            
                $factura_items1 = DB::table('factura_items')->join('factura', 'factura_items.CodigoFactura', '=', 'factura.Codigo')
                    ->select('factura_items.*')
                    ->where('factura.Codigo', $valorFatura->codigo_fatura)
                    ->get();

                $data['DataBanco'] = date("Y-m-d");
                $array_fatura1 = json_decode($factura_items1, true);

                DB::beginTransaction();
                
                if ($valorFatura /*&& $valorFatura->ano_factura == $anoCorrente */ && $valorFatura->tipo_factura != 5 && $servico_mensal && $valorFatura->estado_factura != 2) {
                    foreach ($array_fatura1 as $key => $value) {


                        $data_limite = $this->aplicarMultaMes($data['DataBanco'], $value['mes_temp_id']);

                        if ($data_limite &&  $data['DataBanco'] <= $data_limite['data'] && $value['Multa'] > 0) {

                            if ($value['Multa'] > 0) {
                                try {
                                    $multaAtualItem = DB::table('factura_items')->where('factura_items.codigo', $value['codigo'])->select('Multa')->first();

                                    $total_sem_multa = $value['Total'] - $multaAtualItem->Multa;

                                    DB::table('factura_items')->where('factura_items.codigo', $value['codigo'])->update(['Total' => $total_sem_multa]);
                                } catch (\Illuminate\Database\QueryException $e) {

                                    DB::rollback();
                                    return Response()->json($e->getMessage());
                                }
                                //treceira operacao
                                try {
                                    DB::table('factura_items')->where('factura_items.codigo', $value['codigo'])->update(['Multa' => 0]);
                                } catch (\Illuminate\Database\QueryException $e) {
                                    DB::rollback();
                                    return Response()->json($e->getMessage());
                                }
                                //quarta operacao
                                try {
                                    $multaFatura = DB::table('factura')->where('factura.Codigo', $valorFatura->codigo_fatura)->select('TotalMulta', 'ValorAPagar')->first();
                                    DB::table('factura')->where('factura.Codigo', $valorFatura->codigo_fatura)->update(['ValorAPagar' => $multaFatura->ValorAPagar - $multaAtualItem->Multa]);
                                } catch (\Illuminate\Database\QueryException $e) {

                                    DB::rollback();
                                    return Response()->json($e->getMessage());
                                }
                                // $data['valor_depositado'] = ($multaFatura->ValorAPagar - $multaAtualItem->Multa);
                                $data['valor_depositado'] = $data['valor_depositado'];
                                //quinta operacao
                                try {
                                    $multaFatura = DB::table('factura')->where('factura.Codigo', $valorFatura->codigo_fatura)->select('TotalMulta')->first();
                                    $total_multa_fatura = $multaFatura->TotalMulta - $multaAtualItem->Multa;
                                    DB::table('factura')->where('factura.Codigo', $valorFatura->codigo_fatura)->update(['TotalMulta' => $total_multa_fatura]);
                                } catch (\Illuminate\Database\QueryException $e) {

                                    DB::rollback();
                                    return Response()->json($e->getMessage());
                                }

                                //sexta operacao
                                try {
                                    $saldoC = DB::table('tb_preinscricao')->select('saldo', 'saldo_anterior')->where('Codigo', $codigo)->first();
                                    // Actualizar saldo 1
                                    // DB::table('tb_preinscricao')->where('tb_preinscricao.Codigo', $codigo)->update(['saldo' => $saldoC->saldo + $multaAtualItem->Multa]);
                                } catch (\Illuminate\Database\QueryException $e) {
                                    DB::rollback();
                                    return Response()->json($e->getMessage());
                                }
                            }
                        }

                        // aplicar a percentagem certa

                        elseif ($data_limite &&  $data['DataBanco'] > $data_limite['data'] && $value['Multa'] > 0) {

                            if ($value['Multa'] > 0) {

                                //primeira operacao
                                try {

                                    $multaAtualItem = DB::table('factura_items')->where('factura_items.codigo', $value['codigo'])->select('Multa')->first();

                                    $precoItem = DB::table('factura_items')->where('factura_items.codigo', $value['codigo'])->select('preco', 'descontoProduto')->first();

                                    $valorComdesconto = $precoItem->preco - $precoItem->descontoProduto;
                                    $multaItem = $valorComdesconto * ($data_limite['taxa'] / 100);

                                    $total_item = $valorComdesconto + $multaItem;

                                    // comentado 8/11/21 DB::table('factura_items')->where('factura_items.codigo', $value['codigo'])->update(['Total' => $total_item]);
                                } catch (\Illuminate\Database\QueryException $e) {

                                    DB::rollback();
                                    return Response()->json($e->getMessage());
                                }
                                //treceira operacao
                                try {

                                    // comentado 8/11/21 DB::table('factura_items')->where('factura_items.codigo', $value['codigo'])->update(['Multa' => $multaItem]);
                                } catch (\Illuminate\Database\QueryException $e) {

                                    DB::rollback();
                                    return Response()->json($e->getMessage());
                                }

                                //quarta operacao
                                try {

                                    $multaFatura = DB::table('factura')->where('factura.Codigo', $valorFatura->codigo_fatura)->select('TotalMulta', 'ValorAPagar')->first();

                                    $qtd = $factura_items1->count();

                                    // comentado 8/11/21 DB::table('factura')->where('factura.Codigo', $valorFatura->codigo_fatura)->update(['ValorAPagar' => $qtd * $total_item]);
                                } catch (\Illuminate\Database\QueryException $e) {

                                    DB::rollback();
                                    return Response()->json($e->getMessage());
                                }
                                //quinta operacao
                                try {
                                    $multaFatura = DB::table('factura')->where('factura.Codigo', $valorFatura->codigo_fatura)->select('TotalMulta')->first();

                                    $total_multa_fatura = $qtd * $multaItem;

                                    // comentado 8/11/21 DB::table('factura')->where('factura.Codigo', $valorFatura->codigo_fatura)->update(['TotalMulta' => $total_multa_fatura]);
                                } catch (\Illuminate\Database\QueryException $e) {

                                    DB::rollback();
                                    return Response()->json($e->getMessage());
                                }

                                //sexta operacao
                                try {
                                    $saldoC = DB::table('tb_preinscricao')->select('saldo', 'saldo_anterior')->where('Codigo', $codigo)->first();
                                    //DB::table('tb_preinscricao')->where('tb_preinscricao.Codigo', $codigo)->update(['saldo' => $saldoC->saldo + $multaAtualItem->Multa - $multaItem]);
                                } catch (\Illuminate\Database\QueryException $e) {
                                    DB::rollback();
                                    return Response()->json($e->getMessage());
                                }
                            }
                        }
                        // SE PAGOU DEPOIS DO LIMITE

                        elseif ($data_limite && $data['DataBanco'] > $data_limite['data'] && $value['Multa'] == 0 && $valorFatura->estado_factura != 2) {
                            if ($value['Multa'] == 0) {
                                try {

                                    $precoItem = DB::table('factura_items')->where('factura_items.codigo', $value['codigo'])->select('preco', 'descontoProduto')->first();
                                    $valorComdesconto = $precoItem->preco - $precoItem->descontoProduto;
                                    $multaItem = $valorComdesconto * ($data_limite['taxa'] / 100);
                                    //dd('Multa- '.$multaItem.'taxa- '.$data_limite['taxa'] .'taxa- '.$data_limite['mes']);
                                    DB::table('factura_items')->where('factura_items.codigo', $value['codigo'])->update(['Multa' => $multaItem]);
                                } catch (\Illuminate\Database\QueryException $e) {

                                    DB::rollback();
                                    return Response()->json($e->getMessage());
                                }
                                //treceira operacao
                                try {
                                    DB::table('factura_items')->where('factura_items.codigo', $value['codigo'])->update(['Total' => $value['Total'] + $multaItem]);
                                } catch (\Illuminate\Database\QueryException $e) {

                                    DB::rollback();
                                    return Response()->json($e->getMessage());
                                }
                                //quarta operacao
                                try {
                                    $aplicarMultaFatura = DB::table('factura')->where('factura.Codigo', $valorFatura->codigo_fatura)->select('TotalMulta', 'ValorAPagar')->first();
                                    DB::table('factura')->where('factura.Codigo', $valorFatura->codigo_fatura)->update(['ValorAPagar' => $aplicarMultaFatura->ValorAPagar + $multaItem, 'TotalMulta' => $aplicarMultaFatura->TotalMulta + $multaItem]);
                                } catch (\Illuminate\Database\QueryException $e) {

                                    DB::rollback();
                                    return Response()->json($e->getMessage());
                                }
                                //quinta operacao
                                try {
                                    $totalMultaFatura = DB::table('factura')->where('factura.Codigo', $valorFatura->codigo_fatura)->select('TotalMulta', 'ValorAPagar')->first();
                                    $saldoA = DB::table('tb_preinscricao')->select('saldo', 'saldo_anterior')->where('Codigo', $codigo)->first();
                                    //DB::table('tb_preinscricao')->where('tb_preinscricao.Codigo', $codigo)->update(['saldo' => $saldoA->saldo - $multaItem]);
                                } catch (\Illuminate\Database\QueryException $e) {

                                    DB::rollback();
                                    return Response()->json($e->getMessage());
                                }
                            }
                        }
                    }
                }

                
                if ($fatura_paga && $saldo_novo > 0 && $fonte == 1 && ($data['valor_depositado'] < $fatura_paga->ValorAPagar)) { // SE NAO TEM SALDO SUFICIENTE
                    try {
                        $result['message'] = 'Pagamento efectuado com sucesso! Por favor, verifique a factura gerada pelo sistema.';
                        
                        $pagamento_saldo['Data'] = date('Y-m-d');
                        $pagamento_saldo['AnoLectivo'] = $fatura_paga->ano_factura;
                        $pagamento_saldo['Totalgeral'] = $fatura_paga->ValorAPagar;
                        $pagamento_saldo['Codigo_PreInscricao'] = $codigo;
                        $pagamento_saldo['valor_depositado'] = $saldo_novo;
                        $pagamento_saldo['DataRegisto'] = date('Y-m-d H:i:s');
                        $pagamento_saldo['codigo_factura'] = $fatura_paga->codigo;
                        $pagamento_saldo['caixa_id'] = $caixas->codigo;
                        $pagamento_saldo['status_pagamento'] = 'pendente';
                        $pagamento_saldo['estado'] = ($fatura_paga->codigo_descricao == 2 || $fatura_paga->codigo_descricao == 4 || $fatura_paga->codigo_descricao == 5 || $fatura_paga->codigo_descricao == 10) ? 1 : 0;
                        $pagamento_saldo['corrente'] = 1;
                        $pagamento_saldo['Observacao'] = "Pagamento efectuado com Reserva no Mutue Cash";
                        $pagamento_saldo['forma_pagamento'] = "6";
                        $pagamento_saldo['fk_utilizador'] = auth()->user()->codigo_importado;
                        $pagamento_saldo['Utilizador'] = auth()->user()->codigo_importado;
           
                        $id_pag = DB::table('tb_pagamentos')->insertGetId($pagamento_saldo);
                       
                        DB::table('tb_preinscricao')
                            ->where('tb_preinscricao.Codigo', $codigo)
                            ->update(['saldo' => 0, 'saldo_anterior' => $saldo_novo + $saldo_anterior]);
                    } catch (\Illuminate\Database\QueryException $e) {

                        DB::rollback();
                        return Response()->json($e->getMessage());
                    }


                    $factura_items = DB::table('factura_items')
                        ->join('factura', 'factura_items.CodigoFactura', '=', 'factura.Codigo')
                        ->select('factura_items.*', 'factura.ano_lectivo')
                        ->where('factura.Codigo', $fatura_paga->codigo)
                        ->get();
                    
                    $array_fatura = json_decode($factura_items, true);
                    foreach ($array_fatura as $key => $fac) {
                        $id_mes = null;
                        $mes = null;
                        
                     
                        $anoLectivo = DB::table('tb_ano_lectivo')->where('Codigo', $fac['ano_lectivo'])->first()->Designacao;
                        
                        $desconto_mes = $fac['descontoProduto'];
                        if ($servico_mensal && (int)$anoLectivo <= 2019) {
                            $mes = $fac['Mes'];

                            if ($fac['Mes'] == 'MAR') {
                                $id_mes = 1;
                            } elseif ($fac['Mes'] == 'ABR') {
                                $id_mes = 2;
                            } elseif ($fac['Mes'] == 'MAI') {
                                $id_mes = 3;
                            } elseif ($fac['Mes'] == 'JUN') {
                                $id_mes = 4;
                            } elseif ($fac['Mes'] == 'JUL') {
                                $id_mes = 5;
                            } elseif ($fac['Mes'] == 'AGO') {
                                $id_mes = 6;
                            } elseif ($fac['Mes'] == 'SET') {
                                $id_mes = 7;
                            } elseif ($fac['Mes'] == 'OUT') {
                                $id_mes = 8;
                            } elseif ($fac['Mes'] == 'NOV') {
                                $id_mes = 9;
                            } elseif ($fac['Mes'] == 'DEZ') {
                                $id_mes = 10;
                            }
                        }
                        DB::table('tb_pagamentosi')->insert([
                            'Codigo_Pagamento' => $id_pag,
                            'Codigo_Servico' => $fac['CodigoProduto'],
                            'Valor_Pago' => $saldo_novo,
                            'Quantidade' => 1,
                            'Valor_Total' => $fac['Total'],
                            'Multa' => $fac['Multa'],
                            'Deconnto' => $desconto_mes,
                            'Ano' => $anoLectivo,
                            'mes_id' => $id_mes,
                            'Mes' => $mes,
                            'mes_temp_id' => $fac['mes_temp_id']
                        ]);
                    }
                }
           
                // pagamento
                try {
                    $ano = DB::table('tb_ano_lectivo')
                        ->where('Codigo', $anoCorrente)
                        ->first();
                    $dados_negociacao = '';
                    //$total_pago=$data['valor_depositado'];

                    $fact_aluno = DB::table('factura')
                        ->join('tb_matriculas', 'tb_matriculas.Codigo', 'factura.CodigoMatricula')
                        ->join('tb_admissao', 'tb_admissao.Codigo', '=', 'tb_matriculas.Codigo_Aluno')
                        ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
                        ->where('factura.Codigo', $codigoDaFatura)
                        ->where('tb_preinscricao.Codigo', $codigo)
                        ->select('factura.Codigo', 'factura.ValorAPagar', 'factura.ano_lectivo', 'factura.codigo_descricao as codigo_descricao', 'factura.TotalPreco', 'factura.estado as estado_factura')
                        ->first();

                    // inserir pagamento de negociacao
                    if ($fact_aluno && $fact_aluno->codigo_descricao == 5) {
                        $dados_negociacao = DB::table('factura')->join('tb_matriculas', 'tb_matriculas.Codigo', 'factura.CodigoMatricula')
                            ->join('tb_admissao', 'tb_admissao.Codigo', '=', 'tb_matriculas.Codigo_Aluno')
                            ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
                            ->join('negociacao_dividas', 'negociacao_dividas.codigo_fatura', '=', 'factura.Codigo')
                            ->join('factura_items', 'factura_items.CodigoFactura', '=', 'factura.Codigo')
                            ->where('factura.Codigo', $fact_aluno->Codigo)
                            ->where('tb_preinscricao.Codigo', $codigo)
                            ->select('factura.Codigo', 'factura.ValorAPagar', 'factura.ano_lectivo', 'factura.codigo_descricao as codigo_descricao', 'negociacao_dividas.mesesQuitar as qtdMeses')
                            ->first();
                    }
                

                    $data['Data'] = date('Y-m-d'); //2,4,5,10
                    $data['AnoLectivo'] = $fact_aluno->ano_lectivo;
                    $data['Totalgeral'] = $fact_aluno->ValorAPagar;
                    $data['Codigo_PreInscricao'] = $codigo;
                    $data['DataRegisto'] = date('Y-m-d H:i:s');
                    $data['codigo_factura'] = $fact_aluno->Codigo;
                    $data['estado'] = ($fact_aluno->codigo_descricao == 2 || $fact_aluno->codigo_descricao == 4 || $fact_aluno->codigo_descricao == 5 || $fact_aluno->codigo_descricao == 10) ? 1 : 0;
                    $data['corrente'] = 1;
                    $data['caixa_id'] = $caixas->codigo;
                    $data['status_pagamento'] = 'pendente';
                    $data['forma_pagamento'] = '6';
                    $data['Observacao'] = "Pagamento efectuado por Cash";
                    $data['fk_utilizador'] = auth()->user()->codigo_importado;
                    $data['Utilizador'] = auth()->user()->codigo_importado;
                    
                    try {
                        $id_pag = DB::table('tb_pagamentos')->insertGetId($data);
                    } catch (\Illuminate\Database\QueryException $e) {
                        DB::rollback();
                        return Response()->json($e->getMessage());
                    }

                    try {
                        $pagamentos_factura = DB::table('tb_pagamentos')->where('codigo_factura', $codigoDaFatura)->where('forma_pagamento', 6)->where('feito_com_reserva', 'Y')->first();
                        $valor_pago_com_saldo = filled($pagamentos_factura) ? $pagamentos_factura->valor_depositado : $saldo_novo;
                    } catch (\Throwable $th) {
                        DB::rollback();
                        return Response()->json($th->getMessage());
                    }

                } catch (\Illuminate\Database\QueryException $e) {
                    DB::rollback();
                    return Response()->json($e->getMessage());
                }
                              
                try {
                    $histo = [
                        'referencia' => $id_pag,
                        'data_movimento' => date('Y-m-d'), 'credito' => 0, 'debito' => 0, 'estado' => 0, 'matricula' => $aluno->Codigo, 'saldo_operacao' => 0, 'saldo_geral' => 0, 'codigoTipoMovimento' => 2, 'codigoMotivo' => null,
                        'codigoUtilizador' => null, 'observacao' => 'pagamento enviado', 'Factura' => $fact_aluno->Codigo
                    ];
                    $variavel = DB::table('historico_movimento_conta_estudante')->insert($histo);
                } catch (\Illuminate\Database\QueryException $e) {
                    DB::rollback();
                    return Response()->json('ocorreu um erro(mc)');
                }
       
                try {
                    $controle['pagamento'] = $id_pag;
                    $controle['estado_utilizacao'] = 1;
                    $controle['created_at'] = Carbon::now();
                    $controle['update_at'] = Carbon::now();
                    DB::table('tb_controle_validacao_pagamentos')->insert($controle);
                } catch (\Illuminate\Database\QueryException $e) {

                    DB::rollback();
                    return Response()->json($e->getMessage());
                }

                try {

                    $factura_items = DB::table('factura_items')->join('factura', 'factura_items.CodigoFactura', '=', 'factura.Codigo')->select('factura_items.*')->where('factura.Codigo', $fact_aluno->Codigo)->get();
                    $array_fatura = json_decode($factura_items, true);

                    $desconto_mes = 0;
                    foreach ($array_fatura as $key => $fac) {

                        $anoLectivo = DB::table('tb_ano_lectivo')
                            ->where('Codigo', $fact_aluno->ano_lectivo)
                            ->first();
                        //(int)$anoLectivo->Designacao<=2019
                        $id_mes = null;
                        $mes = null;
                        $desconto_mes = $fac['descontoProduto'];
                        if ($servico_mensal && (int)$anoLectivo->Designacao <= 2019) {
                            $mes = $fac['Mes'];

                            if ($fac['Mes'] == 'MAR') {
                                $id_mes = 1;
                            } elseif ($fac['Mes'] == 'ABR') {
                                $id_mes = 2;
                            } elseif ($fac['Mes'] == 'MAI') {
                                $id_mes = 3;
                            } elseif ($fac['Mes'] == 'JUN') {
                                $id_mes = 4;
                            } elseif ($fac['Mes'] == 'JUL') {
                                $id_mes = 5;
                            } elseif ($fac['Mes'] == 'AGO') {
                                $id_mes = 6;
                            } elseif ($fac['Mes'] == 'SET') {
                                $id_mes = 7;
                            } elseif ($fac['Mes'] == 'OUT') {
                                $id_mes = 8;
                            } elseif ($fac['Mes'] == 'NOV') {
                                $id_mes = 9;
                            } elseif ($fac['Mes'] == 'DEZ') {
                                $id_mes = 10;
                            }
                        }

                        if ($fact_aluno && $fact_aluno->codigo_descricao == 5) {

                            $anoLectivo = DB::table('tb_ano_lectivo')
                                ->where('Codigo', $fac['codigo_anoLectivo'])
                                ->first()->Designacao;

                            if ((int)$anoLectivo <= 2019) {

                                $mes = $fac['Mes'];
                                // $id_mes =DB::table('meses')->where('mes', $fac['Mes'])->first()->codigo;

                                if ($fac['Mes'] == 'MAR') {
                                    $id_mes = 1;
                                } elseif ($fac['Mes'] == 'ABR') {
                                    $id_mes = 2;
                                } elseif ($fac['Mes'] == 'MAI') {
                                    $id_mes = 3;
                                } elseif ($fac['Mes'] == 'JUN') {
                                    $id_mes = 4;
                                } elseif ($fac['Mes'] == 'JUL') {
                                    $id_mes = 5;
                                } elseif ($fac['Mes'] == 'AGO') {
                                    $id_mes = 6;
                                } elseif ($fac['Mes'] == 'SET') {
                                    $id_mes = 7;
                                } elseif ($fac['Mes'] == 'OUT') {
                                    $id_mes = 8;
                                } elseif ($fac['Mes'] == 'NOV') {
                                    $id_mes = 9;
                                } elseif ($fac['Mes'] == 'DEZ') {
                                    $id_mes = 10;
                                }
                            }

                            if ($fact_aluno->estado_factura == 5 || $fact_aluno->estado_factura != 2) {

                                DB::table('tb_pagamentosi')->insert(
                                    ['Codigo_Pagamento' => $id_pag, 'Codigo_Servico' => $fac['CodigoProduto'], 'Valor_Pago' => $fac['Total'], 'Quantidade' => 1, 'Valor_Total' => $fac['Total'], 'Multa' => $fac['Multa'], 'Deconnto' => $desconto_mes, 'Ano' => $anoLectivo, 'mes_id' => $id_mes, 'Mes' => $mes, 'mes_temp_id' => $fac['mes_temp_id']]
                                );
                            } elseif ($fact_aluno->estado_factura == 2 || ($Somapagamentos && $Somapagamentos->sum('valor_depositado') < $Somapagamentos->pluck('ValorAPagar')->first())) {
                                //dd($Somapagamentos->sum('valor_depositado'));
                                DB::table('tb_pagamentosi')->insert(
                                    ['Codigo_Pagamento' => $id_pag, 'Codigo_Servico' => $fac['CodigoProduto'], 'Valor_Pago' => $fac['Total'], 'Quantidade' => 1, 'Valor_Total' => $fac['Total'], 'Multa' => $fac['Multa'], 'Deconnto' => $desconto_mes, 'Ano' => $anoLectivo, 'mes_id' => $id_mes, 'Mes' => $mes, 'mes_temp_id' => $fac['mes_temp_id']]
                                );
                            }
                        } else {


                            DB::table('tb_pagamentosi')->insert(
                                ['Codigo_Pagamento' => $id_pag, 'Codigo_Servico' => $fac['CodigoProduto'], 'Valor_Pago' => $fac['Total'], 'Quantidade' => 1, 'Valor_Total' => $fac['Total'], 'Multa' => $fac['Multa'], 'Deconnto' => $desconto_mes, 'Ano' => $anoLectivo->Designacao, 'mes_id' => $id_mes, 'Mes' => $mes, 'mes_temp_id' => $fac['mes_temp_id']]
                            );
                        }

                        $id_documento_validacao = '';
                        $servico_doc = DB::table('tb_tipo_servicos')->where('codigo_ano_lectivo', $anoCorrente)
                            ->where('Codigo', $fac['CodigoProduto'])->select('*')->first();

                        if ($servico_doc && ($servico_doc->sigla == 'CdF' || $servico_doc->sigla == 'CdHaC')) {

                            try {
                                if ($servico_doc->sigla == 'CdF') {
                                    $tipo_documento = DB::table('tb_tipo_documentos')->where('Codigo', 6)->first();
                                } elseif ($servico_doc->sigla == 'CdHaC') {
                                    $tipo_documento = DB::table('tb_tipo_documentos')->where('Codigo', 7)->first();
                                }
                                $hashcode = strtoupper(bin2hex(random_bytes(4)));
                                $documento['documento'] = $tipo_documento->Designacao;
                                $documento['ano_letivo'] = $anoCorrente;
                                $documento['utilizador'] = auth()->user()->codigo_importado;
                                $documento['DataRegisto'] = date('Y-m-d');
                                $documento['status'] = 'Ativo';

                                $documento['codigo_documento'] = $hashcode;

                                $documento['codigo_matricula'] = $codigo_matricula;
                                $documento['tipo_documento'] = $tipo_documento->Codigo;

                                $id_documento_validacao = DB::table('tb_documentos_uc')->insertGetId($documento);
                            } catch (\Exception $e) {
                                DB::rollback();
                                throw $e;
                            }

                            try {
                                DB::table('tb_pagamentos')->where('Codigo', $id_pag)
                                    ->update(['info_adicional' => $id_documento_validacao]);
                            } catch (\Exception $e) {
                                DB::rollback();
                                throw $e;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    DB::rollback();
                    throw $e;
                }
                // PAGAMENTO DE NEGOCIACAO DE DIVIDA
                try {

                    $response['mensagem'] = "Pagamento enviado com sucesso! Por favor,verifique a factura gerada pelo sistema e aguarde a validação";
                } catch (\Exception $e) {
                    DB::rollback();
                    throw $e;
                }

                if ($data['valor_depositado'] < number_format($fact_aluno->ValorAPagar, 2, '.', '') &&  $valorFatura->tipo_factura != 5 && $valorFatura->estado_factura != 2 && $servico_mensal) {
                    try {
                        DB::table('factura')->where('factura.Codigo', $valorFatura->codigo_fatura)->update(['estado' => 2]);
                    } catch (\Exception $e) {
                        DB::rollback();
                        throw $e;
                    }
                } elseif ($servico_mensal && $fatura_paga  &&  $fatura_paga->codigo_descricao != 5 && $fatura_paga->estado_factura == 2 && $fatura_paga->ano_factura == $anoCorrente && $fatura_paga->ValorAPagar > $total) {
                    $this->MultaValorDivida($codigo, $codigoDaFatura, $data['DataBanco'], $total);
                }
                
                $verificar_negociacao_factura = DB::table('negociacao_dividas')->where('codigo_fatura', $fatura_paga->codigo)->first();
                               
                if ($switch_troco) {
                   
                    if($fatura_paga->ValorAPagar > $fatura_paga->ValorEntregue){
                    
                        $novo_saldo_aluno = ($data['valor_depositado'] + $saldo_novo) - ($fatura_paga->ValorAPagar - $fatura_paga->ValorEntregue);
                        $saldo_a_pagar = $fatura_paga->ValorAPagar - $fatura_paga->ValorEntregue;
                        
                    }
                    
                    if($fatura_paga->ValorAPagar == $fatura_paga->ValorEntregue){
                        $novo_saldo_aluno = ($data['valor_depositado'] + $saldo_novo)  - $fatura_paga->ValorAPagar;
                        $saldo_a_pagar = ($data['valor_depositado'] + $saldo_novo) - $novo_saldo_aluno;
                    }
                    
                    if($fatura_paga->ValorAPagar < $fatura_paga->ValorEntregue){
                        
                        $novo_saldo_aluno = ($data['valor_depositado'] + $saldo_novo) - $fatura_paga->ValorAPagar;
                        $saldo_a_pagar = $fatura_paga->ValorAPagar;
                    
                    }
                    
                    if($saldo_novo > 0){
                        
                        $novo_saldo_aluno = ($data['valor_depositado'] + $saldo_novo) - $fatura_paga->ValorAPagar;
                        $saldo_a_pagar = $fatura_paga->ValorAPagar;
                    
                    }
                    
                    // dd( "sssaldo:" . $novo_saldo_aluno, "pagar:" .$saldo_a_pagar, "com saldo");       
            
                    if($verificar_negociacao_factura && $verificar_negociacao_factura->primeiroValorApagar >= 0 ){
                        
                        if($fatura_paga->ValorAPagar >= $fatura_paga->ValorEntregue){
                            $saldo_a_pagar = $verificar_negociacao_factura->primeiroValorApagar;
                        }else{
                            $saldo_a_pagar = $verificar_negociacao_factura->primeiroValorApagar;
                        }
                        
                        if($saldo_a_pagar >= ($data['valor_depositado'] + $saldo_novo) )  {
                            $novo_saldo_aluno = $saldo_a_pagar - ($data['valor_depositado'] + $saldo_novo) ;
                        }  else{
                            $novo_saldo_aluno = ($data['valor_depositado'] + $saldo_novo)  - $saldo_a_pagar;
                        }
                        
                        if($novo_saldo_aluno < 0){
                            $novo_saldo_aluno = 0;
                        }
                    }
       
                    
                    try {
                        DB::table('tb_preinscricao')->where('tb_preinscricao.Codigo', $codigo)->update(['saldo' => $novo_saldo_aluno]);
                    } catch (\Illuminate\Database\QueryException $e) {
                        DB::rollback();
                        return Response()->json($e->getMessage());
                    }
                    
                    $deposito = DB::table('tb_valor_alunos')
                        ->where('codigo_matricula_id', $codigo_matricula)
                        ->orderBy('codigo', 'DESC')->first();
               
                    $dados_deposito = [
                        'codigo_matricula_id' => $codigo_matricula,
                        'Codigo_PreInscricao' => $codigo,
                        'valor_depositar' => $novo_saldo_aluno,
                        'caixa_id' => $caixas->codigo,
                        'status' => 'pendente',
                        'saldo_apos_movimento' => $deposito ? ($deposito->saldo_apos_movimento + $novo_saldo_aluno) : 0,
                        'created_by' => auth()->user()->codigo_importado,
                        'ano_lectivo_id' => $fatura_paga->ano_factura,
                        'data_movimento' => date("Y-m-d"),
                    ];
                    
                    $deposito = DB::table('tb_valor_alunos')->insertGetId($dados_deposito);

                    $novo_saldo_aluno = ($data['valor_depositado'])-($fatura_paga->ValorAPagar-$fatura_paga->ValorEntregue);
                    
                    if($novo_saldo_aluno < 0){
                        $novo_saldo_aluno = 0;
                    }

                    $troco_do_aluno = $novo_saldo_aluno;

                    DB::table('factura')->where('Codigo', $fatura_paga->codigo)->update(['Troco' => $troco_do_aluno]);

                    try {
                        $caixas = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
                       
                        if (filled($caixas)) {
                            $movimento = MovimentoCaixa::where('caixa_id', $caixas->codigo)->where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();

                            $update = MovimentoCaixa::findOrFail($movimento->codigo);
                            $update->valor_arrecadado_depositos = ($update->valor_arrecadado_depositos + $novo_saldo_aluno);
                            $update->valor_arrecadado_pagamento = ($update->valor_arrecadado_pagamento + $saldo_a_pagar);
                            $update->valor_facturado_pagamento = ($update->valor_facturado_pagamento + $saldo_a_pagar);
                            $update->valor_arrecadado_total = ($update->valor_arrecadado_depositos + $update->valor_facturado_pagamento);
                            $update->update();
                            // dd($troco_do_aluno, $saldo_a_pagar,$update->valor_arrecadado_pagamento, $update->valor_facturado_pagamento,$update->valor_arrecadado_total,$valor_pago_com_saldo);
                        } else {
                            $result['message'] = 'Por valor! faça abertura do caixa para efectuar o pagamento.';
                            return response()->json($result, 201);
                        }
                    } catch (\Illuminate\Database\QueryException $e) {
                        DB::rollback();
                        return Response()->json($e->getMessage(), 201);
                    }
                } else {   
                   
                    // pagamento de facturas com um parte já paga
                    if($fatura_paga->ValorAPagar > $fatura_paga->ValorEntregue){
                    
                        $troco_do_aluno = ($data['valor_depositado'] + $saldo_novo)  - ($fatura_paga->ValorAPagar - $fatura_paga->ValorEntregue);
                        $saldo_a_pagar = $fatura_paga->ValorAPagar - $fatura_paga->ValorEntregue;
                        
                    }
                    
                    // pagamento normal
                    if($fatura_paga->ValorAPagar == $fatura_paga->ValorEntregue){
                        $troco_do_aluno = ($data['valor_depositado'] + $saldo_novo)  - $fatura_paga->ValorAPagar;
                        $saldo_a_pagar = $fatura_paga->ValorAPagar;
                    }
                    
                    if($saldo_novo > 0){
                        $troco_do_aluno = ($data['valor_depositado'] + $saldo_novo)  - $fatura_paga->ValorAPagar;
                        $saldo_a_pagar = $fatura_paga->ValorAPagar;
                    }
                
                    //
                    if($fatura_paga->ValorAPagar < $fatura_paga->ValorEntregue){
                        
                        $troco_do_aluno = ($data['valor_depositado'] + $saldo_novo)  - $fatura_paga->ValorAPagar;
                        $saldo_a_pagar = $fatura_paga->ValorAPagar;
                    
                    }
                    
                    if($verificar_negociacao_factura && $verificar_negociacao_factura->primeiroValorApagar >= 0 ){
                      
                        if($fatura_paga->ValorAPagar >= $fatura_paga->ValorEntregue){
                            $saldo_a_pagar = $verificar_negociacao_factura->primeiroValorApagar;
                        }else{
                            $saldo_a_pagar = $verificar_negociacao_factura->primeiroValorApagar;
                        }
                        
                        if($saldo_a_pagar >= ($data['valor_depositado'] + $saldo_novo) )  {
                            $troco_do_aluno = $saldo_a_pagar - ($data['valor_depositado'] + $saldo_novo) ;
                        }  else{
                            $troco_do_aluno = ($data['valor_depositado'] + $saldo_novo)  - $saldo_a_pagar;
                        }
                    }

                    if($data['valor_depositado'] < ($fatura_paga->ValorAPagar-$fatura_paga->ValorEntregue)){
                        $troco_do_aluno =  ($data['valor_depositado'] + $saldo_novo)-($fatura_paga->ValorAPagar-$fatura_paga->ValorEntregue);
                    }else{
                        $troco_do_aluno =  ($data['valor_depositado'])-($fatura_paga->ValorAPagar-$fatura_paga->ValorEntregue);
                    }
                    
                    if($troco_do_aluno < 0){
                        $troco_do_aluno = 0;
                    }

                    try {
                        DB::table('factura')->where('Codigo', $fatura_paga->codigo)->update(['Troco' => $troco_do_aluno]);
                    } catch (\Illuminate\Database\QueryException $e) {
                        DB::rollback();
                        return Response()->json($e->getMessage());
                    }
                    
                    try {
                        $caixas = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();

                        if (filled($caixas)) {
                            $movimento = MovimentoCaixa::where('caixa_id', $caixas->codigo)->where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
                            $update = MovimentoCaixa::findOrFail($movimento->codigo);

                            if($data['valor_depositado'] >= ($fatura_paga->ValorAPagar-$fatura_paga->ValorEntregue)){
                                $update->valor_arrecadado_pagamento = ($update->valor_arrecadado_pagamento + $data['valor_depositado'])-$troco_do_aluno;
                                $update->valor_facturado_pagamento = ($update->valor_facturado_pagamento + $data['valor_depositado'])-$troco_do_aluno;
                                $update->valor_arrecadado_total = ($update->valor_arrecadado_total + $data['valor_depositado'])-$troco_do_aluno;
                                $update->update();
                            }else {
                                $update->valor_arrecadado_pagamento = ($update->valor_arrecadado_pagamento + $data['valor_depositado']);
                                $update->valor_facturado_pagamento = ($update->valor_facturado_pagamento + $data['valor_depositado']);
                                $update->valor_arrecadado_total = ($update->valor_arrecadado_total + $data['valor_depositado']);
                                $update->update();
                            }
                        } else {
                            $result['message'] = 'Por valor! faça abertura do caixa para efectuar o pagamento.';
                            return response()->json($result, 201);
                        }
                    } catch (\Illuminate\Database\QueryException $e) {
                        DB::rollback();
                        return Response()->json($e->getMessage(), 201);
                    }   
                }
                
                try {
                    
                    if($verificar_negociacao_factura){
                        if($verificar_negociacao_factura->primeiroValorApagar + $fatura_paga->ValorEntregue >= $verificar_negociacao_factura->valor_divida){
                            $estado = 1;
                            $total_pagamento = $verificar_negociacao_factura->valor_divida;
                        }else{
                            $estado = 2;
                            $total_pagamento = $verificar_negociacao_factura->primeiroValorApagar;
                        }
                    }else{
                        $estado = 1;
                        $total_pagamento = $fatura_paga->ValorAPagar;
                    }
                                       
                    DB::table('factura')->where('Codigo', $fatura_paga->codigo)->update(['ValorEntregue' => $total_pagamento, 'estado' => $estado]);
                } catch (\Illuminate\Database\QueryException $e) {
                    DB::rollback();
                    return Response()->json($e->getMessage());
                }
                
            }
        }
       
        DB::commit();

        try {
            $this->pagamentoService->validarPagamentoAdmin($id_pag, Auth::user()->pk_utilizador);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollback();
            $result['message'] = $e->getMessage();
            return Response()->json($result['message']);
        }

        try {
            if(($fatura_paga->codigo_descricao == 1) || ($fatura_paga->codigo_descricao == 9) || ($fatura_paga->codigo_descricao == 11)){
                $troc = Factura::find($codigoDaFatura);
                $troc->update(['Troco' =>$troco_do_aluno]);
            }
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollback();
            return Response()->json($e->getMessage());
        }

        return Response()->json($response);
    }


    public function salvarPagamentoComSaldo(Request $request, $referencia, $user_id)
    {
        
        $caixas = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();
        if (!filled($caixas)) {
            $result['message'] = 'Por valor! faça abertura do caixa para efectuar o pagamento.';
            return response()->json($result, 201);
        }

        $anoCorrente = $this->anoAtualPrincipal->index();

        $ano = DB::table('tb_ano_lectivo')
            ->where('Codigo', $anoCorrente)
            ->first();

        $estudante = DB::table('tb_preinscricao')
            ->select('Codigo')
            ->where('user_id', $user_id)
            ->first();

        $fact_aluno = DB::table('factura')
            ->join('tb_matriculas', 'tb_matriculas.Codigo', 'factura.CodigoMatricula')
            ->join('tb_admissao', 'tb_admissao.Codigo', '=', 'tb_matriculas.Codigo_Aluno')
            ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
            ->select(
                'factura.Codigo',
                'factura.ValorAPagar',
                'factura.ano_lectivo',
                'factura.ValorEntregue',
                'factura.codigo_descricao',
                'factura.estado as estado_factura',
                'CodigoMatricula',
                'tb_preinscricao.Codigo as codigo_preinscricao'
            )
            ->where('factura.Codigo', $referencia)
            ->where('tb_preinscricao.Codigo', $estudante->Codigo)
            ->first();

        $saldo = DB::table('tb_preinscricao')
            ->select('Codigo', 'saldo', 'saldo_anterior')
            ->where('Codigo', $estudante->Codigo)
            ->first();

        $servico_mensal = DB::table('factura_items')
            ->join('factura', 'factura_items.CodigoFactura', '=', 'factura.Codigo')
            ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', '=', 'factura_items.CodigoProduto')
            ->select('*')
            ->where('factura.Codigo', $fact_aluno->Codigo)
            ->where('tb_tipo_servicos.TipoServico', 'Mensal')
            ->first();

        $dados_negociacao = '';

        if ($fact_aluno && $fact_aluno->codigo_descricao == 5) {
            $dados_negociacao = DB::table('factura')->join('tb_matriculas', 'tb_matriculas.Codigo', 'factura.CodigoMatricula')->join('tb_admissao', 'tb_admissao.Codigo', '=', 'tb_matriculas.Codigo_Aluno')->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')->join('negociacao_dividas', 'negociacao_dividas.codigo_fatura', '=', 'factura.Codigo')->join('factura_items', 'factura_items.CodigoFactura', '=', 'factura.Codigo')->where('factura.Codigo', $fact_aluno->Codigo)->where('tb_preinscricao.Codigo', $estudante->Codigo)->select('factura.Codigo', 'factura.ValorAPagar', 'factura.ano_lectivo', 'factura.codigo_descricao as codigo_descricao', 'negociacao_dividas.mesesQuitar as qtdMeses')->first();
        }
        $Somapagamentos = DB::table('factura')->join('tb_matriculas', 'tb_matriculas.Codigo', 'factura.CodigoMatricula')->join('tb_admissao', 'tb_admissao.Codigo', '=', 'tb_matriculas.Codigo_Aluno')->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')->join('tb_pagamentos', 'tb_pagamentos.codigo_factura', '=', 'factura.Codigo')->where('factura.Codigo', $referencia)->where('tb_preinscricao.Codigo', $estudante->Codigo)->select('tb_pagamentos.valor_depositado as valor_depositado', 'factura.ValorAPagar as ValorAPagar', 'factura.codigo_descricao', 'factura.Codigo', 'factura.ValorEntregue as ValorEntregue', 'factura.estado as estado_factura', 'factura.ano_lectivo as ano_factura')->get();

        $valor_falta = 0;
        $valor_falta = $fact_aluno->ValorAPagar - $fact_aluno->ValorEntregue;

        DB::beginTransaction();
        try {
            $pagamento['Data'] = date('Y-m-d');
            $pagamento['AnoLectivo'] = $fact_aluno->ano_lectivo;
            $pagamento['Codigo_PreInscricao'] = $fact_aluno->codigo_preinscricao;
            $pagamento['valor_depositado'] = $saldo->saldo;
            $pagamento['Totalgeral'] = $fact_aluno->ValorAPagar;
            $pagamento['DataRegisto'] = date('Y-m-d H:i:s');
            $pagamento['codigo_factura'] = $fact_aluno->Codigo;
            $pagamento['caixa_id'] = $caixas->codigo;
            $pagamento['status_pagamento'] = 'pendente';
            $pagamento['estado'] = ($fact_aluno->codigo_descricao == 2 || $fact_aluno->codigo_descricao == 4 || $fact_aluno->codigo_descricao == 5 || $fact_aluno->codigo_descricao == 10) ? 1 : 0;
            $pagamento['Observacao'] =  'Pagamento efectuado com Reserva no Mutue Cash';
            $pagamento['forma_pagamento'] =  '6';
            $pagamento['corrente'] = 1;
            $pagamento['fk_utilizador'] =  auth()->user()->codigo_importado;
            $pagamento['Utilizador'] =  auth()->user()->codigo_importado;
            $id_pag = DB::table('tb_pagamentos')->insertGetId($pagamento);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollback();
            return Response()->json($e->getMessage());
        }
        //Atualizar Valor Entregue
        try {
            $this->pagamentoService->salvarPagamMovimentoConta($id_pag, $fact_aluno->CodigoMatricula);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }

        try {
            $dado_fatura = DB::table('tb_pagamentos')
                ->select(DB::Raw('sum(valor_depositado) total_pago'))
                ->where('tb_pagamentos.codigo_factura', $fact_aluno->Codigo)
                ->where('tb_pagamentos.estado', 1)
                ->first();

            DB::table('factura')->where('factura.Codigo', $fact_aluno->Codigo)->update([
                'ValorEntregue' => $dado_fatura->total_pago,
                'obs' => 'Pagamento feito com Reserva no mutue Cash',
                'estado' => 1
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollback();
            return Response()->json($e->getMessage());
        }

        try {

            $anoLectivo = DB::table('tb_ano_lectivo')
                ->where('Codigo', $fact_aluno->ano_lectivo)
                ->first();

            $factura_items = DB::table('factura_items')
                ->join('factura', 'factura_items.CodigoFactura', '=', 'factura.Codigo')
                ->select('factura_items.*')
                ->where('factura.Codigo', $fact_aluno->Codigo)
                ->get();

            $factura_items_preco = DB::table('factura_items')
                ->join('factura', 'factura_items.CodigoFactura', '=', 'factura.Codigo')
                ->select('factura_items.*')
                ->where('factura.Codigo', $fact_aluno->Codigo)
                ->first();

            $array_fatura = json_decode($factura_items, true);
            $desconto_mes = 0;

            foreach ($array_fatura as $key => $fac) {

                $id_mes = null;
                $mes = null;
                $desconto_mes = $fac['descontoProduto'];
                if ($servico_mensal && (int)$anoLectivo->Designacao <= 2019) {

                    $mes = $fac['Mes'];
                    // $id_mes =DB::table('meses')->where('mes', $fac['Mes'])->first()->codigo;

                    if ($fac['Mes'] == 'MAR') {
                        $id_mes = 1;
                    } elseif ($fac['Mes'] == 'ABR') {
                        $id_mes = 2;
                    } elseif ($fac['Mes'] == 'MAI') {
                        $id_mes = 3;
                    } elseif ($fac['Mes'] == 'JUN') {
                        $id_mes = 4;
                    } elseif ($fac['Mes'] == 'JUL') {
                        $id_mes = 5;
                    } elseif ($fac['Mes'] == 'AGO') {
                        $id_mes = 6;
                    } elseif ($fac['Mes'] == 'SET') {
                        $id_mes = 7;
                    } elseif ($fac['Mes'] == 'OUT') {
                        $id_mes = 8;
                    } elseif ($fac['Mes'] == 'NOV') {
                        $id_mes = 9;
                    } elseif ($fac['Mes'] == 'DEZ') {
                        $id_mes = 10;
                    }
                }

                //ADICIONADO POR PATRÍCIO EM 05.04.2, porque mandava a designação do ano lectivo corrente no campo Ano em pagamentosi
                if ($fact_aluno && $fact_aluno->codigo_descricao == 5) {

                    $ano_lectivo = DB::table('tb_ano_lectivo')
                        ->where('Codigo', $fac['codigo_anoLectivo'])
                        ->first()->Designacao;

                    if ((int)$ano_lectivo <= 2019) {
                        $mes = $fac['Mes'];

                        if ($fac['Mes'] == 'MAR') {
                            $id_mes = 1;
                        } elseif ($fac['Mes'] == 'ABR') {
                            $id_mes = 2;
                        } elseif ($fac['Mes'] == 'MAI') {
                            $id_mes = 3;
                        } elseif ($fac['Mes'] == 'JUN') {
                            $id_mes = 4;
                        } elseif ($fac['Mes'] == 'JUL') {
                            $id_mes = 5;
                        } elseif ($fac['Mes'] == 'AGO') {
                            $id_mes = 6;
                        } elseif ($fac['Mes'] == 'SET') {
                            $id_mes = 7;
                        } elseif ($fac['Mes'] == 'OUT') {
                            $id_mes = 8;
                        } elseif ($fac['Mes'] == 'NOV') {
                            $id_mes = 9;
                        } elseif ($fac['Mes'] == 'DEZ') {
                            $id_mes = 10;
                        }
                    }

                    if (($key <= ($dados_negociacao->qtdMeses - 1)) && $fact_aluno->estado_factura != 2) {

                        DB::table('tb_pagamentosi')->insert([
                            'Codigo_Pagamento' => $id_pag,
                            'Codigo_Servico' => $fac['CodigoProduto'],
                            'Valor_Pago' => $fac['Total'],
                            'Quantidade' => 1,
                            'Valor_Total' => $fac['Total'],
                            'Multa' => $fac['Multa'],
                            'Deconnto' => $desconto_mes,
                            'Ano' => $ano_lectivo,
                            'mes_id' => $id_mes,
                            'Mes' => $mes,
                            'mes_temp_id' => $fac['mes_temp_id']
                        ]);
                    } elseif ($fact_aluno->estado_factura == 2 || ($Somapagamentos && $Somapagamentos->sum('valor_depositado') < $Somapagamentos->pluck('ValorAPagar')->first())) {
                        DB::table('tb_pagamentosi')->insert([
                            'Codigo_Pagamento' => $id_pag,
                            'Codigo_Servico' => $fac['CodigoProduto'],
                            'Valor_Pago' => $fac['Total'],
                            'Quantidade' => 1,
                            'Valor_Total' => $fac['Total'],
                            'Multa' => $fac['Multa'],
                            'Deconnto' => $desconto_mes,
                            'Ano' => $ano_lectivo,
                            'mes_id' => $id_mes,
                            'Mes' => $mes,
                            'mes_temp_id' =>
                            $fac['mes_temp_id']
                        ]);
                    }
                } else {
                    DB::table('tb_pagamentosi')->insert(
                        [
                            'Codigo_Pagamento' => $id_pag,
                            'Codigo_Servico' => $fac['CodigoProduto'],
                            'Valor_Pago' => $saldo->saldo,
                            'Quantidade' => 1,
                            'Valor_Total' => $fac['Total'],
                            'Multa' => $fac['Multa'],
                            'Deconnto' => $desconto_mes,
                            'Ano' => $anoLectivo->Designacao,
                            'mes_id' => $id_mes,
                            'Mes' => $mes,
                            'mes_temp_id' => $fac['mes_temp_id']
                        ]
                    );
                }
            }

            $id_documento_validacao = '';
            $servico_doc = DB::table('tb_tipo_servicos')->where('codigo_ano_lectivo', $anoCorrente)
                ->where('Codigo', $fac['CodigoProduto'])->select('*')->first();

            if ($servico_doc && ($servico_doc->sigla == 'CdF' || $servico_doc->sigla == 'CdHaC')) {

                try {
                    if ($servico_doc->sigla == 'CdF') {
                        $tipo_documento = DB::table('tb_tipo_documentos')->where('Codigo', 6)->first();
                    } elseif ($servico_doc->sigla == 'CdHaC') {
                        $tipo_documento = DB::table('tb_tipo_documentos')->where('Codigo', 7)->first();
                    }
                    $hashcode = strtoupper(bin2hex(random_bytes(4)));
                    $documento['documento'] = $tipo_documento->Designacao;
                    $documento['ano_letivo'] = $anoCorrente;
                    $documento['utilizador'] = auth()->user()->codigo_importado;
                    $documento['DataRegisto'] = date('Y-m-d');
                    $documento['status'] = 'Ativo';
                    $documento['codigo_documento'] = $hashcode;
                    $documento['codigo_matricula'] = $fact_aluno->CodigoMatricula;
                    $documento['tipo_documento'] = $tipo_documento->Codigo;

                    $id_documento_validacao = DB::table('tb_documentos_uc')->insertGetId($documento);
                } catch (\Exception $e) {
                    DB::rollback();
                    throw $e;
                }
                try {
                    DB::table('tb_pagamentos')->where('Codigo', $id_pag)
                        ->update(['info_adicional' => $id_documento_validacao]);
                } catch (\Exception $e) {
                    DB::rollback();
                    throw $e;
                }
            }
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
        $saldo_actual = $saldo->saldo - $fact_aluno->ValorAPagar;
        try {
            DB::table('tb_preinscricao')->where('Codigo', $saldo->Codigo)->update([
                'saldo' => $saldo_actual,
                'saldo_anterior' => ($saldo->saldo_anterior + $saldo_actual)
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
        try {
            DB::table('factura_items')->where('factura_items.CodigoFactura', $fact_aluno->Codigo)
                ->update([
                    'valor_pago' => $factura_items_preco->Total,
                    'estado' => 1
                ]);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }

        DB::commit();

        try {
            $this->pagamentoService->validarPagamentoAdmin($id_pag, Auth::user()->pk_utilizador);
            // if(($fact_aluno->codigo_descricao == 1) || ($fact_aluno->codigo_descricao == 9) || ($fact_aluno->codigo_descricao == 11)){
            //     $this->pagamentoService->validarPagamentoAdmin($id_pag, Auth::user()->pk_utilizador);
            // }
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollback();
            $result['message'] = $e->getMessage();
            return Response()->json($result['message']);
        }

        return Response()->json('Pagamento enviado com sucesso!');
    }

    public function cobrarFaturaNegociacao($codigo_matricula)
    {
        $negociacao = DB::table('factura')->join('tb_matriculas', 'tb_matriculas.Codigo', 'factura.CodigoMatricula')
            ->join('tb_admissao', 'tb_admissao.Codigo', '=', 'tb_matriculas.Codigo_Aluno')
            ->join('tb_pagamentos', 'tb_pagamentos.codigo_factura', '=', 'factura.Codigo')
            ->join('negociacao_dividas', 'negociacao_dividas.codigo_fatura', '=', 'factura.Codigo')
            ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
            ->where('factura.CodigoMatricula', $codigo_matricula)->where('factura.codigo_descricao', 5)
            ->where('factura.estado', '!=', 3)
            ->where('factura.estado', 2)
            ->where('negociacao_dividas.estado', 1)
            ->select('negociacao_dividas.id_mes_final as mes_final', 'negociacao_dividas.created_at as data_negociacao', 'factura.ValorEntregue as ValorEntregue', 'factura.ValorAPagar as ValorAPagar', 'factura.Codigo as codigo_fatura', 'negociacao_dividas.estado as estado_negociacao', 'negociacao_dividas.id_mes_inicial as mes_inicial')
            ->first();

        if ($negociacao && $negociacao->estado_negociacao == 1) {
            $horaPrazo = date('H:i:s', strtotime($negociacao->data_negociacao));
            $diaPrazo = date('d', strtotime($negociacao->data_negociacao));
            $zero = 0;
            $mesFinal = '';

            if ($negociacao->mes_final >= 1 && $negociacao->mes_final <= 9) {
                $mesFinal = $zero . $negociacao->mes_final;
            } elseif ($mesFinal >= 10 && $mesFinal <= 12) {

                $mesFinal = $negociacao->mes_final;
            }
            $AnoPrazo = date('Y');
            if ($negociacao->mes_inicial >= 8) {

                $AnoPrazo = date('Y') + 1;
            }

            $dataPrazo = date($AnoPrazo . '-' . $mesFinal . '-' . $diaPrazo . ' ' . $horaPrazo);

            date_default_timezone_set('Africa/Luanda');

            $dataAtual = date('Y-m-d H:i:s');

            if (($dataAtual > $dataPrazo) && ($negociacao->ValorEntregue < $negociacao->ValorAPagar)) {
                return $negociacao->codigo_fatura;
            } else {

                return 0;
            }
        } else {

            return 0;
        }
        return 0;
    }

    public function pagamentoRejeitado($anoCodigo, $anoDesignacao, $id)
    {
        $resultado = '';
        $anoCorrente = $this->anoAtualPrincipal->index();
        $ano = DB::table('tb_ano_lectivo')->where('Codigo', $anoCorrente)->first();

        $rejeitado = DB::table('tb_pagamentos')->join('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', '=', 'tb_pagamentos.Codigo')
            ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', '=', 'tb_pagamentosi.Codigo_Servico')
            ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_pagamentos.Codigo_PreInscricao')
            ->leftJoin('factura', 'factura.Codigo', 'tb_pagamentos.codigo_factura')/*->where('factura.codigo_descricao', '!=', 5)*/
            ->where('tb_preinscricao.Codigo', $id)

            ->where('tb_pagamentos.corrente', 1)
            ->where('tb_pagamentos.estado', 2)
            ->select('tb_pagamentos.estado')
            ->first();
        if ($rejeitado) {

            $resultado = $rejeitado;
        } else {

            $resultado = '';
        }
        return $resultado;
    }

    public function pagamentosPreinscricao(Request $request)
    {

        $anoLectivo = DB::table('tb_ano_lectivo')
            ->where('estado', 'Activo')
            ->first();

        $data['codigo_preinscricao'] = $request->codigo_inscricao;
        $data['valor_depositado'] = $request->valor_a_depositar;


        $preinscriao = Preinscricao::where('Codigo', $data['codigo_preinscricao'])->orWhere('Bilhete_Identidade', $data['codigo_preinscricao'])->first();

        $taxa_servico = DB::table('tb_tipo_servicos')->where('Codigo', $this->pagamentoService->taxaServicoPorSigla("TdEdA"))->first();
        // definir taxa para pos-graduacao
        if ($preinscriao && $preinscriao->codigo_tipo_candidatura != 1) {
            $taxa_servico = DB::table('tb_tipo_servicos')->where('Codigo', $this->pagamentoService->taxaServicoPorSigla("TdIMeP"))->first();
        }

        $keygen = Keygen::numeric(9)->generate();

        $codigo_inscricao = $preinscriao->Codigo;

        DB::beginTransaction();
        if ($data['valor_depositado'] < $taxa_servico->Preco) {

            return response()->json("O valor introduzido não é permitido para
            realizar a operação! O valor não pode ser inferior ao valor do serviço!", 201);
        } else {

            try {

                $factura = Factura::create([
                    'DataFactura' => Carbon::now(),
                    'TotalPreco' =>  $taxa_servico->Preco,
                    'codigo_preinscricao' => $codigo_inscricao,
                    'polo_id' => 1,
                    'Referencia' => $keygen,
                    'ValorAPagar' => $taxa_servico->Preco,
                    'ValorEntregue' => 0,
                    'Descricao' => $taxa_servico->Descricao,
                    'codigo_descricao' => $taxa_servico->sigla == 'TdIdP' ? 11 : 9, //Exame de acesso
                    'canal' => 3, //Portal 1
                    'estado' => 0,
                    'ano_lectivo' => $anoLectivo->Codigo,
                    'obs' => 'Pagamento feito a cash'
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                DB::rollback();
                return Response()->json('Ocorreu um erro ao efectuar o pagamento(0f1)!', 201);
            }
            try {
                $this->faturaService->salvarFacturaMovimentoConta($factura->Codigo);
            } catch (\Exception $e) {
            }

            try {

                $factura_itens =  FacturaItem::create([
                    'CodigoProduto' => $taxa_servico->Codigo, //Exames de Accesso
                    'CodigoFactura' => $factura->Codigo,
                    'preco' => $taxa_servico->Preco,
                    'Total' => $taxa_servico->Preco,  //fucturamente multiplicar pela quantidade
                    'codigo_anoLectivo' => $anoLectivo->Codigo
                ]);
            } catch (\Illuminate\Database\QueryException $e) {

                DB::rollback();
                return Response()->json('Ocorreu um erro ao efectuar o pagamento(0fi2)!', 201);
            }

            try {
                $data['Data'] = date('Y-m-d');
                $data['AnoLectivo'] = $anoLectivo->Codigo;
                $data['Totalgeral'] = $taxa_servico->Preco;
                $data['Codigo_PreInscricao'] = $codigo_inscricao;
                $data['DataRegisto'] = Carbon::now();
                $data['canal'] = 3;
                $data['estado'] = 0;
                $data['codigo_factura'] = $factura->Codigo;
                $data['Observacao'] = "Pagamento efectuado por Cash";
                $data['fk_utilizador'] = auth()->user()->codigo_importado;
                $data['Utilizador'] = auth()->user()->codigo_importado;
                $data['forma_pagamento'] = 6;
                $data['N_Operacao_Bancaria'] = rand(0, $factura->Codigo) . time();
                $pagamento = Pagamento::create($data);
            } catch (\Illuminate\Database\QueryException $e) {
                DB::rollback();
                return Response()->json('Ocorreu um erro ao efectuar o pagamento(0p3)!', 201);
            }

            try {
                $data1['Codigo_Pagamento'] = $pagamento->Codigo;
                $data1['Codigo_Servico'] = $taxa_servico->Codigo; //Exames de Acessos
                $data1['Valor_Pago'] = $taxa_servico->Preco;
                $data1['Valor_Total'] = $taxa_servico->Preco;
                $data1['Ano'] = $anoLectivo->Designacao;
                $data1['Quantidade'] = 1;
                $data1['Estado'] = 1;
                $pagamentosi = PagamentoItems::create($data1);
            } catch (\Illuminate\Database\QueryException $e) {

                DB::rollback();
                return Response()->json('Ocorreu um erro ao efectuar o pagamento(0pi4)!', 201);
                //return Response()->json($e->getMessage(),201);
            }
            try {
                $caixas = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();

                if (filled($caixas)) {
                    $movimento = MovimentoCaixa::where('caixa_id', $caixas->codigo)->where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();

                    $update = MovimentoCaixa::findOrFail($movimento->codigo);
                    // $update->valor_arrecadado_depositos = $update->valor_arrecadado_depositos + $data['valor_depositado'];
                    $update->valor_arrecadado_pagamento = $update->valor_arrecadado_pagamento + $data['valor_depositado'];
                    $update->valor_facturado_pagamento = $update->valor_facturado_pagamento +  $taxa_servico->Preco;
                    $update->valor_arrecadado_total = ($update->valor_facturado_pagamento);
                    $update->update();
                } else {
                    $result['message'] = 'Por valor! faça abertura do caixa para efectuar o pagamento.';
                    return response()->json($result, 201);
                }
            } catch (\Illuminate\Database\QueryException $e) {
                DB::rollback();
                return Response()->json($e->getMessage(), 201);
            }

            $resultado['msg'] = 'Pagamento efectuado com sucesso!';
            $resultado['codigo_factura'] = $factura->Codigo;

            DB::commit();

            try {
                $this->pagamentoService->validarPagamentoAdmin($pagamento->Codigo, Auth::user()->pk_utilizador);
            } catch (\Illuminate\Database\QueryException $e) {
                DB::rollback();
                $resultado['msg'] = $e->getMessage();
                return Response()->json($resultado);
            }

            return response()->json($resultado);
        }
    }


    public function faturaDiversos(Request $request, $codigo_matricula)
    {
        // dd("Factura diverso");
        // verificar se o caixa esta bloqueado
        $caixa = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();

        if($caixa && $caixa->bloqueio == 'Y'){
            return redirect()->route('mc.bloquear-caixa');
        }

        $aluno = Matricula::with(['admissao.preinscricao'])->findOrFail($codigo_matricula);

        $alunoRepository = $this->alunoRepository->dadosAlunoLogado($aluno->admissao->preinscricao->user_id);

        $data1 = $request->pagamento;
        $data1 = json_decode($data1, true);

        $data = $request->fatura_item;
        $data = json_decode($data, true);
        $anoCorrente = $this->anoAtualPrincipal->index();
        $ano = json_decode($request->anoLectivo, true);
        $pagRejeitado = '';

        $preinscricao = DB::table('tb_preinscricao')->where('user_id', $aluno->admissao->preinscricao->user_id)->first();

        $pagmnt_total_com_saldo = null;

        $matricula = DB::table('tb_preinscricao')
            ->join('tb_admissao', 'tb_admissao.pre_incricao', '=', 'tb_preinscricao.Codigo')
            ->join('tb_matriculas', 'tb_matriculas.Codigo_Aluno', '=', 'tb_admissao.codigo')
            ->select(
                'tb_matriculas.Codigo as codigo_matricula',
                'tb_matriculas.estado_matricula',
                'tb_preinscricao.AlunoCacuaco as aluno_Cacuaco',
                'tb_matriculas.Codigo_Curso as codigo_curso_matricula'
            )->where('tb_preinscricao.Codigo', $aluno->admissao->preinscricao->Codigo)->first();

        $ano1 = DB::table('tb_ano_lectivo')->where('Codigo', $anoCorrente)->first(); // redundancia

        $bolsa = DB::table('tb_bolseiros')
            ->where('tb_bolseiros.codigo_matricula', $matricula->codigo_matricula)
            ->where('tb_bolseiros.status', '!=', 1)
            ->first();

        /* COMENTEI NO DIA 04.11.2022, POIS NÃO CONSIDERAVA A PROPINA DO CURSO DA MATRICULA EM ALGUNS CASOS*/
        // $curso_d = DB::table('tb_cursos')->join('tb_preinscricao', 'tb_cursos.Codigo', '=', 'tb_preinscricao.Curso_Candidatura')->select('tb_cursos.Designacao as curso')->where('tb_preinscricao.Codigo', $preinscricao->Codigo)->first();
        $curso_d = DB::table('tb_cursos')->select('tb_cursos.Designacao as curso')->where('Codigo', $matricula->codigo_curso_matricula)->first();

        $propina_d = DB::table('tb_tipo_servicos')
            ->select('Descricao', 'Preco', 'TipoServico', 'Codigo', 'valor_anterior')
            ->where('Descricao', 'like', 'propina ' . $curso_d->curso . '%')
            ->where('cacuaco', $matricula->aluno_Cacuaco)
            ->where('codigo_ano_lectivo', $anoCorrente)
            ->first();

        $keygen = Keygen::numeric(9)->generate();
        $referencia = $keygen;

        DB::beginTransaction();

        $caixas = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();

        if (blank($caixas)) {
            $result['message'] = 'Por valor! faça abertura do caixa para efectuar o pagamento.';
            return response()->json($result, 201);
        }

        try {
            $anoDesignacao = DB::table('tb_ano_lectivo')
                ->where('Codigo', $ano)
                ->first();

            $amount = 0;
            $multa = 0;
            $valor_apagar = 0;
            $desconto_total = 0;

            $temDivida = $this->cobrarFaturaNegociacao($matricula->codigo_matricula);

            if ($temDivida != 0 && $anoDesignacao->Codigo == $anoCorrente) {
                $result['message'] = 'Caro estudante, tem uma factura de negociação de dívida que não foi liquidada totalmente. Número da factura: ' . $temDivida;
                return response()->json($result, 201);
            }

            if ($this->pagamentoRejeitado($anoDesignacao->Codigo, $anoDesignacao->Designacao, $preinscricao->Codigo)) {
                $result['message'] = 'Caro estudante, não lhe é permitido efectuar a operação. Tem um pagamento rejeitado!';
                return response()->json($result, 201);
            }

            foreach ($data as $key => $value) {
                $declaracao_divida = DB::table('tb_tipo_servicos')->where('Codigo', $value['Codigo'])->where('sigla', 'DdD-EN')->first();
                $declaracao_divida_Anual = DB::table('tb_tipo_servicos')->where('Codigo', $value['Codigo'])->where('sigla', 'DdD-EU')->first();


                if (($anoDesignacao->Codigo == $anoCorrente) && ($declaracao_divida == null && $declaracao_divida_Anual == null)) { // alterar para ano corrente

                    $divida_antiga = $this->dividaService->DividasTodosAnos($matricula->codigo_matricula, 2);

                    $pagou_negociacao = $this->divida->pagouNegociacao($matricula->codigo_matricula);

                    if ($divida_antiga > 0 && ($pagou_negociacao == 0)) {
                        $result['message'] = 'Caro estudante, tem dívida de ano(s) anterior(es)! Por favor efectue o pagamento da sua dívida ou faça uma negociação. Caso a sua dívida nao esteja atualizada não se preocupe. Por favor contacte o suporte mutue.';
                        return response()->json($result, 201);
                       // return response()->json('Caro estudante, tem dívida de ano(s) anterior(es)! Por favor efectue o pagamento da sua dívida ou faça uma negociação. Caso a sua dívida nao esteja atualizada não se preocupe. Por favor contacte o suporte mutue.', 201);
                    }
                }


                if ($value['TipoServico'] == 'Mensal') {

                    $confirmacao_ano_corrente = DB::table('tb_confirmacoes')->where('Codigo_Matricula', $alunoRepository->matricula)->where('Codigo_Ano_lectivo', $this->anoAtualPrincipal->index())->count();
                    $incricao_cadeira_ano_corrente = DB::table('tb_grade_curricular_aluno')->where('codigo_matricula', $alunoRepository->matricula)->where('codigo_ano_lectivo', $this->anoAtualPrincipal->index())->whereIn('Codigo_Status_Grade_Curricular', [2, 3])->get();

                    if ($ano == $this->anoAtualPrincipal->index()) {
                        if (blank($incricao_cadeira_ano_corrente) && $aluno->admissao->preinscricao->codigo_tipo_candidatura == 1) {
                            $result['message'] = 'Prezado(a) estudante, para fazer o pagamento de propina deve primeiro inscrever-se nas unidades curriculares para este ano lectivo';
                            return response()->json($result, 201);
                        }
                    }

                    // DESCONTO ATE 31 DE OUTUBRO 2021. VERIFICA  A DATA DO BANCO
                    $dados_pagamentos = $request->pagamento;
                    $dados_pagamentos = json_decode($dados_pagamentos, true);

                    $dados_pagamentos['DataBanco'] = date('Y-m-d');

                    if (isset($dados_pagamentos['DataBanco'])) {


                        $desconto_especial_outubro = DB::table('descontos_especiais')->where('id', 3)->where('estado', 1)->first();
                        $taxa_nov21_jul22PorDataBanco = $this->descontoService->descontoNov21Jul22PorDataBanco($dados_pagamentos['DataBanco']);

                        if (/*$dados_pagamentos['DataBanco']>=$desconto_especial_outubro->data_inicio && */$dados_pagamentos['DataBanco'] <= $desconto_especial_outubro->data_fim && $ano < $anoCorrente) {

                            $desconto_d = $propina_d->Preco - $propina_d->valor_anterior;
                            $total_d = $propina_d->Preco - $propina_d->valor_anterior;
                            $value['Desconto'] = $desconto_d;

                            $bolsa = $this->bolsaService->Bolsa($matricula->codigo_matricula, $anoCorrente);


                            if ($bolsa) {

                                $value['Desconto'] = ($propina_d->valor_anterior * ($bolsa->desconto / 100)) + $desconto_d;
                            } elseif (($this->extenso->finalista($preinscricao->user_id) > 0 && $this->extenso->finalista($preinscricao->user_id) <= 3) && ($ano == $anoCorrente)) {

                                $value['Desconto'] = ($propina_d->valor_anterior * 0.5) + $desconto_d;
                            }
                        } else {
                            if (($ano == $anoCorrente && $preinscricao->AlunoCacuaco == 'NAO')) {
                                if ($taxa_nov21_jul22PorDataBanco && $ano < $anoCorrente) {

                                    $value['Desconto'] = $propina_d->Preco * ($taxa_nov21_jul22PorDataBanco->taxa / 100);

                                    if ($bolsa) {
                                        $value['Desconto'] = ($propina_d->Preco * ($bolsa->desconto / 100));
                                    } elseif (($this->extenso->finalista($preinscricao->user_id) > 0 && $this->extenso->finalista($preinscricao->user_id) <= 3) && ($ano == $anoCorrente)) {
                                        $value['Desconto'] = ($propina_d->Preco * 0.5);
                                    }
                                }
                            }
                        }
                    }
                    //
                } else {
                    $dados_pagamentos = $request->pagamento;
                    $dados_pagamentos = json_decode($dados_pagamentos, true);
                }

                if (($anoDesignacao->Codigo == $anoCorrente) || ($anoDesignacao->Codigo != $anoCorrente)) {
                    //Aplicao de restricao sobre os documentos
                    $parametroDocumentos = DB::table('tb_parametros_pagamentos_portal')
                        ->where('servico', $value['Descricao'])
                        ->where('estado', 'Ativo')
                        ->first(); //Parametros dos Documentos

                    //Consulta para Restricao Pagamento Documentos
                    $retricaoPagamento = DB::table('factura')
                        ->select(DB::RAW(
                            'mes_temp.prestacao'
                        ))
                        ->leftJOIN('factura_items', 'factura_items.CodigoFactura', 'factura.Codigo')
                        ->leftJOIN('mes_temp', 'mes_temp.id', 'factura_items.mes_temp_id')
                        ->WHERE('factura.CodigoMatricula', $matricula->codigo_matricula)
                        ->where('factura.ano_lectivo', $ano)
                        ->where('factura_items.mes_temp_id', '!=', 'Null')
                        ->where('factura_items.estado', '=', '1')
                        ->orderByDesc('factura_items.codigo')
                        ->first();

                    if ($retricaoPagamento) {
                        $prestacaopaga = $retricaoPagamento->prestacao;
                    } else {
                        $prestacaopaga = 0;
                    }

                    $dadosPrestacao = DB::table('mes_temp')
                        ->select()
                        ->where('mes_temp.ano_lectivo', $ano)
                        ->where('mes_temp.prestacao', $prestacaopaga)
                        ->first();

                    //restricao de documentos

                    if ($parametroDocumentos != null) {
                        $parametroServico = $parametroDocumentos->servico;
                    } else {
                        $parametroServico = "Nenhum";
                    }

                    //dd($retricaoPagamento->prestacao);

                    if ($value['Descricao'] == $parametroServico) {
                        $prestacao_anterior = $parametroDocumentos->prestacao_em_cobranca - 1;

                        if ($bolsa != null) {
                            $descontobolsa = $bolsa->desconto;
                        } else {
                            $descontobolsa = 0;
                        }

                        if ($descontobolsa != 100) {
                            if ($preinscricao->codigo_tipo_candidatura == 1) {
                                if (($prestacaopaga < $parametroDocumentos->prestacao_em_cobranca && $matricula->estado_matricula != "diplomado") || ($this->dividaService->DividasTodosAnos($matricula->codigo_matricula, 2) > 0 && $this->divida->pagouNegociacao($matricula->codigo_matricula) == 0)) {
                                    if (($prestacao_anterior == $prestacaopaga && date("Y-m-d") > date($dadosPrestacao->data_limite)) || ($this->dividaService->DividasTodosAnos($matricula->codigo_matricula, 2) > 0 && $this->divida->pagouNegociacao($matricula->codigo_matricula) == 0)) {
                                        if ($value['Descricao'] == $parametroDocumentos->servico) {
                                            return response()->json('Não é possível solicitar ' . $parametroDocumentos->servico . ', por favor regularize a sua situação financeira', 201);
                                        }
                                    }
                                    if ($prestacao_anterior != $prestacaopaga || ($this->dividaService->DividasTodosAnos($matricula->codigo_matricula, 2) > 0 && $this->divida->pagouNegociacao($matricula->codigo_matricula) == 0)) {
                                        if ($value['Descricao'] == $parametroDocumentos->servico) {
                                            return response()->json('Não é possível solicitar ' . $parametroDocumentos->servico . ', por favor regularize a sua situação financeira', 201);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    //nova restricao liquidacao
                    if ($value['TipoServico'] == 'Mensal') {


                        $liquidacao_fatura = DB::table('factura')
                            ->select('factura_items.estado as estado_item', 'factura_items.CodigoFactura', 'factura.estado as estado_fatura', 'factura.corrente')
                            ->join('factura_items', 'factura_items.CodigoFactura', 'factura.Codigo')
                            ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', 'factura_items.CodigoProduto')
                            ->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', 'factura.ano_lectivo')
                            ->where('factura.CodigoMatricula', $matricula->codigo_matricula)
                            ->where('factura.corrente', 1)->where('factura.estado', '!=', 3)
                            ->whereIn('factura.codigo_descricao', [2])
                            ->where('tb_tipo_servicos.TipoServico', 'Mensal')
                            ->where(function ($query) {
                                $query->where('factura_items.estado', '!=', 1)
                                    ->orWhereIn('factura.estado', [0, 2]);
                            })->where(function ($query) use ($ano1) {
                                $query->whereIn('tb_ano_lectivo.ordem', [$ano1->ordem, $ano1->ordem - 1])
                                    ->orWhereIn('tb_ano_lectivo.Designacao', [$this->anoAtualPrincipal->cicloMestrado()->Designacao, $this->anoAtualPrincipal->cicloDoutoramento()->Designacao]);
                            })->orderBy('factura.Codigo', 'desc')->first();
                    } else {
                        // para as facturas que nao são de propinas só se verifica o estado da facturas e nao dos itens das facturas

                        $liquidacao_fatura = DB::table('factura')
                            ->select('factura_items.estado as estado_item', 'factura_items.CodigoFactura', 'factura.estado as estado_fatura', 'factura.corrente')
                            ->join('factura_items', 'factura_items.CodigoFactura', 'factura.Codigo')
                            ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', 'factura_items.CodigoProduto')
                            ->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', 'factura.ano_lectivo')
                            ->where('factura.CodigoMatricula', $matricula->codigo_matricula)
                            ->where('factura.corrente', 1)->where('factura.estado', '!=', 3)
                            //->whereIn('factura.codigo_descricao', [2])
                            ->where('tb_tipo_servicos.TipoServico', '!=', 'Mensal')
                            ->where('factura.estado', '!=', 1)
                            //->where('factura_items.estado',0)
                            ->whereIn('tb_ano_lectivo.ordem', [$ano1->ordem, $ano1->ordem - 1])
                            //->whereRaw('factura_items.mes_temp_id IS NOT NULL')
                            ->orderBy('factura.Codigo', 'desc')->first();
                    }


                    if ($liquidacao_fatura) {
                        $result['message'] = '2. Caro estudante, tem uma factura que não foi liquidada totalmente com o número ' . $liquidacao_fatura->CodigoFactura;
                        return response()->json($result, 201);
                    }
                }

                $diplomado = DB::table('tb_matriculas')->where('estado_matricula', 'diplomado')->where('Codigo', $matricula->codigo_matricula)->select('*')->first();

                if ($diplomado && $value['TipoServico'] == 'Mensal') {
                    $result['message'] = 'Caro estudante, não lhe é permitido efectuar o pagamento de propina visto estar diplomado!';
                    return response()->json($result, 201);
                }

                if (($anoDesignacao->Codigo >= $anoCorrente) || ($anoDesignacao->Codigo == 1)) {
                    try {
                        $mes_existe = DB::table('factura_items')
                        ->join('factura', 'factura.Codigo', 'factura_items.CodigoFactura')
                        ->join('mes_temp', 'mes_temp.id', 'factura_items.mes_temp_id')
                        ->where('factura.ano_lectivo', $ano)
                        ->where('factura.CodigoMatricula', $codigo_matricula)
                        ->where('factura_items.mes_temp_id', $value['mes_temp_id'])
                        ->select('*', 'mes_temp.designacao as mes')
                        ->where('factura.corrente', 1)
                        ->where('factura.estado', '!=', 3)
                        ->first();
                        //code...
                    } catch (\Throwable $th) {
                        $mes_existe = null;
                    }
                    if ($mes_existe) {
                        $result['message'] = 'Já existe uma fatura do mês ' . $mes_existe->mes . '. Clique em registar pagamento, e efectue o pagamento dessa fatura.';
                        return response()->json($result, 201);
                    
                    }
                } else {

                    try {
                        $mes_existeAntigo = DB::table('factura_items')->join('factura', 'factura.Codigo', 'factura_items.CodigoFactura')->where('factura.ano_lectivo', $ano)->where('factura.CodigoMatricula', $matricula->codigo_matricula)->where('factura_items.Mes', $value['Mes'])->where('factura_items.Mes', '!=', '#')->select('*', 'factura_items.Mes as mes')->where('factura.estado', '!=', 3)->first();
                        //code...
                    } catch (\Throwable $th) {
                        $mes_existeAntigo = null;
                    }

                    if ($mes_existeAntigo) {
                        $result['message'] = 'Já existe uma fatura do mês ' . $mes_existeAntigo->mes . '. Clique em registar pagamento, e efectue o pagamento dessa fatura';
                        return response()->json($result, 201);
                    }
                }

                $saldo = DB::table('tb_preinscricao')->select('saldo', 'saldo_anterior', 'saldo_reset')->where('Codigo', $preinscricao->Codigo)->first();

                $this->saldo_actual_estudante = $saldo->saldo;

                $amount += $value['Preco'];
                $multa += $value['Multa'];

                $desconto_total += $value['Desconto'];
            }

            $valor_apagar = $amount - $desconto_total + $multa;

            $alunoLogado = $this->alunoRepository->dadosAlunoLogado($aluno->admissao->preinscricao->user_id);


            $taxa_nov21_jul22 = $this->descontoService->descontoNov21Jul22();


            if ($taxa_nov21_jul22) {
                if (($alunoLogado->anoLectivo >= $anoCorrente) && ($alunoLogado->estado_matricula != 'inactivo') && ($alunoLogado->Codigo_Turno == 6)) {
                    $fatura['obs'] = "Desconto de incentivo";
                }
            }

            $fatura['DataFactura'] = Carbon::now();
            $fatura['TotalPreco'] = $amount;
            $fatura['CodigoMatricula'] = $matricula->codigo_matricula;
            $fatura['polo_id'] = $preinscricao->polo_id;
            $fatura['Referencia'] = $referencia;
            $fatura['ValorAPagar'] = $valor_apagar;
            $fatura['Desconto'] = $desconto_total;
            $fatura['ValorEntregue'] = $dados_pagamentos['valor_depositado'];
            $fatura['TotalMulta'] = $multa;
            $fatura['ano_lectivo'] = $ano;
            $fatura['obs'] = "Pagamento feito a cash";
            $fatura['codigo_descricao'] = 2;
            $fatura['estado'] = 1;
            $result['codigo_fatura'] = DB::table('factura')
                ->insertGetId($fatura);
       
            $codigo_fatura = $result['codigo_fatura'];
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
        try {
            $this->faturaService->salvarFacturaMovimentoConta($codigo_fatura);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }

        try {
            $mes = null;
            $desconto_mes = 0;

            foreach ($data as $key => $value1) {

                if ($value['TipoServico'] == 'Mensal') {
                    $dados_pagamentos = $request->pagamento;
                    $dados_pagamentos = json_decode($dados_pagamentos, true);

                    $dados_pagamentos['DataBanco'] = date("Y-m-d");

                    if (isset($dados_pagamentos['DataBanco'])) {
                        $curso_d1 = DB::table('tb_cursos')->join('tb_preinscricao', 'tb_cursos.Codigo', '=', 'tb_preinscricao.Curso_Candidatura')->select('tb_cursos.Designacao as curso')->where('tb_preinscricao.Codigo', $preinscricao->Codigo)->first();
                        $curso_d1 = DB::table('tb_cursos')->join('tb_preinscricao', 'tb_cursos.Codigo', '=', 'tb_preinscricao.Curso_Candidatura')->select('tb_cursos.Designacao as curso')->where('tb_preinscricao.Codigo', $preinscricao->Codigo)->first();
                        $propina_d1 = DB::table('tb_tipo_servicos')
                            ->select('Descricao', 'Preco', 'TipoServico', 'Codigo', 'valor_anterior')
                            ->where('Descricao', 'like', 'propina ' . $curso_d1->curso . '%')
                            ->where('cacuaco', $matricula->aluno_Cacuaco)
                            ->where('codigo_ano_lectivo', $anoCorrente)
                            ->first();

                        // DESCONTO ATE 31 DE OUTUBRO 2021. VERIFICA  A DATA DO BANCO
                        $desconto_especial_outubro = DB::table('descontos_especiais')->where('id', 3)->where('estado', 1)->first();
                        $taxa_nov21_jul22PorDataBanco = $this->descontoService->descontoNov21Jul22PorDataBanco($dados_pagamentos['DataBanco']);

                        if ($desconto_especial_outubro && $dados_pagamentos['DataBanco'] <= $desconto_especial_outubro->data_fim && $ano < $anoCorrente) {

                            $desconto_d1 = $propina_d1->Preco - $propina_d1->valor_anterior;
                            //$total_d=$propina_d1->Preco-$propina_d1->valor_anterior;
                            $value1['Desconto'] = $desconto_d1;
                            $value1['Total'] = $value1['Preco'] - $value1['Desconto'] + $value1['Multa'];
                            $bolsa = $this->bolsaService->Bolsa($matricula->codigo_matricula, $anoCorrente);

                            if ($bolsa) {

                                $value1['Desconto'] = ($propina_d1->valor_anterior * ($bolsa->desconto / 100)) + $desconto_d1;
                                $value1['Total'] = $value1['Preco'] - $value1['Desconto'] + $value1['Multa'];
                            } elseif (($this->extenso->finalista($preinscricao->user_id) > 0 && $this->extenso->finalista($preinscricao->user_id) <= 3) && ($ano == $anoCorrente)) {

                                $value1['Desconto'] = ($propina_d1->valor_anterior * 0.5) + $desconto_d1;

                                $value1['Total'] = $value1['Preco'] - $value1['Desconto'] + $value1['Multa'];
                            }
                        } else {
                            if (($preinscricao->AlunoCacuaco == 'NAO')) {
                                if ($taxa_nov21_jul22PorDataBanco && $ano < $anoCorrente) {

                                    $value1['Desconto'] = $propina_d1->Preco * ($taxa_nov21_jul22PorDataBanco->taxa / 100);

                                    if ($bolsa) {
                                        $value1['Desconto'] = ($propina_d1->Preco * ($bolsa->desconto / 100));
                                        $value1['Total'] = $value1['Preco'] - $value1['Desconto'] + $value1['Multa'];
                                    } elseif (($this->extenso->finalista($preinscricao->user_id) > 0 && $this->extenso->finalista($preinscricao->user_id) <= 3) && ($ano == $anoCorrente)) {
                                        $value1['Desconto'] = ($propina_d1->Preco * 0.5);
                                        $value1['Total'] = $value1['Preco'] - $value1['Desconto'] + $value1['Multa'];
                                    }
                                }
                            }
                        }
                    }
                }

                $mes = $value1['Mes'];
                $desconto_mes = $value1['Desconto'];

                //Reescrevi a condição para que fosse possivel mandar o Mes=Null para o ano actual, estava restrito só para o ano 2020-2021
                if (($ano == $this->anoAtualPrincipal->index()) || ($ano == 1)) {
                    $mes = null;
                }
                DB::table('factura_items')->insert(
                    [
                        'CodigoProduto' => $value1['Codigo'],
                        'CodigoFactura' => $codigo_fatura,
                        'Quantidade' => 1,
                        'Total' => $value1['Total'],
                        'valor_pago' => $value1['Total'],
                        'Mes' => $mes,
                        'estado' => 1,
                        'mes_temp_id' => $value1['mes_temp_id'],
                        'Multa' => $value1['Multa'],
                        'preco' => $value1['Preco'],
                        'descontoProduto' => $desconto_mes
                    ]
                );
            }
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }

        $servico_mensal = DB::table('factura_items')
            ->join('factura', 'factura_items.CodigoFactura', '=', 'factura.Codigo')
            ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', '=', 'factura_items.CodigoProduto')
            ->select('*')
            ->where('factura.Codigo', $codigo_fatura)
            ->where('tb_tipo_servicos.TipoServico', 'Mensal')
            ->first();

        //if($pagarComSaldo==1){

        $fact_aluno = DB::table('factura')
            ->join('tb_matriculas', 'tb_matriculas.Codigo', 'factura.CodigoMatricula')
            ->join('tb_admissao', 'tb_admissao.Codigo', '=', 'tb_matriculas.Codigo_Aluno')
            ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
            ->where('factura.Codigo', $codigo_fatura)
            ->where('tb_preinscricao.Codigo', $preinscricao->Codigo)
            ->select('factura.Codigo', 'factura.ValorAPagar', 'factura.ano_lectivo', 'factura.codigo_descricao')
            ->first();

        $factura_items = DB::table('factura_items')
            ->join('factura', 'factura_items.CodigoFactura', '=', 'factura.Codigo')
            ->select('factura_items.*')
            ->where('factura.Codigo', $fact_aluno->Codigo)
            ->get();

        $array_fatura = json_decode($factura_items, true);


        if ($saldo->saldo >= $valor_apagar || number_format($saldo->saldo, 2, '.', '') >= number_format($valor_apagar, 2, '.', '')) { //SE TEM SALDO SUFICIENTE

            $pagmnt_total_com_saldo = 1;
            //Pagamento com saldo na fatura
            $result['message'] = 'Pagamento efectuado com a sua reserva disponível! Por favor, verifique a fatura gerada pelo sistema.';
            try {
                try {

                    $ano = DB::table('tb_ano_lectivo')->where('Codigo', $anoCorrente)->first();

                    $pagamento['Data'] = date('Y-m-d');
                    $pagamento['Observacao'] = 'Pagamento efectuado por Cash';
                    $pagamento['AnoLectivo'] = $fact_aluno->ano_lectivo;
                    $pagamento['Codigo_PreInscricao'] = $preinscricao->Codigo;
                    $pagamento['caixa_id'] = $caixas->codigo;
                    $pagamento['status_pagamento'] = 'pendente';
                    $pagamento['valor_depositado'] = $valor_apagar;
                    $pagamento['Totalgeral'] = $valor_apagar;
                    $pagamento['fk_utilizador'] =  auth()->user()->codigo_importado;
                    $pagamento['Utilizador'] =  auth()->user()->codigo_importado;
                    $pagamento['DataRegisto'] = date('Y-m-d H:i:s');
                    $pagamento['codigo_factura'] = $fact_aluno->Codigo;
                    $pagamento['forma_pagamento'] = '6';
                    $pagamento['estado'] = ($fact_aluno->codigo_descricao == 2 || $fact_aluno->codigo_descricao == 4 || $fact_aluno->codigo_descricao == 5 || $fact_aluno->codigo_descricao == 10) ? 1 : 0;
                    $pagamento['corrente'] = 1;

                    $id_pag = DB::table('tb_pagamentos')->insertGetId($pagamento);
                    
                    dd($id_pag);
                } catch (\Illuminate\Database\QueryException $e) {
                    DB::rollback();
                    return Response()->json($e->getMessage());
                }
                try {
                    $this->pagamentoService->salvarPagamMovimentoConta($id_pag, $codigo_matricula);
                } catch (\Exception $e) {
                    DB::rollback();
                    throw $e;
                }
                try {
                    DB::table('factura')->where('factura.Codigo', $codigo_fatura)->update(['obs' => 'Pagamento feito com Reserva no mutue Cash', 'ValorEntregue' => $valor_apagar]);
                } catch (\Exception $e) {
                    DB::rollback();
                    throw $e;
                }

                try {

                    $valorItem = DB::table('factura_items')
                        ->where('factura_items.CodigoFactura', $codigo_fatura)
                        ->select('*')->get();

                    $array11 = json_decode($valorItem, true);

                    foreach ($array11 as $key => $value) {
                        DB::table('factura_items')
                            ->where('factura_items.CodigoFactura', $codigo_fatura)
                            ->update(['valor_pago' => $value['Total'], 'estado' => 1]);
                    }
                } catch (\Exception $e) {
                    DB::rollback();
                    throw $e;
                }
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
            $anoLectivo = DB::table('tb_ano_lectivo')->where('Codigo', $fact_aluno->ano_lectivo)->first()->Designacao;

            $desconto_mes = 0;

            try {


                foreach ($array_fatura as $key => $fac) {

                    $id_mes = null;
                    $mes = null;
                    $desconto_mes = $fac['descontoProduto'];
                    if ($servico_mensal && (int)$anoLectivo <= 2019) {
                        $mes = $fac['Mes'];

                        if ($fac['Mes'] == 'MAR') {
                            $id_mes = 1;
                        } elseif ($fac['Mes'] == 'ABR') {
                            $id_mes = 2;
                        } elseif ($fac['Mes'] == 'MAI') {
                            $id_mes = 3;
                        } elseif ($fac['Mes'] == 'JUN') {
                            $id_mes = 4;
                        } elseif ($fac['Mes'] == 'JUL') {
                            $id_mes = 5;
                        } elseif ($fac['Mes'] == 'AGO') {
                            $id_mes = 6;
                        } elseif ($fac['Mes'] == 'SET') {
                            $id_mes = 7;
                        } elseif ($fac['Mes'] == 'OUT') {
                            $id_mes = 8;
                        } elseif ($fac['Mes'] == 'NOV') {
                            $id_mes = 9;
                        } elseif ($fac['Mes'] == 'DEZ') {
                            $id_mes = 10;
                        }
                    }

                    if ($servico_mensal && $fac['mes_temp_id'] == 5 && $fact_aluno->ano_lectivo == $anoCorrente) {

                        DB::table('tb_matriculas')->where('tb_matriculas.Codigo', $matricula->codigo_matricula)->update(['estado_matricula' => 'activo']);
                    }
                    if ($saldo->saldo > 0) {
                        DB::table('tb_pagamentosi')->insert(
                            [
                                'Codigo_Pagamento' => $id_pag,
                                'Codigo_Servico' => $fac['CodigoProduto'],
                                'Valor_Pago' => $fac['Total'],
                                'Quantidade' => 1,
                                'Valor_Total' => $fac['Total'],
                                'Multa' => $fac['Multa'],
                                'Deconnto' => $desconto_mes,
                                'Ano' => $anoLectivo,
                                'mes_id' => $id_mes,
                                'Mes' => $mes,
                                'mes_temp_id' => $fac['mes_temp_id']
                            ]
                        );
                    }
                }
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

            foreach ($array_fatura as $key => $item) {
                $id_documento_validacao = '';
                $servico_doc = DB::table('tb_tipo_servicos')->where('codigo_ano_lectivo', $anoCorrente)
                    ->where('Codigo', $item['CodigoProduto'])->select('*')->first();

                if ($servico_doc && ($servico_doc->sigla == 'CdF' || $servico_doc->sigla == 'CdHaC')) {

                    try {
                        if ($servico_doc->sigla == 'CdF') {
                            $tipo_documento = DB::table('tb_tipo_documentos')->where('Codigo', 6)->first();
                        } elseif ($servico_doc->sigla == 'CdHaC') {
                            $tipo_documento = DB::table('tb_tipo_documentos')->where('Codigo', 7)->first();
                        }
                        $hashcode = strtoupper(bin2hex(random_bytes(4)));
                        $documento['documento'] = $tipo_documento->Designacao;
                        $documento['ano_letivo'] = $anoCorrente;
                        $documento['utilizador'] = auth()->user()->codigo_importado;
                        $documento['DataRegisto'] = date('Y-m-d');
                        $documento['status'] = 'Ativo';

                        $documento['codigo_documento'] = $hashcode;

                        $documento['codigo_matricula'] = $matricula->codigo_matricula;
                        $documento['tipo_documento'] = $tipo_documento->Codigo;

                        $id_documento_validacao = DB::table('tb_documentos_uc')->insertGetId($documento);
                    } catch (\Exception $e) {
                        DB::rollback();
                        throw $e;
                    }

                    try {
                        DB::table('tb_pagamentos')->where('Codigo', $id_pag)
                            ->update(['info_adicional' => $id_documento_validacao]);
                    } catch (\Exception $e) {
                        DB::rollback();
                        throw $e;
                    }
                }
            }

            try {
                $saldo = DB::table('tb_preinscricao')->select('saldo', 'saldo_anterior')->where('Codigo', $preinscricao->Codigo)->first();

                $saldo_atual = $saldo->saldo - $valor_apagar;

                $novoSaldo = DB::table('tb_preinscricao')->where('tb_preinscricao.Codigo', $preinscricao->Codigo)->update(['saldo' => $saldo_atual > 0 ? $saldo_atual : 0, 'saldo_anterior' => $saldo->saldo]);
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        } else { // SE NAO TEM SALDO SUFICIENTE

            try {
                $result['message'] = 'Pagamento efectuado com sucesso! Por favor, verifique a factura gerada pelo sistema.';

                if (($saldo->saldo > 0) && ($data1['valor_depositado'] < $valor_apagar)) {

                    $pagamento['Data'] = date('Y-m-d');
                    $pagamento['Observacao'] = 'Pagamento_Saldo';
                    $pagamento['AnoLectivo'] = $anoCorrente;
                    $pagamento['Codigo_PreInscricao'] = $preinscricao->Codigo;
                    $pagamento['valor_depositado'] = $preinscricao->saldo;
                    $pagamento['caixa_id'] = $caixas->codigo;
                    $pagamento['status_pagamento'] = 'pendente';
                    $pagamento['Totalgeral'] =  $valor_apagar;
                    $pagamento['DataRegisto'] = date('Y-m-d H:i:s');
                    $pagamento['codigo_factura'] = $codigo_fatura;
                    $pagamento['fk_utilizador'] =  auth()->user()->codigo_importado;
                    $pagamento['Utilizador'] =  auth()->user()->codigo_importado;
                    $pagamento['Observacao'] =  'Pagamento efectuado com Reserva no Mutue Cash';
                    $pagamento['feito_com_reserva'] =  'Y';
                    $pagamento['forma_pagamento'] =  '6';
                    $pagamento['estado'] = ($fact_aluno->codigo_descricao == 2 || $fact_aluno->codigo_descricao == 4 || $fact_aluno->codigo_descricao == 5 || $fact_aluno->codigo_descricao == 10) ? 1 : 0;
                    $data['corrente'] = 1;

                    $id_pag = DB::table('tb_pagamentos')->insertGetId($pagamento);


                    DB::table('tb_preinscricao')
                        ->where('tb_preinscricao.Codigo', $preinscricao->Codigo)
                        ->update(['saldo' => 0, 'saldo_anterior' => $saldo->saldo]);
                }
            } catch (\Illuminate\Database\QueryException $e) {

                DB::rollback();
                return Response()->json($e->getMessage());
            }
        }
        

        if (!$pagmnt_total_com_saldo) {
        
            $this->codigo_factura_em_curso = $codigo_fatura;
            
            try {
                $data1['forma_pagamento'] = 6;
                // colocou-se aqui esta funcao por causa da transaccao
                $this->salvarPagamentosDiversos($request, $codigo_matricula);
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        }
            
        // aqui vai a funcao de salvar pagamento

        // If we reach here, then
        // data is valid and working.
        // Commit the queries!
        DB::commit();

        try {
            if($id_pag && $id_pag > 0){
                $this->pagamentoService->validarPagamentoAdmin($id_pag, Auth::user()->pk_utilizador);
            }
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollback();
            $result['message'] = $e->getMessage();
            return Response()->json($result['message']);
        }

        return Response()->json($result);
        //}
    }

    public function pdfFatReciboIExameAcesso($id)
    {
        $id = base64_decode(base64_decode(base64_decode($id)));
        $data['instituicao'] = DB::table('tb_dados_instituicao')->first();
        $data['aluno'] = DB::table('tb_preinscricao')->join('tb_cursos', 'tb_cursos.codigo', '=', 'tb_preinscricao.Curso_Candidatura')
            ->join('tb_periodos', 'tb_periodos.codigo', '=', 'tb_preinscricao.Codigo_Turno')
            ->join('polos', 'polos.id', '=', 'tb_preinscricao.polo_id')
            ->join('factura', 'factura.codigo_preinscricao', '=', 'tb_preinscricao.Codigo')
            ->join('tb_pagamentos', 'tb_pagamentos.codigo_factura', '=', 'factura.Codigo')->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', '=', 'factura.ano_lectivo')->select(
                '*',
                'factura.Codigo as numero_fatura',
                'tb_preinscricao.Codigo as codigo_preinscricao',
                'tb_cursos.Designacao as curso',
                'polos.designacao as polo',
                'tb_periodos.Designacao as turno',
                'tb_ano_lectivo.Designacao as anoLectivo',
                'polos.designacao as polo',
                'factura.TotalPreco as TotalPreco',
                'factura.Desconto as Desconto',
                'factura.ValorAPagar as ValorAPagar',
                'tb_pagamentos.valor_depositado as valor_depositado',
                'factura.Troco as Troco',
                'tb_pagamentos.Observacao as obs',
                'tb_pagamentos.fk_utilizador as codigo_importado',
                'tb_ano_lectivo.Designacao as anoLectivo'
            )->where('factura.Codigo', $id)->first();

        $codigo_aluno = $data['aluno']->codigo_preinscricao;
        $polo_aluno = $data['aluno']->polo;
        $curso_aluno = $data['aluno']->curso;
        $turno_aluno = $data['aluno']->turno;
        $nome_aluno = $data['aluno']->Nome_Completo;
        $data['faturas'] = DB::table('factura_items')
            ->join('factura', 'factura_items.CodigoFactura', '=', 'factura.Codigo')->join(
                'tb_tipo_servicos',
                'tb_tipo_servicos.Codigo',
                'factura_items.CodigoProduto'
            )
            ->join('tb_preinscricao', 'tb_preinscricao.Codigo', 'factura.codigo_preinscricao')->select(
                'factura_items.Total as total',
                'factura.TotalPreco as TotalFatura',
                'tb_tipo_servicos.preco as preco',
                'tb_tipo_servicos.Descricao as servico',
                'factura_items.descontoProduto as desconto',
                'factura_items.Multa as multa',
                'factura_items.Total as total'

            )->where('factura.Codigo', $id)->get();

        $data['Total_fatura'] = DB::table('factura')->join('tb_preinscricao', 'tb_preinscricao.Codigo', 'factura.codigo_preinscricao')
            ->select('*')->where('factura.Codigo', $id)->first();
        $data['conta1'] = DB::table('tb_local_pagamento')->where('codigo', 1)->first();
        $data['conta2'] = DB::table('tb_local_pagamento')->where('codigo', 3)->first();
        $data['conta3'] = DB::table('tb_local_pagamento')->where('codigo', 9)->first();
        //$servico= $data['faturas']->servico;
        $data['servico'] = DB::table('tb_tipo_servicos')->where('Codigo', 37)->first();
        $data['total_geral'] = $data['Total_fatura']->TotalPreco;

        $data['extenso'] = $this->extenso->index($data['total_geral']);;
        \QrCode::size(250)
            ->format('png')
            ->generate("Nº matrícula:$codigo_aluno \n Nome: $nome_aluno \n Curso:$curso_aluno \n Polo: $polo_aluno \n
        Periodo:$turno_aluno \n Servico: Matrsícula", public_path('img/qrcode.png'));


        //Recuperar prova do candidato
        $candidatoProva = CandidatoProva::where('candidato_id', $codigo_aluno)->where('status', 0)->first();

        if ($candidatoProva) {
            $candidatoProva->load('horario.curso', 'horario.sala', 'horario.periodo', 'prova');
        }

        $data['candidato_prova'] = $candidatoProva;

        $data['pagamento_utilizador'] = DB::table('mca_tb_utilizador')
            ->where('codigo_importado', $data['aluno']->codigo_importado)->select('mca_tb_utilizador.nome as nome')->first();

        $pdf = PDF::loadView('pdf.fatura_inscricao_exame_acesso', $data)->setPaper('a5');

        return $pdf->stream('fatura.pdf');
    }

    public function imprimirFaturaDiversos(Request $request, $id)
    {
        // ESTA FUNÇÃO É UTILIZADA PARA IMPRIMIR FACTURAS. FUNÇÃO EM USO
        $id = base64_decode(base64_decode(base64_decode($id)));

        $fatura = DB::table('factura')->where('Codigo', $id)->first();
        try {
            $aluno = Matricula::with(['admissao.preinscricao'])->findOrFail($fatura->CodigoMatricula);
            $id_aluno = $aluno->admissao->preinscricao->user_id;
        } catch (\Throwable $th) {
            $aluno = DB::table('tb_preinscricao')->where('Codigo', $fatura->codigo_preinscricao)->first();
            $id_aluno = $aluno->user_id;
        }
        
        $data['aluno'] = DB::table('tb_preinscricao')
            ->leftJoin('tb_admissao', 'tb_admissao.pre_incricao', 'tb_preinscricao.Codigo')
            ->leftJoin('tb_matriculas', 'tb_matriculas.Codigo_Aluno', 'tb_admissao.codigo')
            ->join('tb_cursos', 'tb_cursos.codigo', '=', 'tb_matriculas.Codigo_Curso')
            // ->join('tb_turmas', 'tb_turmas.Codigo_Curso', '=', 'tb_cursos.codigo')
            ->join('tb_periodos', 'tb_periodos.codigo', '=', 'tb_preinscricao.Codigo_Turno')
            ->join('polos', 'polos.id', '=', 'tb_preinscricao.polo_id')
            ->join('factura', 'factura.CodigoMatricula', '=', 'tb_matriculas.Codigo')
            ->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', '=', 'factura.ano_lectivo')
            ->leftjoin('negociacao_dividas', 'negociacao_dividas.codigo_fatura', 'factura.Codigo')
            ->leftjoin('meses_calendario as tb_mes_inicial', 'tb_mes_inicial.id', 'negociacao_dividas.id_mes_inicial')
            ->leftjoin('meses_calendario as tb_mes_final', 'tb_mes_final.id', 'negociacao_dividas.id_mes_final')
            ->select(
                '*',
                'factura.Codigo as numero_fatura',
                'factura.Troco as troco',
                'tb_matriculas.Codigo as codigo_matricula',
                'tb_cursos.sigla as curso',
                'polos.designacao as polo',
                'tb_periodos.Designacao as turno',
                'tb_ano_lectivo.Designacao as anoLectivo',
                'polos.designacao as polo',
                'factura.TotalPreco as TotalFatura',
                'factura.ValorAPagar as valor_apagar',
                'factura.Desconto as desconto',
                'tb_preinscricao.Nome_Completo',
                'tb_preinscricao.saldo',
                'tb_preinscricao.saldo_anterior',
                'factura.TotalMulta as multa',
                'tb_ano_lectivo.Designacao as anoDesignacao',
                'tb_ano_lectivo.Codigo as anoCodigo',
                'factura.ValorEntregue as valor_depositado',
                'factura.obs',
                'factura.estado',
                'factura.ValorAPagarExtenso',
                'tb_mes_final.designacao as mes_final',
                'tb_mes_inicial.designacao as mes_inicial',
                'negociacao_dividas.id as negociacao',
                'negociacao_dividas.qtd_prestacoes',
                'negociacao_dividas.mesesQuitar',
                'negociacao_dividas.valorRestante',
                'negociacao_dividas.valorPrestacoes',
                'negociacao_dividas.primeiroValorApagar',
                'tb_matriculas.Codigo as codigo_matricula',
                'tb_preinscricao.codigo_tipo_candidatura as codigo_tipo_candidatura'
            )
            ->where('tb_preinscricao.user_id', $id_aluno)
            ->where('factura.Codigo', $id)
            ->first();

        if (blank($data['aluno'])) {

            $data['aluno'] = DB::table('tb_preinscricao')
                ->leftJoin('tb_admissao', 'tb_admissao.pre_incricao', 'tb_preinscricao.Codigo')
                ->leftJoin('tb_matriculas', 'tb_matriculas.Codigo_Aluno', 'tb_admissao.codigo')
                ->join('tb_cursos', 'tb_cursos.codigo', '=', 'tb_preinscricao.Curso_Candidatura')
                ->join('tb_periodos', 'tb_periodos.codigo', '=', 'tb_preinscricao.Codigo_Turno')
                ->join('polos', 'polos.id', '=', 'tb_preinscricao.polo_id')
                ->join('factura', 'factura.codigo_preinscricao', '=', 'tb_preinscricao.Codigo')
                ->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', '=', 'factura.ano_lectivo')
                ->leftjoin('negociacao_dividas', 'negociacao_dividas.codigo_fatura', 'factura.Codigo')
                ->leftjoin('meses_calendario as tb_mes_inicial', 'tb_mes_inicial.id', 'negociacao_dividas.id_mes_inicial')
                ->leftjoin('meses_calendario as tb_mes_final', 'tb_mes_final.id', 'negociacao_dividas.id_mes_final')
                ->select(
                    '*',
                    'factura.Codigo as numero_fatura',
                    'factura.Troco as troco',
                    'tb_matriculas.Codigo as codigo_matricula',
                    'tb_cursos.sigla as curso',
                    'polos.designacao as polo',
                    'tb_periodos.Designacao as turno',
                    'tb_ano_lectivo.Designacao as anoLectivo',
                    'polos.designacao as polo',
                    'factura.TotalPreco as TotalFatura',
                    'factura.ValorAPagar as valor_apagar',
                    'factura.Desconto as desconto',
                    'tb_preinscricao.Nome_Completo',
                    'tb_preinscricao.saldo',
                    'tb_preinscricao.saldo_anterior',
                    'factura.TotalMulta as multa',
                    'tb_ano_lectivo.Designacao as anoDesignacao',
                    'tb_ano_lectivo.Codigo as anoCodigo',
                    'factura.ValorEntregue as valor_depositado',
                    'factura.obs',
                    'factura.estado',
                    'factura.ValorAPagarExtenso',
                    'tb_mes_final.designacao as mes_final',
                    'tb_mes_inicial.designacao as mes_inicial',
                    'negociacao_dividas.id as negociacao',
                    'negociacao_dividas.qtd_prestacoes',
                    'negociacao_dividas.mesesQuitar',
                    'negociacao_dividas.valorRestante',
                    'negociacao_dividas.valorPrestacoes',
                    'negociacao_dividas.primeiroValorApagar',
                    'tb_matriculas.Codigo as codigo_matricula',
                    'tb_preinscricao.codigo_tipo_candidatura as codigo_tipo_candidatura'
                )
                ->where('tb_preinscricao.user_id', $id_aluno)
                ->where('factura.Codigo', $id)
                ->first();
        }

        $polo_aluno = $data['aluno']->polo;
        $curso_aluno = $data['aluno']->curso;
        $turno_aluno = $data['aluno']->turno;
        $nome_aluno = $data['aluno']->Nome_Completo;
        $codigo_aluno = $data['aluno']->codigo_matricula;

        if ($data['aluno'] && (int)$data['aluno']->anoLectivo <= 2019) {

            $data['faturas'] = DB::table('factura_items')
                ->join('factura', 'factura_items.CodigoFactura', '=', 'factura.Codigo')
                ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', 'factura_items.CodigoProduto')
                ->leftJoin('tb_matriculas', 'tb_matriculas.Codigo', 'factura.CodigoMatricula')
                ->leftJoin('tb_admissao', 'tb_admissao.codigo', 'tb_matriculas.Codigo_Aluno')
                ->leftjoin('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', '=', 'factura_items.codigo_anoLectivo')
                ->join('tb_preinscricao', 'tb_preinscricao.Codigo', 'tb_admissao.pre_incricao')
                ->leftJoin('mes_temp', 'mes_temp.id', '=', 'factura_items.mes_temp_id')
                ->leftJoin('inscricao_avaliacoes', 'inscricao_avaliacoes.codigo_factura', '=', 'factura.Codigo')
                ->leftJoin('tb_grade_curricular', 'tb_grade_curricular.Codigo', '=', 'inscricao_avaliacoes.codigo_grade')
                ->leftJoin('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')
                ->leftjoin('tb_grade_curricular as tbgc', 'tbgc.Codigo', 'tb_tipo_servicos.codigo_grade_currilular')
                ->leftjoin('tb_disciplinas as tbd', 'tbd.Codigo', 'tbgc.Codigo_Disciplina')
                ->select(
                    DB::raw('ANY_VALUE(factura_items.Codigo) as codigoItem'),
                    DB::raw('(factura_items.Total) as total'),
                    DB::raw('(factura_items.preco) as preco'),
                    DB::raw('(tbd.Designacao) as cadeira'),
                    DB::raw('(tb_tipo_servicos.Descricao) as servico'),
                    DB::raw('(tb_disciplinas.Designacao) as disciplina'),
                    DB::raw('(tb_ano_lectivo.Designacao) as anoLectivo'),
                    DB::raw('(factura_items.Mes) as mes'),
                    DB::raw('(mes_temp.prestacao) as prestacao'),
                    DB::raw('(factura_items.Quantidade) as qtd'),
                    DB::raw('(factura_items.descontoProduto) as desconto'),
                    DB::raw('(factura_items.estado) as estado'),
                    DB::raw('(factura_items.Multa) as multa')
                )
                ->where('tb_preinscricao.user_id', $id_aluno)
                ->where('factura.Codigo', $id)
                ->distinct('codigoItem')
                ->get();
        } else {

            $data['faturas'] = DB::table('factura_items')
                ->join('factura', 'factura_items.CodigoFactura', '=', 'factura.Codigo')
                ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', 'factura_items.CodigoProduto')
                ->join('tb_matriculas', 'tb_matriculas.Codigo', 'factura.CodigoMatricula')
                ->join('tb_admissao', 'tb_admissao.codigo', 'tb_matriculas.Codigo_Aluno')
                ->leftjoin('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', '=', 'factura_items.codigo_anoLectivo')
                ->join('tb_preinscricao', 'tb_preinscricao.Codigo', 'tb_admissao.pre_incricao')
                ->leftJoin('mes_temp', 'mes_temp.id', '=', 'factura_items.mes_temp_id')
                ->leftJoin('inscricao_avaliacoes', 'inscricao_avaliacoes.codigo_factura', '=', 'factura.Codigo')
                ->leftJoin('tb_grade_curricular', 'tb_grade_curricular.Codigo', '=', 'inscricao_avaliacoes.codigo_grade')
                ->leftJoin('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')
                ->leftjoin('tb_grade_curricular as tbgc', 'tbgc.Codigo', 'tb_tipo_servicos.codigo_grade_currilular')
                ->leftjoin('tb_disciplinas as tbd', 'tbd.Codigo', 'tbgc.Codigo_Disciplina')
                ->select(
                    DB::raw('ANY_VALUE(factura_items.Codigo) as codigoItem'),
                    DB::raw('ANY_VALUE(factura_items.Total) as total'),
                    DB::raw('ANY_VALUE(factura_items.preco) as preco'),
                    DB::raw('ANY_VALUE(tbd.Designacao) as cadeira'),
                    DB::raw('ANY_VALUE(tb_tipo_servicos.Descricao) as servico'),
                    DB::raw('ANY_VALUE(tbd.Designacao) as disciplina'),
                    DB::raw('ANY_VALUE(tb_ano_lectivo.Designacao) as anoLectivo'),
                    DB::raw('ANY_VALUE(inscricao_avaliacoes.codigo_tipo_avaliacao) as avaliacao'),
                    DB::raw('ANY_VALUE(mes_temp.designacao) as mes'),
                    DB::raw('ANY_VALUE(mes_temp.prestacao) as prestacao'),
                    DB::raw('ANY_VALUE(factura_items.Quantidade) as qtd'),
                    DB::raw('ANY_VALUE(factura_items.descontoProduto) as desconto'),
                    DB::raw('ANY_VALUE(factura_items.Multa) as multa'),
                    DB::raw('ANY_VALUE(factura_items.estado) as estado')
                )->where('tb_preinscricao.user_id', $id_aluno)
                ->where('factura.Codigo', $id)
                ->distinct('codigoItem')
                ->get();

            if(blank($data['faturas'])){
                $data['faturas'] = DB::table('factura_items')
                ->join('factura', 'factura_items.CodigoFactura', '=', 'factura.Codigo')
                ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', 'factura_items.CodigoProduto')
                ->leftjoin('tb_matriculas', 'tb_matriculas.Codigo', 'factura.CodigoMatricula')
                ->leftjoin('tb_admissao', 'tb_admissao.codigo', 'tb_matriculas.Codigo_Aluno')
                ->leftjoin('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', '=', 'factura_items.codigo_anoLectivo')
                ->join('tb_preinscricao', 'tb_preinscricao.Codigo', 'factura.codigo_preinscricao')
                ->leftJoin('mes_temp', 'mes_temp.id', '=', 'factura_items.mes_temp_id')
                ->leftJoin('inscricao_avaliacoes', 'inscricao_avaliacoes.codigo_factura', '=', 'factura.Codigo')
                ->leftJoin('tb_grade_curricular', 'tb_grade_curricular.Codigo', '=', 'inscricao_avaliacoes.codigo_grade')
                ->leftJoin('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')
                ->leftjoin('tb_grade_curricular as tbgc', 'tbgc.Codigo', 'tb_tipo_servicos.codigo_grade_currilular')
                ->leftjoin('tb_disciplinas as tbd', 'tbd.Codigo', 'tbgc.Codigo_Disciplina')
                ->select(
                    DB::raw('ANY_VALUE(factura_items.Codigo) as codigoItem'),
                    DB::raw('ANY_VALUE(factura_items.Total) as total'),
                    DB::raw('ANY_VALUE(factura_items.preco) as preco'),
                    DB::raw('ANY_VALUE(tbd.Designacao) as cadeira'),
                    DB::raw('ANY_VALUE(tb_tipo_servicos.Descricao) as servico'),
                    DB::raw('ANY_VALUE(tbd.Designacao) as disciplina'),
                    DB::raw('ANY_VALUE(tb_ano_lectivo.Designacao) as anoLectivo'),
                    DB::raw('ANY_VALUE(inscricao_avaliacoes.codigo_tipo_avaliacao) as avaliacao'),
                    DB::raw('ANY_VALUE(mes_temp.designacao) as mes'),
                    DB::raw('ANY_VALUE(mes_temp.prestacao) as prestacao'),
                    DB::raw('ANY_VALUE(factura_items.Quantidade) as qtd'),
                    DB::raw('ANY_VALUE(factura_items.descontoProduto) as desconto'),
                    DB::raw('ANY_VALUE(factura_items.Multa) as multa'),
                    DB::raw('ANY_VALUE(factura_items.estado) as estado')
                )->where('tb_preinscricao.user_id', $id_aluno)
                ->where('factura.Codigo', $id)
                ->distinct('codigoItem')
                ->get();
            }

        }

        $data['conta1'] = DB::table('tb_local_pagamento')->where('codigo', 1)->first();
        $data['conta2'] = DB::table('tb_local_pagamento')->where('codigo', 3)->first();
        $data['conta3'] = DB::table('tb_local_pagamento')->where('codigo', 9)->first();

        $data['total_apagar'] = $data['aluno']->valor_apagar;
        $data['qtdPrestacoes'] = count($this->parametro_uma->totalPrestacoesPagarPorAno($fatura->ano_lectivo, $data['aluno']->codigo_tipo_candidatura));
        if($request->has('extensivo')){
            DB::table('factura')->where('Codigo', $id)->update(['ValorAPagarExtenso' => $request->get('extensivo')]);  
        }else{
            // dd(22);
            DB::table('factura')->where('Codigo', $id)->update(['ValorAPagarExtenso' => $this->valor_por_extenso($data['total_apagar'], false)]);
        }

        $data['extenso'] = $data['aluno']->ValorAPagarExtenso ? $data['aluno']->ValorAPagarExtenso : $this->valor_por_extenso($data['total_apagar'], false);

        \QrCode::size(250)
            ->format('png')
            ->generate("Nº matrícula: $codigo_aluno \n Nome: $nome_aluno \n Curso: $curso_aluno \n Polo: $polo_aluno
                Periodo: $turno_aluno \n Servico: 'Diversos'", public_path('images/qrcode.png'));

        //Recuperar os pagamentos por referências by Ndongala Nguinamau
        $data['aluno']->numero_fatura;
        $data['pagamento'] = PagamentoPorReferencia::where('factura_codigo', $data['aluno']->numero_fatura)->first();

        $pagamento = DB::table('tb_pagamentos')->where('codigo_factura', $id)
            ->select('tb_pagamentos.fk_utilizador as codigo_importado')
            ->first();

        $data['pagamento_utilizador'] = DB::table('mca_tb_utilizador')
            ->where('codigo_importado', $pagamento->codigo_importado)->select('mca_tb_utilizador.nome as nome')->first();

        $pdf = PDF::loadView('pdf.fatura_diversos', $data)
            ->setPaper('a5');

        return $pdf->stream('fatura.pdf');
    }


    public function FaturaTicket(Request $request, $id)
    {
        // ESTA FUNÇÃO É UTILIZADA PARA IMPRIMIR FACTURAS. FUNÇÃO EM USO
        $id = base64_decode(base64_decode(base64_decode($id)));

        $fatura = DB::table('factura')->where('Codigo', $id)->first();

        $aluno = Matricula::with(['admissao.preinscricao'])->findOrFail($fatura->CodigoMatricula);

        if ($fatura->codigo_descricao == 5) {
            $id = base64_encode(base64_encode(base64_encode($id)));
            return redirect('estudante/fatura/negociacao/show/' . $id);
        }

        $id_aluno = $aluno->admissao->preinscricao->user_id;
        $data['aluno'] = DB::table('tb_preinscricao')
            ->join('tb_admissao', 'tb_admissao.pre_incricao', 'tb_preinscricao.Codigo')
            ->join('tb_matriculas', 'tb_matriculas.Codigo_Aluno', 'tb_admissao.codigo')
            ->join('tb_cursos', 'tb_cursos.codigo', '=', 'tb_matriculas.Codigo_Curso')
            ->join('tb_turmas', 'tb_turmas.Codigo_Curso', '=', 'tb_cursos.codigo')
            ->join('tb_periodos', 'tb_periodos.codigo', '=', 'tb_preinscricao.Codigo_Turno')
            ->join('polos', 'polos.id', '=', 'tb_preinscricao.polo_id')
            ->join('factura', 'factura.CodigoMatricula', '=', 'tb_matriculas.Codigo')
            ->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', '=', 'factura.ano_lectivo')
            ->leftjoin('negociacao_dividas', 'negociacao_dividas.codigo_fatura', 'factura.Codigo')
            ->leftjoin('meses_calendario as tb_mes_inicial', 'tb_mes_inicial.id', 'negociacao_dividas.id_mes_inicial')
            ->leftjoin('meses_calendario as tb_mes_final', 'tb_mes_final.id', 'negociacao_dividas.id_mes_final')
            ->select(
                '*',
                'factura.Codigo as numero_fatura',
                'factura.Troco as troco',
                'tb_matriculas.Codigo as codigo_matricula',
                'tb_cursos.sigla as curso',
                'polos.designacao as polo',
                'tb_periodos.Designacao as turno',
                'tb_ano_lectivo.Designacao as anoLectivo',
                'polos.designacao as polo',
                'factura.TotalPreco as TotalFatura',
                'factura.ValorAPagar as valor_apagar',
                'factura.Desconto as desconto',
                'tb_preinscricao.Nome_Completo',
                'tb_preinscricao.Contactos_Telefonicos',
                'tb_preinscricao.saldo',
                'factura.TotalMulta as multa',
                'tb_ano_lectivo.Designacao as anoDesignacao',
                'tb_ano_lectivo.Codigo as anoCodigo',
                'factura.ValorEntregue as valor_depositado',
                'tb_turmas.Designacao as turma',
                'tb_turmas.Codigo_Classe as classe',
                'factura.obs',
                'factura.estado',
                'factura.ValorAPagarExtenso',
                'tb_mes_final.designacao as mes_final',
                'tb_mes_inicial.designacao as mes_inicial',
                'negociacao_dividas.id as negociacao',
                'negociacao_dividas.qtd_prestacoes',
                'negociacao_dividas.mesesQuitar',
                'negociacao_dividas.valorRestante',
                'negociacao_dividas.valorPrestacoes',
                'negociacao_dividas.primeiroValorApagar',
                'tb_matriculas.Codigo as codigo_matricula',
                'tb_preinscricao.codigo_tipo_candidatura as codigo_tipo_candidatura'
            )
            ->where('tb_preinscricao.user_id', $id_aluno)
            ->where('factura.Codigo', $id)
            ->first();


        $polo_aluno = $data['aluno']->polo;
        $curso_aluno = $data['aluno']->curso;
        $turno_aluno = $data['aluno']->turno;
        $nome_aluno = $data['aluno']->Nome_Completo;
        $codigo_aluno = $data['aluno']->codigo_matricula;

        if ($data['aluno'] && (int)$data['aluno']->anoLectivo <= 2019) {

            $data['faturas'] = DB::table('factura_items')
                ->join('factura', 'factura_items.CodigoFactura', '=', 'factura.Codigo')
                ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', 'factura_items.CodigoProduto')
                ->join('tb_matriculas', 'tb_matriculas.Codigo', 'factura.CodigoMatricula')
                ->join('tb_admissao', 'tb_admissao.codigo', 'tb_matriculas.Codigo_Aluno')
                ->leftjoin('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', '=', 'factura_items.codigo_anoLectivo')
                ->join('tb_preinscricao', 'tb_preinscricao.Codigo', 'tb_admissao.pre_incricao')
                ->leftJoin('mes_temp', 'mes_temp.id', '=', 'factura_items.mes_temp_id')
                ->leftJoin('inscricao_avaliacoes', 'inscricao_avaliacoes.codigo_factura', '=', 'factura.Codigo')
                ->leftJoin('tb_grade_curricular', 'tb_grade_curricular.Codigo', '=', 'inscricao_avaliacoes.codigo_grade')
                ->leftJoin('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')
                ->leftjoin('tb_grade_curricular as tbgc', 'tbgc.Codigo', 'tb_tipo_servicos.codigo_grade_currilular')
                ->leftjoin('tb_disciplinas as tbd', 'tbd.Codigo', 'tbgc.Codigo_Disciplina')
                ->select(
                    DB::raw('ANY_VALUE(factura_items.Codigo) as codigoItem'),
                    DB::raw('(factura_items.Total) as total'),
                    DB::raw('(factura_items.preco) as preco'),
                    DB::raw('(tbd.Designacao) as cadeira'),
                    DB::raw('(tb_tipo_servicos.Descricao) as servico'),
                    DB::raw('(tb_disciplinas.Designacao) as disciplina'),
                    DB::raw('(tb_ano_lectivo.Designacao) as anoLectivo'),
                    DB::raw('(factura_items.Mes) as mes'),
                    DB::raw('(mes_temp.prestacao) as prestacao'),
                    DB::raw('(factura_items.Quantidade) as qtd'),
                    DB::raw('(factura_items.descontoProduto) as desconto'),
                    DB::raw('(factura_items.estado) as estado'),
                    DB::raw('(factura_items.Multa) as multa')
                )
                ->where('tb_preinscricao.user_id', $id_aluno)
                ->where('factura.Codigo', $id)
                ->distinct('codigoItem')
                ->get();
        } else {

            $data['faturas'] = DB::table('factura_items')
                ->join('factura', 'factura_items.CodigoFactura', '=', 'factura.Codigo')
                ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', 'factura_items.CodigoProduto')
                ->join('tb_matriculas', 'tb_matriculas.Codigo', 'factura.CodigoMatricula')
                ->join('tb_admissao', 'tb_admissao.codigo', 'tb_matriculas.Codigo_Aluno')
                ->leftjoin('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', '=', 'factura_items.codigo_anoLectivo')
                ->join('tb_preinscricao', 'tb_preinscricao.Codigo', 'tb_admissao.pre_incricao')
                ->leftJoin('mes_temp', 'mes_temp.id', '=', 'factura_items.mes_temp_id')
                ->leftJoin('inscricao_avaliacoes', 'inscricao_avaliacoes.codigo_factura', '=', 'factura.Codigo')
                ->leftJoin('tb_grade_curricular', 'tb_grade_curricular.Codigo', '=', 'inscricao_avaliacoes.codigo_grade')
                ->leftJoin('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')
                ->leftjoin('tb_grade_curricular as tbgc', 'tbgc.Codigo', 'tb_tipo_servicos.codigo_grade_currilular')
                ->leftjoin('tb_disciplinas as tbd', 'tbd.Codigo', 'tbgc.Codigo_Disciplina')
                ->select(
                    DB::raw('ANY_VALUE(factura_items.Codigo) as codigoItem'),
                    DB::raw('ANY_VALUE(factura_items.Total) as total'),
                    DB::raw('ANY_VALUE(factura_items.preco) as preco'),
                    DB::raw('ANY_VALUE(tbd.Designacao) as cadeira'),
                    DB::raw('ANY_VALUE(tb_tipo_servicos.Descricao) as servico'),
                    DB::raw('ANY_VALUE(tbd.Designacao) as disciplina'),
                    DB::raw('ANY_VALUE(tb_ano_lectivo.Designacao) as anoLectivo'),
                    DB::raw('ANY_VALUE(inscricao_avaliacoes.codigo_tipo_avaliacao) as avaliacao'),
                    DB::raw('ANY_VALUE(mes_temp.designacao) as mes'),
                    DB::raw('ANY_VALUE(mes_temp.prestacao) as prestacao'),
                    DB::raw('ANY_VALUE(factura_items.Quantidade) as qtd'),
                    DB::raw('ANY_VALUE(factura_items.descontoProduto) as desconto'),
                    DB::raw('ANY_VALUE(factura_items.Multa) as multa'),
                    DB::raw('ANY_VALUE(factura_items.estado) as estado')
                )->where('tb_preinscricao.user_id', $id_aluno)
                ->where('factura.Codigo', $id)
                ->distinct('codigoItem')
                ->get();
        }

        $data['conta1'] = DB::table('tb_local_pagamento')->where('codigo', 1)->first();
        $data['conta2'] = DB::table('tb_local_pagamento')->where('codigo', 3)->first();
        $data['conta3'] = DB::table('tb_local_pagamento')->where('codigo', 9)->first();


        $data['total_apagar'] = $data['aluno']->valor_apagar;
        $data['qtdPrestacoes'] = count($this->parametro_uma->totalPrestacoesPagarPorAno($fatura->ano_lectivo, $data['aluno']->codigo_tipo_candidatura));
        
        if($request->has('extensivo')){
            DB::table('factura')->where('Codigo', $id)->update(['ValorAPagarExtenso' => $request->get('extensivo')]);  
        }else{
            // dd(22);
            DB::table('factura')->where('Codigo', $id)->update(['ValorAPagarExtenso' => $this->valor_por_extenso($data['total_apagar'], false)]);
        }
        
        $data['extenso'] = $request->has('extensivo') ? $request->get('extensivo') : $this->valor_por_extenso($data['total_apagar'], false);


        \QrCode::size(250)
            ->format('png')
            ->generate("Nº matrícula: $codigo_aluno \n Nome: $nome_aluno \n Curso: $curso_aluno \n Polo: $polo_aluno
                Periodo: $turno_aluno \n Servico: 'Diversos'", public_path('images/qrcode.png'));

        //Recuperar os pagamentos por referências by Ndongala Nguinamau
        $data['aluno']->numero_fatura;
        $data['pagamento'] = PagamentoPorReferencia::where('factura_codigo', $data['aluno']->numero_fatura)->first();

        $pdf = PDF::loadView('Relatorios.ticket-pagamento', $data);

        return $pdf->stream('ticket-pagamento.pdf');
    }

}
