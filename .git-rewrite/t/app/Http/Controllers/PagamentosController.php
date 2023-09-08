<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\FormaPagamento;
use App\Models\GerarRefereciaDePagamento;
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

class PagamentosController extends Controller
{
    use TraitHelpers;

    public $extenso;
    public $descontoService;
    public $bolsaService;
    public $dividaService;
    public $codigo_factura_em_curso = null;
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

        $data['items'] = Pagamento::leftjoin('tb_preinscricao', 'tb_pagamentos.Codigo_PreInscricao', '=', 'tb_preinscricao.Codigo')
        ->leftjoin('tb_admissao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
        ->leftjoin('tb_matriculas', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
        ->leftjoin('tb_cursos', 'tb_matriculas.Codigo_Curso', '=', 'tb_cursos.Codigo')
        ->leftjoin('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', '=', 'tb_pagamentos.Codigo')
        ->leftjoin('tb_tipo_servicos', 'tb_pagamentosi.Codigo_Servico', '=', 'tb_tipo_servicos.Codigo')
        ->orderBy('tb_pagamentos.Codigo', 'desc')
        ->select('tb_pagamentos.Codigo', 'Nome_Completo', 'Totalgeral', 'DataRegisto', 'tb_matriculas.Codigo AS matricula', 'tb_cursos.Designacao AS curso', 'tb_tipo_servicos.Descricao AS servico')
        ->paginate(5)
        ->withQueryString();
        
        return Inertia::render('Operacoes/Pagamentos/Index', $data);
    }

    public function create(Request $request)
    {
        $user = auth()->user();

        $data['forma_pagamentos'] = FormaPagamento::where('status', 1)->get();

        return Inertia::render('Operacoes/Pagamentos/Create', $data);
    }


    public function getTodasReferencias(Request $request, $codigo_matricula)
    {

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
                'factura.ano_lectivo as ano_lectivo'
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
        $aluno = Matricula::with(['admissao.preinscricao'])->findOrFail($codigo_matricula);

        $id = $aluno->admissao->preinscricao->Codigo;
        $codigo = $aluno->admissao->preinscricao->Codigo;
        $data = json_decode($request->pagamento, true);
        $fonte = json_decode($request->fonte, true);
        $codigoDaFatura = json_decode($request->codigo_fatura, true);

        //Controlar valor pago por referencia Activa
        try {
            // if ($data['forma_pagamento'] == "POR REFERÊNCIA") {
            //     $factura = Factura::find($codigoDaFatura);
            //     $total_pagas = PagamentoPorReferencia::where('factura_codigo', $codigoDaFatura)->where('Status', 'PAID')->sum('AMOUNT');
            //     $total_entregue = PagamentoPorReferencia::where('factura_codigo', $codigoDaFatura)->where('Status', 'ACTIVE')->sum('AMOUNT');
            //     //recuperar valor_pago com saldo
            //     $total_com_saldo = $factura->pagamentos->where('Observacao', 'Pagamento_Saldo')->sum('valor_depositado');
            //     $total_entregue += $total_com_saldo + $total_pagas + $data['valor_depositado'];
            //     if ($total_entregue > $factura->ValorAPagar) {

            //         return response()->json("Valor superior ao esperado! Existem pagamentos activos, aguarde pela validação, total entregue até o memento: " . number_format($total_entregue, 2, ",", ".") . 'Kz', 201);
            //     }
            // }
        } catch (\Exception $th) {
        }

        if ($fonte == 2) {
            $codigoDaFatura = $this->codigo_factura_em_curso; // codigo da factura gerada aqui no backend 
            if (!$codigoDaFatura) {
                return Response()->json("Ocorreu um erro(cf)", 201);
            }
        }

        $saldo_novo = DB::table('tb_preinscricao')
            ->where('tb_preinscricao.Codigo', $codigo)
            ->select('saldo')->first();

        $tamanho = 0;

        if (sizeOf($data) > 0) {
            $tamanho = sizeOf($data);
        }

        if ($saldo_novo->saldo > 1 && $fonte == 1) {  //Saldo maior que 1, Ndongala

            try {
                $this->salvarPagamentoComSaldo($request, $codigoDaFatura, $id);
                $response['mensagem'] = "Pagamento enviado com seu saldo disponível! Por favor verifique a factura gerada pelo sistema";
            } catch (\Illuminate\Database\QueryException $e) {
                DB::rollback();
                return Response()->json($e->getMessage());
            }
        }
        
        $mensagens = [
            'N_Operacao_Bancaria.alpha_num' => 'O número de operação bancária digitado é inválido. Por favor digite números e/ou letras sem espaços em branco.',
            'N_Operacao_Bancaria.unique' => 'O número de operação bancária digitado já existe no sistema.'
        ];

        $validate = Validator::make($data, [
            'forma_pagamento' => ['required'],
            'valor_depositado' => ['numeric']
        ], $mensagens);

        if ($data['forma_pagamento'] != "POR REFERÊNCIA") {
        }
        if ($data['forma_pagamento'] != 'POR REFERÊNCIA') {
            $validate = Validator::make($data, [
                // 'N_Operacao_Bancaria' => ['required', 'unique:tb_pagamentos', 'alpha_num'],
                'Observacao' => ['max:255'],
                'forma_pagamento' => ['required'],
                'ContaMovimentada' => ['required'],
                'valor_depositado' => ['numeric']
            ], $mensagens);
        }

        if ($validate->fails()) {
            return response()->json(['errors' => $validate->errors()], 422);
        }

        
        
        //$stra = trim(preg_replace('/\s+/','', $str));
        $data['N_Operacao_Bancaria'] = rand(0, $codigoDaFatura) . time();

        $total_sem_multa = 0;
        $total_fatura_sem_multa = 0;
        $total_multa_fatura = 0;
        $multaItem = 0;
        $valorComdesconto = 0;
        $anoCorrente = $this->anoLectivoActivo();

        $fatura_paga = DB::table('factura')->join('tb_matriculas', 'tb_matriculas.Codigo', 'factura.CodigoMatricula')
            ->join('tb_admissao', 'tb_admissao.Codigo', '=', 'tb_matriculas.Codigo_Aluno')
            ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
            ->join('tb_pagamentos', 'tb_pagamentos.codigo_factura', '=', 'factura.Codigo')
            ->where('factura.Codigo', $codigoDaFatura)
            ->where('tb_preinscricao.Codigo', $codigo)
            ->select('tb_pagamentos.valor_depositado', 'factura.ValorAPagar as ValorAPagar', 'factura.codigo_descricao', 'factura.Codigo', 'factura.ValorEntregue as ValorEntregue', 'factura.estado as estado_factura', 'factura.ano_lectivo as ano_factura')
            ->first();

        $Somapagamentos = DB::table('factura')->join('tb_matriculas', 'tb_matriculas.Codigo', 'factura.CodigoMatricula')
            ->join('tb_admissao', 'tb_admissao.Codigo', '=', 'tb_matriculas.Codigo_Aluno')
            ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
            ->join('tb_pagamentos', 'tb_pagamentos.codigo_factura', '=', 'factura.Codigo')
            ->where('factura.Codigo', $codigoDaFatura)->where('tb_preinscricao.Codigo', $codigo)
            ->select('tb_pagamentos.valor_depositado as valor_depositado', 'factura.ValorAPagar as ValorAPagar', 'factura.codigo_descricao', 'factura.Codigo', 'factura.ValorEntregue as ValorEntregue', 'factura.estado as estado_factura', 'factura.ano_lectivo as ano_factura')
            ->get();

        $total = $Somapagamentos->sum('valor_depositado');
        if ($fatura_paga && $Somapagamentos && $total >= $fatura_paga->ValorAPagar) {
            return response()->json("Ja efectuou o pagamento da fatura referida!", 201);
        } elseif ($fatura_paga && $fatura_paga->ValorEntregue >= $fatura_paga->ValorAPagar) {

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

            $mesOutubro = DB::table('factura_items')
                ->join('factura', 'factura_items.CodigoFactura', '=', 'factura.Codigo')
                ->join('tb_matriculas', 'tb_matriculas.Codigo', 'factura.CodigoMatricula')
                ->join('tb_admissao', 'tb_admissao.Codigo', '=', 'tb_matriculas.Codigo_Aluno')
                ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
                ->select('factura_items.mes_temp_id as id_mes', 'factura_items.Total as valor_apagar')
                ->where('factura.Codigo', $codigoDaFatura)->where('factura_items.mes_temp_id', 5)
                ->where('tb_preinscricao.Codigo', $codigo)->first();
            //dd(number_format(($servico_mensal->ValorAPagar / 2), 2, '.', ''));
            $saldo = DB::table('tb_preinscricao')->where('tb_preinscricao.Codigo', $codigo)->select('saldo')->first();


            $candidatura1 = DB::table('tb_preinscricao')->where('tb_preinscricao.Codigo', $codigo)->select()->first();


            if (isset($data['N_Operacao_Bancaria2']) && ($data['N_Operacao_Bancaria'] == $data['N_Operacao_Bancaria2'])) {
                return response()->json("Digitou dois números de operações bancárias iguais!", 201);
            }

            if ($data['valor_depositado'] <= 0) {
                return response()->json("O valor introduzido não é permitido para realizar a operação!", 201);
            }
            //dd($valorFatura->ValorAPagar,$data['valor_depositado'],$saldo->saldo);
            if ($valorFatura->ValorEntregue <= 0 && number_format($data['valor_depositado'], 2, '.', '') < $valorFatura->ValorAPagar && ($valorFatura->tipo_factura == 3 || $valorFatura->tipo_factura == 6 || $valorFatura->tipo_factura == 7 || $valorFatura->tipo_factura == 8)) {
                return response()->json("O valor introduzido não é permitido para realizar a operação! Seleccionou uma factura de serviço diferente de propina", 201);
            }

            if (($valorFatura->ValorEntregue > 0 && number_format(($data['valor_depositado'] + $valorFatura->ValorEntregue), 2, '.', '') < $valorFatura->ValorAPagar) && ($valorFatura->tipo_factura == 3 || $valorFatura->tipo_factura == 6 || $valorFatura->tipo_factura == 7 || $valorFatura->tipo_factura == 8)) {
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
                                $data['valor_depositado'] = ($multaFatura->ValorAPagar - $multaAtualItem->Multa);
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
                    // if (isset($request->talao_banco)) {
                    //     $fileName = rand(0, $codigo) . time() . '.' . $request->talao_banco->getClientOriginalExtension();
                    //     $request->talao_banco->storeAs('documentos', $fileName);
                    //     $data['nome_documento'] = $fileName;
                    // }
                    // if (isset($request->talao_banco2)) {
                    //     $fileName2 = rand(0, $codigo) . time() . '.' . $request->talao_banco2->getClientOriginalExtension();
                    //     $request->talao_banco2->storeAs('documentos', $fileName2);
                    //     $data['nome_documento2'] = $fileName2;
                    // }

                    $data['Data'] = date('Y-m-d');
                    $data['AnoLectivo'] = $fact_aluno->ano_lectivo;

                    $data['Totalgeral'] = $fact_aluno->ValorAPagar;
                    $data['Codigo_PreInscricao'] = $codigo;
                    $data['DataRegisto'] = date('Y-m-d H:i:s');
                    $data['codigo_factura'] = $fact_aluno->Codigo;
                    $data['estado'] = 1;
                    $data['corrente'] = 1;
                    $data['Observacao'] = "Pagamento efectuado por Cash";
                    $data['fk_utilizador'] = auth()->user()->codigo_importado;
                    $data['Utilizador'] = auth()->user()->codigo_importado;

                    try {
                        $id_pag = DB::table('tb_pagamentos')->insertGetId($data);
                    } catch (\Illuminate\Database\QueryException $e) {
                        DB::rollback();
                        return Response()->json($e->getMessage());
                    }
                    
                    $ultimo_pag = DB::table('tb_pagamentos')->where('Codigo', $id_pag)->first();

                    //Gerar Referencia do BE By Ndongala Nguinamau
                    // if ($data['forma_pagamento'] == 'POR REFERÊNCIA') {

                    //     $pagamento_referencia = GerarRefereciaDePagamento::run($valorFatura->codigo_fatura, $data['valor_depositado']);
                    //     //Recupera o pagamento recem inserido na tb_pagamentos e actualiza o numero de operacao pelo codigo da referencia(SOURCE_ID)
                    //     $pagamento = Pagamento::find($id_pag);
                    //     $pagamento->update(['N_Operacao_Bancaria' => $pagamento_referencia->SOURCE_ID]);
                    // }
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
                $response['codigo_pagamento'] = $id_pag;
                DB::commit();
            }
        }

        

        return Response()->json($response);
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


    public function faturaDiversos(Request $request, $codigo_matricula)
    {

        $aluno = Matricula::with(['admissao.preinscricao'])->findOrFail($codigo_matricula);

        $alunoRepository = $this->alunoRepository->dadosAlunoLogado($aluno->admissao->preinscricao->user_id);

        $data1 = $request->pagamento;

        $data1 = json_decode($data1, true);

        $data = $request->fatura_item;
        $data = json_decode($data, true);
        $anoCorrente = $this->anoAtualPrincipal->index();


        $ano = $request->anoLectivo;
        $ano = json_decode($ano, true);
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
                return response()->json('Caro estudante, tem uma factura de negociação de dívida que não foi liquidada totalmente. Número da factura: ' . $temDivida, 201);
            }

            if ($this->pagamentoRejeitado($anoDesignacao->Codigo, $anoDesignacao->Designacao, $preinscricao->Codigo)) {
                return response()->json('Caro estudante, não lhe é permitido efectuar a operação. Tem um pagamento rejeitado!', 201);
            }
            
            foreach ($data as $key => $value) {
                $declaracao_divida = DB::table('tb_tipo_servicos')->where('Codigo', $value['Codigo'])->where('sigla', 'DdD-EN')->first();
                $declaracao_divida_Anual = DB::table('tb_tipo_servicos')->where('Codigo', $value['Codigo'])->where('sigla', 'DdD-EU')->first();


                if (($anoDesignacao->Codigo == $anoCorrente) && ($declaracao_divida == null && $declaracao_divida_Anual == null)) { // alterar para ano corrente
                    
                    $divida_antiga = $this->dividaService->DividasTodosAnos($matricula->codigo_matricula, 2);
            
                    $pagou_negociacao = $this->divida->pagouNegociacao($matricula->codigo_matricula);
                    
                    if ($divida_antiga > 0 && ($pagou_negociacao == 0)) {
                        return response()->json('Caro estudante, tem dívida de ano(s) anterior(es)! Por favor efectue o pagamento da sua dívida ou faça uma negociação. Caso a sua dívida nao esteja atualizada não se preocupe. Por favor contacte o suporte mutue.', 201);
                    }
                }
             

                if ($value['TipoServico'] == 'Mensal') {

                    $confirmacao_ano_corrente = DB::table('tb_confirmacoes')->where('Codigo_Matricula', $alunoRepository->matricula)->where('Codigo_Ano_lectivo', $this->anoAtualPrincipal->index())->count();
                    $incricao_cadeira_ano_corrente = DB::table('tb_grade_curricular_aluno')->where('codigo_matricula', $alunoRepository->matricula)->where('codigo_ano_lectivo', $this->anoAtualPrincipal->index())->whereIn('Codigo_Status_Grade_Curricular', [2, 3])->get();

                    if ($ano == $this->anoAtualPrincipal->index()) {
                        if (blank($incricao_cadeira_ano_corrente) && auth()->user()->preinscricao->codigo_tipo_candidatura == 1) {
                            return response()->json('Prezado(a) estudante, para fazer o pagamento de propina deve primeiro inscrever-se nas unidades curriculares para este ano lectivo', 201);
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
                        return response()->json('2. Caro estudante, tem uma factura que não foi liquidada totalmente com o número ' . $liquidacao_fatura->CodigoFactura, 201);
                        // dd('2. Caro estudante, tem uma factura que não foi liquidada totalmente com o número ' . $liquidacao_fatura->CodigoFactura);
                    }
                }
                
                $diplomado = DB::table('tb_matriculas')->where('estado_matricula', 'diplomado')->where('Codigo', $matricula->codigo_matricula)->select('*')->first();

                if ($diplomado && $value['TipoServico'] == 'Mensal') {

                    return response()->json('Caro estudante, não lhe é permitido efectuar o pagamento de propina visto estar diplomado!', 201);
                }

                if (($anoDesignacao->Codigo >= $anoCorrente) || ($anoDesignacao->Codigo == 1)) {
                    try {
                        $mes_existe = DB::table('factura_items')->join('factura', 'factura.Codigo', 'factura_items.CodigoFactura')
                        ->join('mes_temp', 'mes_temp.id', 'factura_items.mes_temp_id')
                        ->where('factura.ano_lectivo', $ano)
                        ->where('factura.CodigoMatricula', $matricula->codigo_matricula)
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

                        return response()->json('Já existe uma fatura do mês ' . $mes_existe->mes . '. Clique em registar pagamento, e efectue o pagamento dessa fatura.', 201);
                    }
                } else {

                    try {
                        $mes_existeAntigo = DB::table('factura_items')->join('factura', 'factura.Codigo', 'factura_items.CodigoFactura')->where('factura.ano_lectivo', $ano)->where('factura.CodigoMatricula', $matricula->codigo_matricula)->where('factura_items.Mes', $value['Mes'])->where('factura_items.Mes', '!=', '#')->select('*', 'factura_items.Mes as mes')->where('factura.estado', '!=', 3)->first();
                        //code...
                    } catch (\Throwable $th) {
                        $mes_existeAntigo = null;
                    }

                    if ($mes_existeAntigo) {

                        return response()->json('Já existe uma fatura do mês ' . $mes_existeAntigo->mes . '. Clique em registar pagamento, e efectue o pagamento dessa fatura', 201);
                    }
                }

                $saldo = DB::table('tb_preinscricao')->select('saldo', 'saldo_anterior', 'saldo_reset')->where('Codigo', $preinscricao->Codigo)->first();

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
            $fatura['ValorEntregue'] = $data1['valor_depositado'];
            $fatura['TotalMulta'] = $multa;
            $fatura['ano_lectivo'] = $ano;
            $fatura['obs'] = "Pagamento feito a cash";
            $fatura['codigo_descricao'] = 2;
            $fatura['estado'] = 1;
            $result['codigo_fatura'] = DB::table('factura')
                ->insertGetId($fatura);
            $codigo_fatura = $result['codigo_fatura'];
            //$request['codigo_factura']=$codigo_fatura;
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

                        if (/*$dados_pagamentos['DataBanco']>=$desconto_especial_outubro->data_inicio && */$desconto_especial_outubro && $dados_pagamentos['DataBanco'] <= $desconto_especial_outubro->data_fim && $ano < $anoCorrente) {

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
            ->select('factura.Codigo', 'factura.ValorAPagar', 'factura.ano_lectivo')
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
            $result['message'] = 'Pagamento efectuado com seu saldo disponível! Por favor, verifique a fatura gerada pelo sistema.';
            try {
                try {
                
                    $ano = DB::table('tb_ano_lectivo')->where('Codigo', $anoCorrente)->first();

                    $pagamento['Data'] = date('Y-m-d');
                    $pagamento['Observacao'] = 'Pagamento_Saldo';
                    $pagamento['AnoLectivo'] = $fact_aluno->ano_lectivo;
                    $pagamento['Codigo_PreInscricao'] = $preinscricao->Codigo;
                    $pagamento['valor_depositado'] = $valor_apagar;
                    $pagamento['Totalgeral'] = $valor_apagar;
                    $pagamento['fk_utilizador'] =  auth()->user()->codigo_importado;
                    $pagamento['Utilizador'] =  auth()->user()->codigo_importado;
                    $pagamento['DataRegisto'] = date('Y-m-d H:i:s');
                    $pagamento['codigo_factura'] = $fact_aluno->Codigo;
                    $pagamento['estado'] = 1;
                    $pagamento['corrente'] = 1;

                    $id_pag = DB::table('tb_pagamentos')->insertGetId($pagamento);
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
                    $saldo = DB::table('tb_preinscricao')
                        ->select('saldo', 'saldo_anterior')
                        ->where('Codigo', $preinscricao->Codigo)
                        ->first();

                    $saldo_atual = $saldo->saldo - $valor_apagar;

                    $novoSaldo = DB::table('tb_preinscricao')->where('tb_preinscricao.Codigo', $preinscricao->Codigo)->update(['saldo' => $saldo_atual > 0 ? $saldo_atual : 0, 'saldo_anterior' => $saldo->saldo]);
                } catch (\Exception $e) {
                    DB::rollback();
                    throw $e;
                }
                try {
                    DB::table('factura')->where('factura.Codigo', $codigo_fatura)->update(['estado' => 1, 'ValorEntregue' => $valor_apagar]);
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
        } else { // SE NAO TEM SALDO SUFICIENTE
        
            $result['message'] = 'Pagamento efectuado com sucesso! Por favor, verifique a factura gerada pelo sistema.';

            try {

                $pagamento['Data'] = date('Y-m-d');
                $pagamento['Observacao'] = 'Pagamento_Saldo';
                $pagamento['AnoLectivo'] = $anoCorrente;
                $pagamento['Codigo_PreInscricao'] = $preinscricao->Codigo;
                $pagamento['valor_depositado'] = $saldo->saldo;
                $pagamento['Totalgeral'] =  $valor_apagar;
                $pagamento['DataRegisto'] = date('Y-m-d H:i:s');
                $pagamento['codigo_factura'] = $codigo_fatura;
                $pagamento['fk_utilizador'] =  auth()->user()->codigo_importado;
                $pagamento['Utilizador'] =  auth()->user()->codigo_importado;
                $pagamento['Observacao'] =  'Pagamento efectuado a Cash!';
                $pagamento['estado'] = 1;
                
                if ($saldo->saldo > 0) {
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

            try {

                $ano = DB::table('tb_ano_lectivo')->where('Codigo', $anoCorrente)->first();

                $fact_aluno = DB::table('factura')
                    ->join('tb_matriculas', 'tb_matriculas.Codigo', 'factura.CodigoMatricula')
                    ->join('tb_admissao', 'tb_admissao.Codigo', '=', 'tb_matriculas.Codigo_Aluno')
                    ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
                    ->select('factura.Codigo', 'factura.ValorAPagar', 'factura.ano_lectivo',
                        'factura.ValorEntregue', 'CodigoMatricula', 'tb_preinscricao.Codigo as codigo_preinscricao'
                    )
                    ->where('factura.Codigo', $codigo_fatura)
                    ->where('tb_preinscricao.Codigo', $preinscricao->Codigo)
                    ->first();

                $anoLectivo = DB::table('tb_ano_lectivo')->where('Codigo', $fact_aluno->ano_lectivo)->first();

                $factura_items = DB::table('factura_items')
                    ->join('factura', 'factura_items.CodigoFactura', '=', 'factura.Codigo')
                    ->select('factura_items.*')
                    ->where('factura.Codigo', $fact_aluno->Codigo)
                    ->get();

                $servico_mensal = DB::table('factura_items')
                    ->join('factura', 'factura_items.CodigoFactura', '=', 'factura.Codigo')
                    ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', '=', 'factura_items.CodigoProduto')
                    ->select('*')
                    ->where('factura.Codigo', $fact_aluno->Codigo)
                    ->where('tb_tipo_servicos.TipoServico', 'Mensal')
                    ->first();

                $valor_falta = 0;
                $valor_falta = $fact_aluno->ValorAPagar - $fact_aluno->ValorEntregue;


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


                    if ($saldo->saldo > 0) {

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
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

            try {

                $saldo_antes = 0;
                $saldo_atual = $saldo->saldo - $valor_apagar - $fact_aluno->ValorEntregue;

                if ($saldo->saldo > 0) {
                    $saldo_antes = $saldo->saldo;
                }
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

            try {
                $estado_fatura = 0;
                if ($saldo->saldo >= $valor_falta) {
                    $estado_fatura = 1;
                } else {
                    $estado_fatura = 0;
                }

                DB::table('factura')
                    ->where('factura.Codigo', $fact_aluno->Codigo)
                    ->update(['estado' => $estado_fatura]);
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }


            try {
                DB::table('factura')
                    ->where('factura.Codigo', $codigo_fatura)
                    ->update(['ValorEntregue' => $saldo_antes]);

                $valorEmSaldo = DB::table('factura')
                    ->where('factura.Codigo', $codigo_fatura)
                    ->select('ValorEntregue')
                    ->first();
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

            if ($valorEmSaldo->ValorEntregue > 0) {
                try {
                    $valorGuardado = $valorEmSaldo->ValorEntregue;
                    $total = 0;
                    $valorItem = DB::table('factura_items')
                        ->where('factura_items.CodigoFactura', $codigo_fatura)
                        ->select('*')
                        ->get();

                    $array = json_decode($valorItem, true);
                    foreach ($array as $key => $value) {

                        if ($valorGuardado > 0) {
                            if ($valorGuardado >= $value['Total']) {


                                $valorGuardado = $valorGuardado - $value['Total'];
                                try {
                                    DB::table('factura_items')
                                        ->where('factura_items.codigo', $value['codigo'])
                                        ->update([
                                            'valor_pago' => $value['Total'],
                                            'estado' => 1,
                                            'valor_a_transportar' => $valorGuardado
                                        ]);
                                } catch (\Exception $e) {
                                    DB::rollback();
                                    throw $e;
                                }

                                try {

                                    DB::table('factura')
                                        ->where('factura.Codigo', $codigo_fatura)
                                        ->update(['ValorEntregue' => $valorGuardado]);
                                } catch (\Exception $e) {
                                    DB::rollback();
                                    throw $e;
                                }
                            } else {
                                $estadoItem = 0;
                                if ($valorGuardado > ($value['Total'] * 0.5)) {
                                    $estadoItem = 2;
                                }
                                
                                DB::table('factura_items')->where('factura_items.codigo', $value['codigo'])
                                    ->update(['valor_pago' => $valorGuardado, 'estado' => $estadoItem, 'valor_a_transportar' => 0]);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    DB::rollback();
                    throw $e;
                }
            }
        }
        
        try {

            $anoLectivo = DB::table('tb_ano_lectivo')->where('Codigo', $fact_aluno->ano_lectivo)->first()->Designacao;

            $desconto_mes = 0;
            foreach ($array_fatura as $key => $fac) {

                $id_mes = null;
                $mes = null;
                $desconto_mes = $fac['descontoProduto'];
                // if ($servico_mensal && $fact_aluno->ano_lectivo != $anoCorrente) {
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
            }
        }
        try {
            DB::table('tb_pagamentos')->where('Codigo', $id_pag)
            ->update(['info_adicional' => $id_documento_validacao]);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }


        if (!$pagmnt_total_com_saldo) {
            $this->codigo_factura_em_curso = $codigo_fatura;

            try {
                //Dongala Nguinamau
                if ($data1['forma_pagamento'] != "POR REFERÊNCIA") {
                
                    $mensagens = [
                        'N_Operacao_Bancaria.alpha_num' => 'O número de operação bancária digitado é inválido. Por favor digite números e/ou letras sem espaços em branco.',
                        'N_Operacao_Bancaria.unique' => 'O número de operação bancária digitado já existe no sistema.'
                    ];


                    $validate = Validator::make($data1, [
                        // 'N_Operacao_Bancaria' => ['required', 'unique:tb_pagamentos', 'alpha_num'],
                        'Observacao' => ['max:255'],
                        'forma_pagamento' => ['required'],
                        'ContaMovimentada' => ['required'],
                        'valor_depositado' => ['numeric']
                    ], $mensagens);



                    if ($validate->fails()) {

                        return response()->json(['errors' => $validate->errors()], 422);
                    }
                }
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


        return Response()->json($result);
        //}
    }

    public function imprimirFaturaDiversos(Request $request, $id)
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
                'tb_preinscricao.saldo',
                'factura.TotalMulta as multa',
                'tb_ano_lectivo.Designacao as anoDesignacao',
                'tb_ano_lectivo.Codigo as anoCodigo',
                'factura.ValorEntregue as valor_depositado',
                'tb_turmas.Designacao as turma',
                'tb_turmas.Codigo_Classe as classe',
                'factura.obs',
                'factura.estado',
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
        $data['extenso'] = $this->valor_por_extenso($data['total_apagar'], false);

        $data['qtdPrestacoes'] = count($this->parametro_uma->totalPrestacoesPagarPorAno($fatura->ano_lectivo, $data['aluno']->codigo_tipo_candidatura));
            
        \QrCode::size(250)
            ->format('png')
            ->generate("Nº matrícula: $codigo_aluno \n Nome: $nome_aluno \n Curso: $curso_aluno \n Polo: $polo_aluno  
                Periodo: $turno_aluno \n Servico: 'Diversos'", public_path('images/qrcode.png'));

        //Recuperar os pagamentos por referências by Ndongala Nguinamau
        $data['aluno']->numero_fatura;
        $data['pagamento'] = PagamentoPorReferencia::where('factura_codigo', $data['aluno']->numero_fatura)->first();

        $pdf = PDF::loadView('pdf.fatura_diversos', $data)
            ->setPaper('a5');

        return $pdf->stream('fatura.pdf');
    }

}    
 