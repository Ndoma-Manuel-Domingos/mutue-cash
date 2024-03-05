<?php

namespace App\Http\Controllers;

use App\Models\Matricula;
use App\Models\PreInscricao;
use Illuminate\Http\Request;
use App\Http\Controllers\ClassesAuxiliares\anoAtual;
use App\Http\Controllers\Extenso;
use App\Http\Controllers\Divida\ControloDivida;
use App\Models\AnoLectivo;
use App\Models\Caixa;
use App\Models\GradeCurricularAluno;
use App\Models\Pagamento;
use Illuminate\Support\Facades\DB;
use App\Repositories\AlunoRepository;
use App\Services\DividaService;
use App\Services\prazoExpiracaoService;
use App\Services\DescontoService;
use App\Services\AnoLectivoService;
use App\Services\BolsaService;
use App\Services\FaturaService;
use App\Services\PagamentoService;
use App\Services\PropinaService;
use \Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Http;

class SearhController extends Controller
{
    public $extenso;
    public $divida;
    public $pagamentoParcial;
    public $anoAtualPrincipal;
    public $alunoRepository;
    public $dividaService;
    public $prazoExpiracaoService;
    public $descontoService;
    public $bolsaService;
    public $faturaService;
    public $pagamentoService;
    public $codigo_factura_em_curso = null;
    public $saldoHistorico;
    public $propinaService;
    public $anoLectivoService;
    public $anoLectivoCorrente;

    public function __construct()
    {
        $this->divida = new ControloDivida();
        $this->anoAtualPrincipal = new anoAtual();
        $this->anoLectivoCorrente = new anoAtual();
        $this->dividaService = new DividaService();
        $this->descontoService = new DescontoService();
        $this->bolsaService = new BolsaService();
        $this->pagamentoService = new PagamentoService();
        $this->faturaService = new FaturaService();
        $this->propinaService = new  PropinaService();
        $this->anoLectivoService = new  AnoLectivoService();
        $this->alunoRepository = new  AlunoRepository();
        $this->prazoExpiracaoService = new  prazoExpiracaoService();
        $this->extenso = new  Extenso();

        $this->middleware('auth');
    }

    public function search(Request $request)
    {
        $user = auth()->user();

        $resultado = Matricula::where('tb_matriculas.Codigo', $request->search)
            ->orWhere('tb_preinscricao.Bilhete_Identidade',  $request->search)
            ->join('tb_cursos', 'tb_matriculas.Codigo_Curso', '=', 'tb_cursos.Codigo')
            ->join('tb_admissao', 'tb_matriculas.Codigo_Aluno', '=', 'tb_admissao.Codigo')
            ->join('tb_preinscricao', 'tb_admissao.pre_incricao', '=', 'tb_preinscricao.Codigo')
            ->select(
                'tb_matriculas.Codigo',
                'tb_preinscricao.Codigo AS codigo_preinscricao',
                'tb_preinscricao.Nome_Completo',
                'tb_preinscricao.Bilhete_Identidade',
                'tb_preinscricao.user_id',
                'tb_preinscricao.saldo',
                'tb_preinscricao.codigo_tipo_candidatura',
                'tb_cursos.Designacao'
            )->first();

        if ($resultado->codigo_tipo_candidatura == 1) {
            $ano_lectivo = $this->anoAtualPrincipal->index();
        } else {
            if ($resultado->codigo_tipo_candidatura == 2) {
                $ano_lectivo = $this->anoAtualPrincipal->cicloMestrado()->Codigo;
            } else {
                $ano_lectivo = $this->anoAtualPrincipal->cicloDoutoramento()->Codigo;
            }
        }

        return response()->json(["dados" => $resultado, "ano_lectivo_id" => $ano_lectivo], 200);
    }

    public function search_preinscricao(Request $request)
    {
        $user = auth()->user();

        $resultado = PreInscricao::where('tb_preinscricao.Codigo', $request->search)
            ->orWhere('tb_preinscricao.Bilhete_Identidade',  $request->search)
            ->join('tb_cursos', 'tb_preinscricao.Curso_Candidatura', '=', 'tb_cursos.Codigo')
            ->select(
                'tb_preinscricao.Codigo AS codigo_preinscricao',
                'tb_preinscricao.Nome_Completo',
                'tb_preinscricao.Bilhete_Identidade',
                'tb_preinscricao.user_id',
                'tb_preinscricao.saldo',
                'tb_preinscricao.codigo_tipo_candidatura',
                'tb_cursos.Designacao'
            )->first();

        if ($resultado) {

            $pagamento = Pagamento::whereHas('factura', function ($query) {
                $query->where('codigo_descricao', 9)->where('ano_lectivo', $this->anoAtualPrincipal->index());
            })->where('Codigo_PreInscricao', $resultado->codigo_preinscricao)->where('AnoLectivo', $this->anoAtualPrincipal->index())->first();

            if (filled($pagamento)) {
                return response()->json(["dados" => 'Este candidato já efectuou o pagamento da taxa de inscrição'], 201);
            }

            if ($resultado->codigo_tipo_candidatura == 1) {
                $ano_lectivo = $this->anoAtualPrincipal->index();
            } elseif ($resultado->codigo_tipo_candidatura == 2) {
                $ano_lectivo = $this->anoAtualPrincipal->cicloMestrado()->Codigo;
            } else {
                $ano_lectivo = $this->anoAtualPrincipal->cicloDoutoramento()->Codigo;
            }
        }

        return response()->json(["dados" => $resultado, "ano_lectivo_id" => $ano_lectivo], 200);
    }

    public function dadosPagamentos(Request $request)
    {
        $anoCorrente = $this->anoAtualPrincipal->index();

        $data = DB::table('tb_tipo_servicos')->select('*')->where('Codigo', $this->pagamentoService->taxaServicoPorSigla(request()->get('sigla_do_servico')))->where('codigo_ano_lectivo', $anoCorrente)->first();
        return response()->json($data);
    }

    public function bancosFormaPagamento(Request $request)
    {
        $forma_pagamento = $request->get('forma_pagamento');
        $bancos = [];
        if ($forma_pagamento == 'TPA') {
            $bancos = DB::table('tb_local_pagamento')->select('*')->whereBetween('codigo', array(11, 16))->get();
        } else/*if ($forma_pagamento == 'DEPOSITO' || $forma_pagamento == 'TRANSFERENCIA' || $forma_pagamento == 'EXPRESS' || $forma_pagamento == 'POR REFERÊNCIA')*/ {
            $bancos = DB::table('tb_local_pagamento')->select('*')->where('codigo', 1)->orWhere('codigo', 3)->orWhere('codigo', 9)->orWhere('codigo', 16)->get();
        }
        return Response()->json($bancos);
    }

    public function pegaAluno(Request $request, $codigo_matricula)
    {
        $aluno = Matricula::with(['admissao.preinscricao'])->findOrFail($codigo_matricula);

        $dados = DB::table('tb_preinscricao')
            ->join('tb_admissao', 'tb_admissao.pre_incricao', 'tb_preinscricao.Codigo')
            ->join('tb_matriculas', 'tb_matriculas.Codigo_Aluno', 'tb_admissao.codigo')
            ->join('polos', 'tb_preinscricao.polo_id', '=', 'polos.id')
            ->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_preinscricao.Curso_Candidatura')
            ->join('tb_cursos as tb_curso_matricula', 'tb_curso_matricula.Codigo', '=', 'tb_matriculas.Codigo_Curso')
            ->select(
                'polos.designacao as polo',
                'tb_matriculas.Codigo as codigo_matricula',
                'tb_cursos.Codigo as codigo_curso',
                'tb_cursos.Designacao as curso',
                'tb_matriculas.Codigo_Curso as curso_matricula',
                'tb_preinscricao.AlunoCacuaco',
                'tb_curso_matricula.Designacao as curso_designacao',
                'tb_preinscricao.Nome_Completo',
                'tb_preinscricao.Saldo as saldo',
                'tb_preinscricao.codigo_tipo_candidatura as tipo_candidatura'
            )->where('tb_preinscricao.user_id', $aluno->admissao->preinscricao->user_id)->first();

        return Response()->json($dados);
    }

    public function descontoAtribuido(Request $request, $codigo_matricula)
    {
        $ano = AnoLectivo::where('estado', 'Activo')->first();

        $desconto = DB::table('tb_descontos_alunoo')->join('descontos_especiais', 'tb_descontos_alunoo.tipo_taxa_desconto_especial', 'descontos_especiais.id')
            ->where('tb_descontos_alunoo.codigo_matricula', $codigo_matricula)
            ->where('tb_descontos_alunoo.estatus_desconto_id', 1)
            ->where('tb_descontos_alunoo.codigo_anoLectivo', $ano->Codigo)
            // ->where('status',  0)
            // ->select('*', 'tb_tipo_bolsas.designacao as tipo_bolsa')
            ->first(); //Abordagem do ano actual
     
        // dd($ano, $desconto, $codigo_matricula);
     
        if ($desconto) {
            return Response()->json($desconto);
        } else {
            return Response()->json('');
        }
    }
    
    public function pegaAnolectivo(Request $request, $codigo_matricula)
    {
        $ano_lectivo = null;

        $aluno = Matricula::with(['admissao.preinscricao'])->findOrFail($codigo_matricula);

        $dados = DB::table('tb_preinscricao')
            ->join('tb_admissao', 'tb_admissao.pre_incricao', 'tb_preinscricao.Codigo')
            ->join('tb_matriculas', 'tb_matriculas.Codigo_Aluno', 'tb_admissao.codigo')
            ->join('polos', 'tb_preinscricao.polo_id', '=', 'polos.id')
            ->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_preinscricao.Curso_Candidatura')
            ->join('tb_cursos as tb_curso_matricula', 'tb_curso_matricula.Codigo', '=', 'tb_matriculas.Codigo_Curso')
            ->select('tb_preinscricao.codigo_tipo_candidatura as tipo_candidatura')->where('tb_preinscricao.user_id', $aluno->admissao->preinscricao->user_id)->first();

        if ($dados->tipo_candidatura == 1) {
            $ano_lectivo = $this->anoAtualPrincipal->index();
        } else {
            if ($dados->tipo_candidatura == 2) {
                $ano_lectivo = $this->anoAtualPrincipal->cicloMestrado()->Codigo;
            } else {
                $ano_lectivo = $this->anoAtualPrincipal->cicloDoutoramento()->Codigo;
            }
        }
        return Response()->json($ano_lectivo);
    }


    public function saldo(Request $request, $codigo_matricula)
    {
        $aluno = Matricula::with(['admissao.preinscricao'])->findOrFail($codigo_matricula);

        $saldo = DB::table('tb_preinscricao')->select('saldo')->where('user_id', $aluno->admissao->preinscricao->user_id)->first()->saldo;

        return Response()->json($saldo);
    }

    public function mesUltimo(Request $request, $codigo_matricula)
    {
        $anoCorrente = $this->anoAtualPrincipal->index();

        $aluno = Matricula::with(['admissao.preinscricao'])->findOrFail($codigo_matricula);

        $matricula_aluno = DB::table('tb_grade_curricular_aluno')
            ->join('tb_matriculas', 'tb_matriculas.Codigo', '=', 'tb_grade_curricular_aluno.codigo_matricula')
            ->join('tb_admissao', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
            ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
            ->join('tb_grade_curricular_aluno_avaliacoes', 'tb_grade_curricular_aluno.codigo_grade_curricular', '=', 'tb_grade_curricular_aluno_avaliacoes.codigo')
            ->where('tb_preinscricao.user_id', $aluno->admissao->preinscricao->user_id)->select('tb_matriculas.Codigo AS matricula')->first();

        $anoCorrente = $this->anoAtualPrincipal->index();
        $ano = $request->get('ano');
        if (!$ano) {
            $ano = $anoCorrente; //171121
        }
        $anoLectivo = DB::table('tb_ano_lectivo')
            ->where('Codigo', $ano)
            ->select('Codigo', 'Designacao')
            ->first();

        $bolseiro = $this->bolsaService->BolsaPorSemestreCemPorCento($codigo_matricula, $anoLectivo->Codigo, 1);

        $candidato = DB::table('tb_preinscricao')
            ->select('Codigo', 'polo_id', 'AlunoCacuaco', 'Curso_Candidatura')
            ->where('user_id', (int)$aluno->admissao->preinscricao->user_id)
            ->first();

        $matricula = DB::table('tb_preinscricao')
            ->join('tb_admissao', 'tb_admissao.pre_incricao', '=', 'tb_preinscricao.Codigo')
            ->join('tb_matriculas', 'tb_matriculas.Codigo_Aluno', '=', 'tb_admissao.codigo')
            ->where('tb_preinscricao.Codigo', $candidato->Codigo)
            ->select('tb_matriculas.*')
            ->first();

        $curso = '';
        if ($candidato && $matricula && ($candidato->Curso_Candidatura != $matricula->Codigo_Curso)) {
            $curso = DB::table('tb_cursos')
                ->join('tb_preinscricao', 'tb_cursos.Codigo', '=', 'tb_preinscricao.Curso_Candidatura')
                ->select('tb_cursos.Designacao as curso')
                ->where('tb_preinscricao.Codigo', $candidato->Codigo)
                ->first();
        } else {
            $curso = DB::table('tb_cursos')
                ->join('tb_matriculas', 'tb_cursos.Codigo', '=', 'tb_matriculas.Codigo_Curso')
                ->select('tb_cursos.Designacao as curso')
                ->where('tb_matriculas.Codigo', $matricula->Codigo)
                ->first();
        }
        $data['propina'] = DB::table('tb_tipo_servicos')
            ->select('Descricao', 'Preco', 'TipoServico', 'Codigo')
            ->where('Descricao', 'like', '%' . $curso->curso . '%')
            ->where('cacuaco', $candidato->AlunoCacuaco)
            ->first();


        //Adicionei condição do Ciclos pós-graduação
        if ((int)$anoLectivo->Designacao <= 2019 && ($anoLectivo->Designacao != $this->anoAtualPrincipal->cicloMestrado()->Designacao) && ($anoLectivo->Designacao != $this->anoAtualPrincipal->cicloDoutoramento()->Designacao)) {

            $CodultimoMes = DB::table('tb_pagamentos')
                ->join('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', '=', 'tb_pagamentos.Codigo')
                ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', '=', 'tb_pagamentosi.Codigo_Servico')
                ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_pagamentos.Codigo_PreInscricao')
                ->where('tb_preinscricao.Codigo', $candidato->Codigo)
                ->where('tb_tipo_servicos.TipoServico', 'Mensal')
                ->where('tb_pagamentosi.Ano', $anoLectivo->Designacao)
                ->whereIn('tb_pagamentos.estado', [0, 1])
                //->where('tb_tipo_servicos.Codigo',$data['propina']->Codigo)
                ->select(DB::raw('max(tb_pagamentosi.mes_id) as ultimo'))
                ->first();

            //pega o utilmo codigo de pagamento do ultimo mes
            $CodultimoPagamento = DB::table('tb_pagamentos')
                ->join('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', '=', 'tb_pagamentos.Codigo')
                ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', '=', 'tb_pagamentosi.Codigo_Servico')
                ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_pagamentos.Codigo_PreInscricao')
                ->where('tb_preinscricao.Codigo', $candidato->Codigo)
                ->where('tb_tipo_servicos.TipoServico', 'Mensal')
                ->where('tb_pagamentosi.Ano', $anoLectivo->Designacao)
                //->where('tb_tipo_servicos.Codigo',$data['propina']->Codigo)
                ->where('tb_pagamentosi.mes_id', $CodultimoMes->ultimo)
                ->select('tb_pagamentosi.Codigo as ultimoCodigo')
                ->first();
            if ($CodultimoPagamento) {
                //pega o ultimo mes de pagamento
                $data['ultimoMes'] = DB::table('tb_pagamentosi')
                    ->join('meses', 'meses.codigo', '=', 'tb_pagamentosi.mes_id')
                    ->select('tb_pagamentosi.Mes as mes', 'meses.codigo as prestacao')
                    ->where('tb_pagamentosi.Codigo', $CodultimoPagamento->ultimoCodigo)
                    ->first();
            } elseif (!$CodultimoPagamento) {
                $data['ultimoMes'] = '';
            }
        } else {

            $CodultimoMes = DB::table('tb_pagamentos')->join('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', '=', 'tb_pagamentos.Codigo')
                ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', '=', 'tb_pagamentosi.Codigo_Servico')
                ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_pagamentos.Codigo_PreInscricao')
                ->where('tb_preinscricao.Codigo', $candidato->Codigo)
                ->where('tb_tipo_servicos.TipoServico', 'Mensal')
                // ->where('tb_pagamentos.AnoLectivo', $anoLectivo->Codigo)
                ->where('tb_pagamentosi.Ano', $anoLectivo->Designacao)
                //->where('tb_tipo_servicos.Codigo',$data['propina']->Codigo)
                ->where('tb_pagamentos.corrente', 1)
                ->whereIn('tb_pagamentos.estado', [0, 1])
                ->select(
                    DB::raw('max(tb_pagamentosi.mes_temp_id) as ultimo'),
                    DB::raw('max(tb_pagamentos.Codigo) as codigo')
                )
                ->first();

            //pega o ultimo codigo de pagamento do ultimo mes
            $CodultimoPagamento = DB::table('tb_pagamentos')
                ->join('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', '=', 'tb_pagamentos.Codigo')
                ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', '=', 'tb_pagamentosi.Codigo_Servico')
                ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_pagamentos.Codigo_PreInscricao')
                ->where('tb_preinscricao.Codigo', $candidato->Codigo)
                ->where('tb_tipo_servicos.TipoServico', 'Mensal')
                ->where('tb_pagamentosi.Ano', $anoLectivo->Designacao)
                //->where('tb_pagamentos.AnoLectivo', $anoLectivo->Codigo)
                ->where('tb_pagamentosi.mes_temp_id', $CodultimoMes->ultimo)
                ->where('tb_pagamentos.corrente', 1)
                ->whereIn('tb_pagamentos.estado', [0, 1])
                ->select('tb_pagamentosi.Codigo as ultimoCodigo', 'tb_pagamentosi.mes_temp_id')
                ->first();

            $marcoPagamento = DB::table('tb_pagamentos')
                ->join('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', '=', 'tb_pagamentos.Codigo')
                //->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', '=', 'tb_pagamentosi.Codigo_Servico')
                ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_pagamentos.Codigo_PreInscricao')
                ->join('factura', 'factura.Codigo', '=', 'tb_pagamentos.codigo_factura')
                ->join('factura_items', 'factura.Codigo', '=', 'factura_items.CodigoFactura')
                ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', '=', 'factura_items.CodigoProduto')
                ->where('tb_preinscricao.Codigo', $candidato->Codigo)
                //->where('tb_tipo_servicos.TipoServico', 'Mensal')
                ->where('tb_pagamentos.AnoLectivo', $anoLectivo->Codigo)
                ->where('tb_pagamentosi.Mes', "MAR")
                ->where('tb_pagamentos.corrente', 0)
                ->whereIn('tb_pagamentos.estado', [0, 1])
                ->where('tb_tipo_servicos.TipoServico', 'Mensal')
                ->select(
                    'tb_pagamentosi.Codigo as ultimoCodigo',
                    'tb_pagamentosi.mes_temp_id'
                )
                ->first();


            if ($CodultimoPagamento) {
                //pega o ultimo mes de pagamento
                if ($CodultimoPagamento->mes_temp_id == 13 && $anoLectivo->Codigo == 1 && $marcoPagamento) {
                    $data['ultimoMes'] = DB::table('mes_temp')
                        //->join('mes_temp', 'mes_temp.id', '=', 'tb_pagamentosi.mes_temp_id')
                        ->select('mes_temp.designacao as mes', 'mes_temp.id', 'prestacao')
                        ->where('mes_temp.id', 14)
                        ->first();
                } else {
                    $data['ultimoMes'] = DB::table('tb_pagamentosi')
                        ->join('mes_temp', 'mes_temp.id', '=', 'tb_pagamentosi.mes_temp_id')
                        ->join('tb_pagamentos', 'tb_pagamentos.Codigo', '=', 'tb_pagamentosi.Codigo_Pagamento')
                        ->select('mes_temp.designacao as mes', 'mes_temp.id', 'mes_temp.prestacao', 'tb_pagamentos.DataRegisto')
                        ->where('tb_pagamentosi.Codigo', $CodultimoPagamento->ultimoCodigo)
                        ->first();
                }
            } elseif (!$CodultimoPagamento) {
                $data['ultimoMes'] = '';
            }

            if ($bolseiro) {

                $ultimoMes =  DB::table('mes_temp')
                    ->select('mes_temp.designacao as mes', 'mes_temp.id', 'mes_temp.prestacao')
                    ->where('mes_temp.ano_lectivo', $anoLectivo->Codigo)
                    ->where('mes_temp.semestre', 1)
                    ->orderBy('id', 'DESC')
                    ->first();

                if ($data['ultimoMes']) {
                    if ($data['ultimoMes']->prestacao > $ultimoMes->prestacao) {
                        $data['ultimoMes'];
                    } else {
                        $data['ultimoMes'] = $ultimoMes;
                    }
                } else {
                    $data['ultimoMes'];
                }
            }
        }
        return Response()->json($data['ultimoMes']);
    }

    public function getPrestacoesPorAnoLectivo($codigo_anoLectivo, $codigo_matricula)
    {
        $aluno1 = Matricula::with(['admissao.preinscricao'])->findOrFail($codigo_matricula);

        $aluno = $this->alunoRepository->dadosAlunoLogado($aluno1->admissao->preinscricao->user_id);

        $isencaoMes_tempIds = $this->getIsencaoMes_tempIds($codigo_anoLectivo, $codigo_matricula);
        $isencaoMesIds = $this->getIsencaoMesIds($codigo_anoLectivo, $codigo_matricula);

        $verificaPagamentoMarco = $this->alunoRepository->verificaPagamentoMarco($codigo_anoLectivo, $aluno->preinscricao);

        $anosLectivo = $this->anoLectivoService->AnosLectivo((int)$codigo_anoLectivo);

        $todos_meses_pagos = null;
        if ((int)$anosLectivo->Designacao <= 2019 && $anosLectivo->Designacao != $this->anoLectivoCorrente->cicloMestrado()->Designacao && $anosLectivo->Designacao != $this->anoLectivoCorrente->cicloDoutoramento()->Designacao) {
            $todos_meses_pagos = DB::table('tb_pagamentosi')->join('tb_pagamentos', 'tb_pagamentos.Codigo', 'tb_pagamentosi.Codigo_Pagamento')->where('tb_pagamentos.Codigo_PreInscricao', $this->alunoRepository->dadosAlunoLogado($aluno->admissao->preinscricao->user_id)->codigo_inscricao)->where('tb_pagamentosi.Ano', $anosLectivo->Designacao)->where('tb_pagamentosi.mes_id', '!=', null)->pluck('mes_id');
        } else {

            $todos_meses_pagos = DB::table('factura_items')
                ->join('factura', 'factura.Codigo', 'factura_items.CodigoFactura')
                ->where('factura_items.mes_temp_id', '!=', null)
                ->where('factura.ano_lectivo', $codigo_anoLectivo)
                ->where('factura.CodigoMatricula', $codigo_matricula)
                ->where('factura.corrente', 1)
                ->where('factura.codigo_descricao', '!=', 5)
                ->where('factura.estado', '!=', 3)
                ->pluck('mes_temp_id');
        }

        if ($aluno1->admissao->preinscricao->codigo_tipo_candidatura == 1) {
            if ($verificaPagamentoMarco) {
                $mes_temp_marco = DB::table('mes_temp')->where('ano_lectivo', $codigo_anoLectivo)->where('activo', 1)->whereNotIn('id', $isencaoMes_tempIds)->select('id as mes_temp_id')->orderBy('id', 'asc')->get();
                $array_meses_id = json_decode($mes_temp_marco->pluck('mes_temp_id'), true);
                array_push($array_meses_id, 1);
                $mes_tem = DB::table('mes_temp')->where('ano_lectivo', $codigo_anoLectivo)->whereIn('activo', [0, 1])->whereIn('id', $array_meses_id)->orderBy('id', 'asc')->limit(10)->get();
            } else {
                $mes_tem = DB::table('mes_temp')->where('ano_lectivo', $codigo_anoLectivo)->where('activo', 1)->whereNotIn('id', $isencaoMes_tempIds)->orderBy('id', 'asc')->get();

                if ((int)$anosLectivo->Designacao <= 2019 && $anosLectivo->Designacao != $this->anoLectivoCorrente->cicloMestrado()->Designacao && $anosLectivo->Designacao != $this->anoLectivoCorrente->cicloDoutoramento()->Designacao) {
                    $mes_tem = DB::table('meses')->whereNotIn('codigo', $isencaoMesIds)->orderBy('codigo', 'asc')->pluck('mes');
                }
            }
        } else {
            $mes_tem = DB::table('mes_temp')->where('ano_lectivo', $codigo_anoLectivo)->where('activo_posgraduacao', 1)->whereNotIn('id', $isencaoMes_tempIds)->orderBy('id', 'asc')->get();
        }

        $data['mes_temp'] = $mes_tem;

        $data['prestacoes_por_ano'] = count($this->totalPrestacoesPagarPorAno($codigo_anoLectivo, $aluno1->admissao->preinscricao->codigo_tipo_candidatura));

        $data['todos_meses_pagos'] = count($todos_meses_pagos);

        return response()->json($data);
    }

    public function totalPrestacoesPagarPorAno($codigo_anoLectivo, $codigo_tipo_candidatura)
    {
        $anosLectivo = $this->anoLectivoService->AnosLectivo($codigo_anoLectivo);


        if ($codigo_tipo_candidatura == 1) {
            $mes_tem = DB::table('mes_temp')->where('ano_lectivo', $codigo_anoLectivo)->where('activo', 1)->orderBy('id', 'asc')->get();

            if ((int)$anosLectivo->Designacao <= 2019 && $anosLectivo->Designacao != $this->anoLectivoCorrente->cicloMestrado()->Designacao && $anosLectivo->Designacao != $this->anoLectivoCorrente->cicloDoutoramento()->Designacao) {
                $mes_tem = DB::table('meses')->orderBy('codigo', 'asc')->pluck('mes');
            }
        } else {
            $mes_tem = DB::table('mes_temp')->where('ano_lectivo', $codigo_anoLectivo)->where('activo_posgraduacao', 1)->orderBy('id', 'asc')->get();
        }

        return $mes_tem;
    }

    public function getIsencaoMes_tempIds($codigo_anoLectivo, $codigo_matricula)
    {

        $aluno = Matricula::with(['admissao.preinscricao'])->findOrFail($codigo_matricula);

        $aluno = $this->alunoRepository->dadosAlunoLogado($aluno->admissao->preinscricao->user_id);

        $isencao = DB::table('tb_isencoes')
            ->where('mes_temp_id', '!=', null)
            ->where('codigo_anoLectivo', $codigo_anoLectivo)
            ->where('codigo_matricula', $aluno->matricula)
            ->where('estado_isensao', 'Activo')
            ->where('Codigo_motivo', '!=', 42)
            ->select('mes_temp_id as mes_temp_id')->get();

        $isencaoMes_tempIds = $isencao->pluck('mes_temp_id');

        return $isencaoMes_tempIds;
    }

    public function getIsencaoMesIds($codigo_anoLectivo, $codigo_matricula)
    {

        $aluno = Matricula::with(['admissao.preinscricao'])->findOrFail($codigo_matricula);

        $aluno = $this->alunoRepository->dadosAlunoLogado($aluno->admissao->preinscricao->user_id);

        $isencao = DB::table('tb_isencoes')
            ->where('mes_id', '!=', null)
            ->where('codigo_anoLectivo', $codigo_anoLectivo)
            ->where('codigo_matricula', $aluno->matricula)
            ->where('estado_isensao', 'Activo')
            ->select('mes_id as mes_id')->get();

        $isencaoMesIds = $isencao->pluck('mes_id');

        return $isencaoMesIds;
    }

    public function anosLectivoEstudante($codigo_matricula)
    {

        $aluno = Matricula::with(['admissao.preinscricao'])->findOrFail($codigo_matricula);
        // Verificar na tabela de anos anterior o seu ultimo ano incrito
        $ultimo_ano_letivo_designacao = DB::table('tb_inscricoes_ano_anterior')
            ->join('tb_ano_lectivo', 'tb_inscricoes_ano_anterior.codigo_ano_lectivo', '=', 'tb_ano_lectivo.Codigo')
            ->select('tb_ano_lectivo.Designacao')
            ->where('tb_inscricoes_ano_anterior.codigo_matricula', $codigo_matricula)
            ->orderBy('Designacao', 'DESC')
            ->first();

        // no caso de retornar nulo implica que se trata de um estudante que seus dados não foram migrados do siuma
        if ($ultimo_ano_letivo_designacao != null) {

            if ($aluno->admissao->preinscricao->codigo_tipo_candidatura == 1) {
                $anosLectivos = DB::table('tb_ano_lectivo')->select('*')->where('Designacao', '>=', $ultimo_ano_letivo_designacao->Designacao)->where('Designacao', '!=', $this->anoAtualPrincipal->cicloMestrado()->Designacao)->where('Designacao', '!=', $this->anoAtualPrincipal->cicloDoutoramento()->Designacao)->orderBy('Designacao', 'DESC')->get();
            } elseif ($aluno->admissao->preinscricao->codigo_tipo_candidatura == 2) {
                $anosLectivos = DB::table('tb_ano_lectivo')->select('*')->where('Designacao', '>=', $ultimo_ano_letivo_designacao->Designacao)->where('Designacao', '!=', $this->anoAtualPrincipal->cicloMestrado()->Designacao)->where('Designacao', '!=', $this->anoAtualPrincipal->cicloDoutoramento()->Designacao)->orderBy('Designacao', 'DESC')->get();
            } else {
                $anosLectivos = DB::table('tb_ano_lectivo')->select('*')->where('Designacao', '>=', $ultimo_ano_letivo_designacao->Designacao)->where('Designacao', '!=', $this->anoAtualPrincipal->cicloMestrado()->Designacao)->where('Designacao', '!=', $this->anoAtualPrincipal->cicloDoutoramento()->Designacao)->orderBy('Designacao', 'DESC')->get();
            }
        } else {

            // se trata de esudantes Não migrados
            $ano_letivo_designacao = DB::table('tb_confirmacoes')
                ->join('tb_ano_lectivo', 'tb_confirmacoes.Codigo_Ano_lectivo', '=', 'tb_ano_lectivo.Codigo')
                ->select('tb_ano_lectivo.Designacao')
                ->where('tb_confirmacoes.Codigo_Matricula', $codigo_matricula)
                ->orderBy('Designacao', 'ASC')
                ->first();

            //Adicionei condição do Ciclos pós-graduação
            if (!$ano_letivo_designacao) {
                $factura_ano_lectivo = DB::table('factura')->select('factura.ano_lectivo')->where('factura.CodigoMatricula', $codigo_matricula)->get();

                if ($aluno->admissao->preinscricao->codigo_tipo_candidatura == 1) {
                    $anosLectivos = DB::table('tb_ano_lectivo')->select('*')->where('Codigo', $this->anoAtualPrincipal->index())->orWhereIn('Codigo', $factura_ano_lectivo->pluck('ano_lectivo'))->orderBy('Designacao', 'DESC')->get();
                } elseif ($aluno->admissao->preinscricao->codigo_tipo_candidatura == 2) {
                    $anosLectivos = DB::table('tb_ano_lectivo')->select('*')->whereIn('Codigo', [$this->anoAtualPrincipal->index(), $this->anoAtualPrincipal->cicloMestrado()->Codigo])->orWhereIn('Codigo', $factura_ano_lectivo->pluck('ano_lectivo'))->orderBy('Designacao', 'DESC')->get();
                } else {
                    $anosLectivos = DB::table('tb_ano_lectivo')->select('*')->whereIn('Codigo', [$this->anoAtualPrincipal->index(), $this->anoAtualPrincipal->cicloDoutoramento()->Codigo])->orWhereIn('Codigo', $factura_ano_lectivo->pluck('ano_lectivo'))->orderBy('Designacao', 'DESC')->get();
                }
            } else {
                if ($aluno->admissao->preinscricao->codigo_tipo_candidatura == 1) {
                    $anosLectivos = DB::table('tb_ano_lectivo')->select('*')->where('Designacao', '>=', $ano_letivo_designacao->Designacao)->where('Designacao', '!=', $this->anoAtualPrincipal->cicloMestrado()->Designacao)->where('Designacao', '!=', $this->anoAtualPrincipal->cicloDoutoramento()->Designacao)->orderBy('Designacao', 'DESC')->get();
                } elseif ($aluno->admissao->preinscricao->codigo_tipo_candidatura == 2) {
                    $anosLectivos = DB::table('tb_ano_lectivo')->select('*')->where('Designacao', '>=', $ano_letivo_designacao->Designacao)->where('Designacao', '!=', $this->anoAtualPrincipal->cicloMestrado()->Designacao)->where('Designacao', '!=', $this->anoAtualPrincipal->cicloDoutoramento()->Designacao)->orderBy('Designacao', 'DESC')->get();
                } else {
                    $anosLectivos = DB::table('tb_ano_lectivo')->select('*')->where('Designacao', '>=', $ano_letivo_designacao->Designacao)->where('Designacao', '!=', $this->anoAtualPrincipal->cicloMestrado()->Designacao)->where('Designacao', '!=', $this->anoAtualPrincipal->cicloDoutoramento()->Designacao)->orderBy('Designacao', 'DESC')->get();
                }
            }
        }

        return Response()->json($anosLectivos);
    }


    public function servicos($codigo_ano, $codigo_matricula)
    {
        $aluno = Matricula::with(['admissao.preinscricao'])->findOrFail($codigo_matricula);

        $anoCorrente = $this->anoAtualPrincipal->index();
        // $dado = DB::table('tb_tipo_servicos')->select('Descricao', 'Preco', 'TipoServico', 'Codigo')->where('codigo_ano_lectivo', $codigo_ano)->where('Descricao', '!=', '')->where('Descricao', 'not like', '%Propina%')->where('visualizar_no_portal', 'SIM')->orderBy('Descricao', 'asc')->get();
        if ($aluno->admissao->preinscricao->codigo_tipo_candidatura == 1) {
            $dado = DB::table('tb_tipo_servicos')->select('Descricao', 'Preco', 'TipoServico', 'Codigo')->where('codigo_ano_lectivo', $codigo_ano)->where('Descricao', '!=', '')->where('Descricao', 'not like', '%Propina%')->where('visualizar_no_portal', 'SIM')->whereIn('tipo_candidatura', [0, 1])->orderBy('Descricao', 'asc')->get();
            if ($this->alunoRepository->verificaConfirmacaoNosAnosAnteriores((int)$aluno->admissao->preinscricao->user_id) == 0 && $this->alunoRepository->dadosAlunoLogado((int)$aluno->admissao->preinscricao->user_id)->estado_matricula == 'inactivo') {
                $dado = DB::table('tb_tipo_servicos')->select('Descricao', 'Preco', 'TipoServico', 'Codigo')->where('codigo_ano_lectivo', $codigo_ano)->where('Descricao', '!=', '')/*->where('sigla', '!=', 'TdR')*/->where('Descricao', 'not like', '%Propina%')->where('visualizar_no_portal', 'SIM')->whereIn('tipo_candidatura', [0, 1])->orderBy('Descricao', 'asc')->get();
            }
        }
        if ($aluno->admissao->preinscricao->codigo_tipo_candidatura == 2) {
            $dado = DB::table('tb_tipo_servicos')->select('Descricao', 'Preco', 'TipoServico', 'Codigo')->where('codigo_ano_lectivo', $codigo_ano)->where('Descricao', '!=', '')->where('Descricao', 'not like', '%Propina%')->where('visualizar_no_portal', 'SIM')->whereIn('tipo_candidatura', [0, 2, 5])->orderBy('Descricao', 'asc')->get();
        }
        if ($aluno->admissao->preinscricao->codigo_tipo_candidatura == 3) {
            $dado = DB::table('tb_tipo_servicos')->select('Descricao', 'Preco', 'TipoServico', 'Codigo')->where('codigo_ano_lectivo', $codigo_ano)->where('Descricao', '!=', '')->where('Descricao', 'not like', '%Propina%')->where('visualizar_no_portal', 'SIM')->whereIn('tipo_candidatura', [0, 3, 5])->orderBy('Descricao', 'asc')->get();
        }

        if ($codigo_ano == 1 || ($codigo_ano >= 17 && $codigo_ano != $anoCorrente)) {
            $dado = DB::table('tb_tipo_servicos')->select('Descricao', 'Preco', 'TipoServico', 'Codigo')->where('codigo_ano_lectivo', $codigo_ano)->where('Descricao', '!=', '')->where('sigla', 'JdMdFdC')->where('visualizar_no_portal', 'SIM')->orderBy('Descricao', 'asc')->get();
            if ($aluno->admissao->preinscricao->codigo_tipo_candidatura != 1) {
                $dado = DB::table('tb_tipo_servicos')->select('Descricao', 'Preco', 'TipoServico', 'Codigo')->where('codigo_ano_lectivo', $codigo_ano)->where('Descricao', '!=', '')->where('sigla', 'JdDoT')->where('visualizar_no_portal', 'SIM')->orderBy('Descricao', 'asc')->get();
            }
        }

        return Response()->json($dado);
    }

    public function getDescricaoTiposAlunos()
    {

        $data['descricao_tipo1'] = DB::table('tb_tipo_aluno')->select('designacao', 'descricao', 'status')->where('id', 1)->where('status', 1)->first();

        $data['descricao_tipo2'] = DB::table('tb_tipo_aluno')->select('designacao', 'descricao', 'status')->where('id', 2)->where('status', 1)->first();

        $data['descricao_tipo3'] = DB::table('tb_tipo_aluno')->select('designacao', 'descricao', 'status')->where('id', 3)->where('status', 1)->first();

        $data['descricao_tipo4'] = DB::table('tb_tipo_aluno')->select('designacao', 'descricao', 'status')->where('id', 4)->where('status', 1)->first();

        return response()->json($data);
    }

    public function descontoBolsa(Request $request, $codigo_matricula)
    {
        $aluno = Matricula::with(['admissao.preinscricao'])->findOrFail($codigo_matricula);

        $anoCorrente = $this->anoAtualPrincipal->index();

        $ano_lectivo = $request->ano_lectivo;
        if (!$ano_lectivo) {
            $ano_lectivo = $this->anoAtualPrincipal->index();
        }
        $id = (int)$aluno->admissao->preinscricao->user_id;
        $aluno = DB::table('tb_matriculas')
            ->join('tb_admissao', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
            ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')->where('tb_preinscricao.user_id', $id)->select('tb_matriculas.Codigo as matricula', 'tb_matriculas.Codigo_Curso as curso_matricula', 'tb_preinscricao.Curso_Candidatura as curso_preinscricao')->first();

        // $bolseiro = $this->bolsaService->Bolsa($aluno->matricula, $ano_lectivo);

        // if ($bolseiro) {
        //     return Response()->json($bolseiro);
        // } else {
        //     return Response()->json('');
        // }
        
        
        $bolseiro1 = $this->bolsaService->BolsaPorSemestre1($aluno->matricula, $ano_lectivo, 1);
        $bolseiro2 = $this->bolsaService->BolsaPorSemestre2($aluno->matricula, $ano_lectivo, 2);
        $bolseiro = "";
    
        if($bolseiro1){
          $bolseiro = $bolseiro1;
        }elseif($bolseiro2){
          $bolseiro = $bolseiro2;
        }
        if ($bolseiro) {
          return Response()->json($bolseiro);
        } else {
          return Response()->json('');
        }
    }

    public function prestacoesPorBolsaSemestre(Request $request)
    {

        $aluno = Matricula::with(['admissao.preinscricao'])->findOrFail($request->codigo_matricula);
        $ano_lectivo = $request->ano_lectivo;
        if (!$ano_lectivo) {
            $ano_lectivo = $this->anoAtualPrincipal->index();
        }
        $meses_bolsa = $this->bolsaService->prestacoesPorBolsaSemestre($ano_lectivo, $aluno->admissao->preinscricao->user_id);

        $result = null;

        return Response()->json($meses_bolsa);
    }

    public function getUltimaPrestacaoPorAnoLectivo($codigo_anoLectivo, $codigo_matricula)
    {

        $aluno = Matricula::with(['admissao.preinscricao'])->findOrFail($codigo_matricula);
        if (!$codigo_anoLectivo) {
            $codigo_anoLectivo = $this->anoAtualPrincipal->index();
        }
        $isencaoMes_tempIds = $this->getIsencaoMes_tempIds($codigo_anoLectivo, $codigo_matricula);

        if ($aluno->admissao->preinscricao->codigo_tipo_candidatura == 1) {
            $anoLectivo = DB::table('tb_confirmacoes')->where('Codigo_Matricula', $codigo_matricula)->first();
            $mes_tem = DB::table('mes_temp')->where('ano_lectivo', $codigo_anoLectivo)->where('activo', 1)->whereNotIn('id', $isencaoMes_tempIds)->orderBy('prestacao', 'desc')->first();
        } else {
            $mes_tem = DB::table('mes_temp')->where('ano_lectivo', $codigo_anoLectivo)->where('activo_posgraduacao', 1)->whereNotIn('id', $isencaoMes_tempIds)->orderBy('prestacao', 'desc')->first();
        }

        return response()->json($mes_tem);
    }

    public function getPrimeiraPrestacaoPorAnoLectivo($codigo_anoLectivo, $codigo_matricula)
    {
        $aluno = Matricula::with(['admissao.preinscricao'])->findOrFail($codigo_matricula);
        $isencaoMes_tempIds = $this->getIsencaoMes_tempIds($codigo_anoLectivo, $codigo_matricula);

        if ($aluno->admissao->preinscricao->codigo_tipo_candidatura == 1) {
            $mes_tem = DB::table('mes_temp')->where('ano_lectivo', $codigo_anoLectivo)->where('activo', 1)->whereNotIn('id', $isencaoMes_tempIds)->orderBy('prestacao', 'asc')->first();
        } else {
            $mes_tem = DB::table('mes_temp')->where('ano_lectivo', $codigo_anoLectivo)->where('activo_posgraduacao', 1)->whereNotIn('id', $isencaoMes_tempIds)->orderBy('prestacao', 'asc')->first();
        }

        $data['primeira_prestacao'] = $mes_tem;

        if (($aluno->admissao->preinscricao->codigo_tipo_candidatura == 1 && $codigo_anoLectivo == $this->anoLectivoCorrente->index()) || ($aluno->admissao->preinscricao->codigo_tipo_candidatura == 2 && $codigo_anoLectivo == $this->anoLectivoCorrente->cicloMestrado()->Codigo) || ($aluno->admissao->preinscricao->codigo_tipo_candidatura == 3 && $codigo_anoLectivo == $this->anoLectivoCorrente->cicloDoutoramento()->Codigo)) {
            try {
                //code...
                $data['prazo_desconto_ano_todo'] = $this->prazoExpiracaoService->prazoPagamentoAnoTodoComDesconto($codigo_anoLectivo, $aluno, 1); // prazo para ter o desconto de 5% pelo pagamento do ano todo. Neste caso dentro do mes da primeira prestacao
            } catch (\Throwable $th) {
                $data['prazo_desconto_ano_todo'] = null;
            }
        }
        return response()->json($data);
    }

    public function finalista(Request $request, $codigo_matricula)
    {
        $aluno = Matricula::with(['admissao.preinscricao'])->findOrFail($codigo_matricula);

        $ano_lectivo = $request->ano_lectivo;

        $ultimo_ano_lecivo = $this->anoLectivoService->getUltimoAnoLectivoInscrito($codigo_matricula)->Codigo;

        if (!$ano_lectivo) {
            $ano_lectivo = $this->anoAtualPrincipal->index();
        }

        $id = $aluno->admissao->preinscricao->user_id;

        $collection = collect([]);


        $cadeirasRestantes = 0;
        $aluno = DB::table('tb_matriculas')
            ->join('tb_admissao', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
            ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')->where('tb_preinscricao.user_id', $id)->select('tb_matriculas.Codigo as matricula', 'tb_matriculas.Codigo_Curso as curso_matricula', 'tb_preinscricao.Curso_Candidatura as curso_preinscricao')->first();


        $planoCurricular = DB::table('tb_grade_curricular')
            ->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')
            ->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')
            ->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')
            ->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')
            ->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')
            ->where('tb_grade_curricular.Codigo_Curso', $aluno->curso_matricula)
            ->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina', 'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano', 'tb_duracao.codigo as codigo_duracao')->whereIn('tb_grade_curricular.status', [1, 2])
            ->distinct('disciplina')->get()->count();

        $collection = collect([]);



        //dd($planoCurricular);
        $planoCurricular1 = DB::table('tb_grade_curricular')
            ->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')
            ->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')
            ->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')
            ->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')
            ->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')
            ->where('tb_grade_curricular.Codigo_Curso', $aluno->curso_preinscricao)
            ->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina', 'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano', 'tb_duracao.codigo as codigo_duracao')
            ->whereIn('tb_grade_curricular.status', [1, 2])
            ->distinct('disciplina')->get()->count();




        $cadeirasEliminadas1 = DB::table('tb_grade_curricular')
            ->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')
            ->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')
            ->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')
            ->join('tb_grade_curricular_aluno', 'tb_grade_curricular_aluno.codigo_grade_curricular', '=', 'tb_grade_curricular.Codigo')
            ->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')
            ->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')
            ->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina', 'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano', 'tb_grade_curricular.valor_inscricao as valor_cadeira', 'tb_duracao.codigo as codigo_duracao')
            ->where('tb_grade_curricular.Codigo_Curso', $aluno->curso_preinscricao)
            ->where('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', 3)
            ->whereIn('tb_grade_curricular.status', [1, 2])
            ->where('tb_grade_curricular_aluno.codigo_matricula', $aluno->matricula)
            ->distinct('disciplina')->get()->count();

        $cadeirasEliminadas = DB::table('tb_grade_curricular')
            ->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')
            ->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')
            ->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')
            ->join('tb_grade_curricular_aluno', 'tb_grade_curricular_aluno.codigo_grade_curricular', '=', 'tb_grade_curricular.Codigo')
            ->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')
            ->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')
            ->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina', 'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano', 'tb_grade_curricular.valor_inscricao as valor_cadeira', 'tb_duracao.codigo as codigo_duracao')
            ->where('tb_grade_curricular.Codigo_Curso', $aluno->curso_matricula)
            ->where('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', 3)
            ->whereIn('tb_grade_curricular.status', [1, 2])
            ->where('tb_grade_curricular_aluno.codigo_matricula', $aluno->matricula)
            ->distinct('disciplina')->get()->count();

        // dd($cadeirasEliminadas1, $cadeirasEliminadas);

        if ($ano_lectivo != $this->anoAtualPrincipal->index() || !$ano_lectivo) {
            $cadeirasEliminadas = DB::table('tb_grade_curricular')
                ->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')
                ->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')
                ->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')
                ->join('tb_grade_curricular_aluno', 'tb_grade_curricular_aluno.codigo_grade_curricular', '=', 'tb_grade_curricular.Codigo')
                ->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')
                ->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')
                ->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina', 'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano', 'tb_grade_curricular.valor_inscricao as valor_cadeira', 'tb_duracao.codigo as codigo_duracao')
                ->where('tb_grade_curricular.Codigo_Curso', $aluno->curso_matricula)
                ->where('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', 3)
                ->whereIn('tb_grade_curricular.status', [1, 2])
                ->where('tb_grade_curricular_aluno.codigo_ano_lectivo', '!=', $ultimo_ano_lecivo)
                ->where('tb_grade_curricular_aluno.codigo_matricula', $aluno->matricula)
                ->distinct('disciplina')->get()->count();
        }


        $planoCurricular1 = DB::table('tb_grade_curricular')
            ->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')
            ->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')
            ->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')
            ->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')
            ->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')
            ->where('tb_grade_curricular.Codigo_Curso', $aluno->curso_preinscricao)
            ->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina', 'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano', 'tb_duracao.codigo as codigo_duracao')
            ->whereIn('tb_grade_curricular.status', [1, 2])
            ->distinct('disciplina')->get()->count();

        $cadeirasEliminadaAnoCorrente = DB::table('tb_grade_curricular')
            ->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')
            ->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')
            ->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')
            ->join('tb_grade_curricular_aluno', 'tb_grade_curricular_aluno.codigo_grade_curricular', '=', 'tb_grade_curricular.Codigo')
            ->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')
            ->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')
            ->join('tb_ano_lectivo', 'tb_ano_lectivo.codigo', 'tb_grade_curricular_aluno.codigo_ano_lectivo')
            ->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina', 'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano', 'tb_grade_curricular.valor_inscricao as valor_cadeira', 'tb_duracao.codigo as codigo_duracao')
            ->where('tb_grade_curricular.Codigo_Curso', $aluno->curso_preinscricao)
            ->where('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', 3)
            ->whereIn('tb_grade_curricular.status', [1, 2])
            ->where('tb_grade_curricular_aluno.codigo_matricula', $aluno->matricula)
            ->where('tb_ano_lectivo.estado', 'Activo')
            ->distinct('disciplina')->get()->count();


        $cadeirasEliminadaAnoCorrente1 = DB::table('tb_grade_curricular')
            ->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')
            ->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')
            ->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')
            ->join('tb_grade_curricular_aluno', 'tb_grade_curricular_aluno.codigo_grade_curricular', '=', 'tb_grade_curricular.Codigo')
            ->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')
            ->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')
            ->join('tb_ano_lectivo', 'tb_ano_lectivo.codigo', 'tb_grade_curricular_aluno.codigo_ano_lectivo')
            ->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina', 'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano', 'tb_grade_curricular.valor_inscricao as valor_cadeira', 'tb_duracao.codigo as codigo_duracao')
            ->where('tb_grade_curricular.Codigo_Curso', $aluno->curso_matricula)
            ->where('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', 3)
            ->whereIn('tb_grade_curricular.status', [1, 2])
            ->where('tb_grade_curricular_aluno.codigo_matricula', $aluno->matricula)
            ->where('tb_ano_lectivo.estado', 'Activo')
            ->distinct('disciplina')->get()->count();

        $naoFinalista = 100;



        if ($aluno) {
            if (($aluno->curso_preinscricao == 1 || $aluno->curso_preinscricao == 5 || $aluno->curso_preinscricao == 9 || $aluno->curso_matricula == 28 || $aluno->curso_matricula == 29 || $aluno->curso_matricula == 30 || $aluno->curso_matricula == 31 || $aluno->curso_matricula == 32 || $aluno->curso_matricula == 33 || $aluno->curso_matricula == 34 || $aluno->curso_matricula == 35)) { //SE O ALUNO ESTA INSCRITO EM UM CURSO DE ESPECIALIDADE

                if (($aluno->curso_preinscricao == 1 || $aluno->curso_preinscricao == 5 || $aluno->curso_preinscricao == 9) && ($aluno->curso_preinscricao == $aluno->curso_matricula)) {

                    $cadeirasRestantes = $planoCurricular - $cadeirasEliminadas;
                    //Cadeiras Elminadas no Ano Corrente
                    $cadeirasRestantes = $cadeirasRestantes + $cadeirasEliminadaAnoCorrente;

                    //dd($cadeirasEliminadas);

                } elseif ($aluno->curso_matricula == 28 || $aluno->curso_matricula == 29 || $aluno->curso_matricula == 30 || $aluno->curso_matricula == 31 || $aluno->curso_matricula == 32 || $aluno->curso_matricula == 33 || $aluno->curso_matricula == 34 || $aluno->curso_matricula == 35) {

                    if ($aluno->curso_preinscricao != $aluno->curso_matricula) {

                        $cadeirasRestantes = ($planoCurricular1 + $planoCurricular) - ($cadeirasEliminadas + $cadeirasEliminadas1);

                        //Cadeiras Elminadas no Ano Corrente
                        $cadeirasRestantes = $cadeirasRestantes + $cadeirasEliminadaAnoCorrente + $cadeirasEliminadaAnoCorrente1;
                    }
                } else {

                    //ESTUDANTE EMIGRADO
                    $cadeirasRestantes = $planoCurricular - $cadeirasEliminadas;
                    //Cadeiras eliminadas no ANo Corrente
                    $cadeirasRestantes = $cadeirasRestantes + $cadeirasEliminadaAnoCorrente;
                }
            } else { // SE O ALUNO NAO ESTA INSCRITO EM UM CURSO DE ESPECIALIDADE



                $cadeirasRestantes = $planoCurricular - $cadeirasEliminadas;
                //Cadeiras Elminadas no Ano Corrente

                $cadeirasRestantes = $cadeirasRestantes + $cadeirasEliminadaAnoCorrente;
            }
        }



        return Response()->json($cadeirasRestantes);
    }

    /*public function finalista2(Request $request,  $codigo_matricula)
    {

        $aluno = Matricula::with(['admissao.preinscricao'])->findOrFail($codigo_matricula);

        $curso_matricula = $aluno->Codigo_Curso;
        $curso_preinscricao = $aluno->admissao->preinscricao->Curso_Candidatura;

        $ano_lectivo = $request->ano_lectivo;
        $ultimo_ano_lecivo = $this->anoLectivoService->getUltimoAnoLectivoInscrito($codigo_matricula)->Codigo;
        if (!$ano_lectivo) {
            $ano_lectivo = $this->anoAtualPrincipal->index();
        }

        $id = $aluno->admissao->preinscricao->user_id;

        $collection = collect([]);


        $cadeirasRestantes = 0;

        $planoCurricular = DB::table('tb_grade_curricular')
            ->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')
            ->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')
            ->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')
            ->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')
            ->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')
            ->where('tb_grade_curricular.Codigo_Curso', $curso_matricula)
            ->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina', 'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano', 'tb_duracao.codigo as codigo_duracao')->whereIn('tb_grade_curricular.status', [1, 2])
            ->distinct('disciplina')->get()->count();

        $collection = collect([]);



        //dd($planoCurricular);
        $planoCurricular1 = DB::table('tb_grade_curricular')
            ->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')
            ->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')
            ->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')
            ->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')
            ->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')
            ->where('tb_grade_curricular.Codigo_Curso', $curso_preinscricao)
            ->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina', 'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano', 'tb_duracao.codigo as codigo_duracao')
            ->whereIn('tb_grade_curricular.status', [1, 2])
            ->distinct('disciplina')->get()->count();




        $cadeirasEliminadas1 = DB::table('tb_grade_curricular')
            ->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')
            ->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')
            ->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')
            ->join('tb_grade_curricular_aluno', 'tb_grade_curricular_aluno.codigo_grade_curricular', '=', 'tb_grade_curricular.Codigo')
            ->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')
            ->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')
            ->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina', 'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano', 'tb_grade_curricular.valor_inscricao as valor_cadeira', 'tb_duracao.codigo as codigo_duracao')
            ->where('tb_grade_curricular.Codigo_Curso', $curso_preinscricao)
            ->where('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', 3)
            ->whereIn('tb_grade_curricular.status', [1, 2])
            ->where('tb_grade_curricular_aluno.codigo_matricula', $aluno->matricula)
            ->distinct('disciplina')->get()->count();

        $cadeirasEliminadas = DB::table('tb_grade_curricular')
            ->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')
            ->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')
            ->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')
            ->join('tb_grade_curricular_aluno', 'tb_grade_curricular_aluno.codigo_grade_curricular', '=', 'tb_grade_curricular.Codigo')
            ->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')
            ->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')
            ->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina', 'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano', 'tb_grade_curricular.valor_inscricao as valor_cadeira', 'tb_duracao.codigo as codigo_duracao')
            ->where('tb_grade_curricular.Codigo_Curso', $curso_matricula)
            ->where('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', 3)
            ->whereIn('tb_grade_curricular.status', [1, 2])
            ->where('tb_grade_curricular_aluno.codigo_matricula', $aluno->matricula)
            ->distinct('disciplina')->get()->count();

        // dd($cadeirasEliminadas1, $cadeirasEliminadas);

        if ($ano_lectivo != $this->anoAtualPrincipal->index() || !$ano_lectivo) {
            $cadeirasEliminadas = DB::table('tb_grade_curricular')
                ->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')
                ->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')
                ->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')
                ->join('tb_grade_curricular_aluno', 'tb_grade_curricular_aluno.codigo_grade_curricular', '=', 'tb_grade_curricular.Codigo')
                ->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')
                ->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')
                ->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina', 'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano', 'tb_grade_curricular.valor_inscricao as valor_cadeira', 'tb_duracao.codigo as codigo_duracao')
                ->where('tb_grade_curricular.Codigo_Curso', $curso_matricula)
                ->where('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', 3)
                ->whereIn('tb_grade_curricular.status', [1, 2])
                ->where('tb_grade_curricular_aluno.codigo_ano_lectivo', '!=', $ultimo_ano_lecivo)
                ->where('tb_grade_curricular_aluno.codigo_matricula', $aluno->matricula)
                ->distinct('disciplina')->get()->count();
        }


        $planoCurricular1 = DB::table('tb_grade_curricular')
            ->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')
            ->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')
            ->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')
            ->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')
            ->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')
            ->where('tb_grade_curricular.Codigo_Curso', $curso_preinscricao)
            ->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina', 'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano', 'tb_duracao.codigo as codigo_duracao')
            ->whereIn('tb_grade_curricular.status', [1, 2])
            ->distinct('disciplina')->get()->count();

        $cadeirasEliminadaAnoCorrente = DB::table('tb_grade_curricular')
            ->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')
            ->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')
            ->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')
            ->join('tb_grade_curricular_aluno', 'tb_grade_curricular_aluno.codigo_grade_curricular', '=', 'tb_grade_curricular.Codigo')
            ->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')
            ->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')
            ->join('tb_ano_lectivo', 'tb_ano_lectivo.codigo', 'tb_grade_curricular_aluno.codigo_ano_lectivo')
            ->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina', 'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano', 'tb_grade_curricular.valor_inscricao as valor_cadeira', 'tb_duracao.codigo as codigo_duracao')
            ->where('tb_grade_curricular.Codigo_Curso', $curso_preinscricao)
            ->where('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', 3)
            ->whereIn('tb_grade_curricular.status', [1, 2])
            ->where('tb_grade_curricular_aluno.codigo_matricula', $aluno->matricula)
            ->where('tb_ano_lectivo.estado', 'Activo')
            ->distinct('disciplina')->get()->count();


        $cadeirasEliminadaAnoCorrente1 = DB::table('tb_grade_curricular')
            ->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')
            ->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')
            ->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')
            ->join('tb_grade_curricular_aluno', 'tb_grade_curricular_aluno.codigo_grade_curricular', '=', 'tb_grade_curricular.Codigo')
            ->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')
            ->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')
            ->join('tb_ano_lectivo', 'tb_ano_lectivo.codigo', 'tb_grade_curricular_aluno.codigo_ano_lectivo')
            ->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina', 'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano', 'tb_grade_curricular.valor_inscricao as valor_cadeira', 'tb_duracao.codigo as codigo_duracao')
            ->where('tb_grade_curricular.Codigo_Curso', $curso_matricula)
            ->where('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', 3)
            ->whereIn('tb_grade_curricular.status', [1, 2])
            ->where('tb_grade_curricular_aluno.codigo_matricula', $aluno->matricula)
            ->where('tb_ano_lectivo.estado', 'Activo')
            ->distinct('disciplina')->get()->count();

        $naoFinalista = 100;



        if ($aluno) {
            if (($curso_preinscricao == 1 || $curso_preinscricao == 5 || $curso_preinscricao == 9 || $curso_matricula == 28 || $curso_matricula == 29 || $curso_matricula == 30 || $curso_matricula == 31 || $curso_matricula == 32 || $curso_matricula == 33 || $curso_matricula == 34 || $curso_matricula == 35)) { //SE O ALUNO ESTA INSCRITO EM UM CURSO DE ESPECIALIDADE

                if (($curso_preinscricao == 1 || $curso_preinscricao == 5 || $curso_preinscricao == 9) && ($curso_preinscricao == $curso_matricula)) {

                    $cadeirasRestantes = $planoCurricular - $cadeirasEliminadas;
                    //Cadeiras Elminadas no Ano Corrente
                    $cadeirasRestantes = $cadeirasRestantes + $cadeirasEliminadaAnoCorrente;

                    //dd($cadeirasEliminadas);

                } elseif ($curso_matricula == 28 || $curso_matricula == 29 || $curso_matricula == 30 || $curso_matricula == 31 || $curso_matricula == 32 || $curso_matricula == 33 || $curso_matricula == 34 || $curso_matricula == 35) {

                    if ($curso_preinscricao != $curso_matricula) {

                        $cadeirasRestantes = ($planoCurricular1 + $planoCurricular) - ($cadeirasEliminadas + $cadeirasEliminadas1);

                        //Cadeiras Elminadas no Ano Corrente
                        $cadeirasRestantes = $cadeirasRestantes + $cadeirasEliminadaAnoCorrente + $cadeirasEliminadaAnoCorrente1;
                    }
                } else {

                    //ESTUDANTE EMIGRADO
                    $cadeirasRestantes = $planoCurricular - $cadeirasEliminadas;
                    //Cadeiras eliminadas no ANo Corrente
                    $cadeirasRestantes = $cadeirasRestantes + $cadeirasEliminadaAnoCorrente;
                }
            } else { // SE O ALUNO NAO ESTA INSCRITO EM UM CURSO DE ESPECIALIDADE



                $cadeirasRestantes = $planoCurricular - $cadeirasEliminadas;
                //Cadeiras Elminadas no Ano Corrente

                $cadeirasRestantes = $cadeirasRestantes + $cadeirasEliminadaAnoCorrente;
            }
        }

        return Response()->json($cadeirasRestantes);
    }*/


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
            ->groupBy('factura.Codigo')
            ->orderBy('factura.Codigo', 'desc')
            ->get();

        return response()->json($referencias);
    }

    public function verificaConfirmacaoNoAnoLectivoCorrente($codigo_matricula)
    {

        $confirmacao_ano_corrente = DB::table('tb_confirmacoes')->where('Codigo_Matricula', $codigo_matricula)->where('Codigo_Ano_lectivo', $this->anoAtualPrincipal->index())->get();

        $incricao_cadeira_ano_corrente = DB::table('tb_grade_curricular_aluno')->where('codigo_matricula', $codigo_matricula)->where('codigo_ano_lectivo', $this->anoAtualPrincipal->index())->where('Codigo_Status_Grade_Curricular', 2)->get();

        if (filled($confirmacao_ano_corrente)) {

            return response()->json($confirmacao_ano_corrente);
        } elseif (filled($incricao_cadeira_ano_corrente)) {
            return response()->json($incricao_cadeira_ano_corrente);
        }

        return response()->json($incricao_cadeira_ano_corrente);
    }


    public function propina(Request $request, $codigo_matricula)
    {

        $aluno1 = Matricula::with(['admissao.preinscricao'])->findOrFail($codigo_matricula);

        $data['propina'] = '';
        $anoCorrente = $this->anoAtualPrincipal->index();
        $alunoLogado = $this->alunoRepository->dadosAlunoLogado($aluno1->admissao->preinscricao->user_id);

        $ano = $request->get('ano');


        $anoDesignacao = DB::table('tb_ano_lectivo')
            ->where('Codigo', $anoCorrente)->select('Designacao')
            ->first()->Designacao;

        if (!$ano) {
            $ano = $anoCorrente;
        }

        $candidato = DB::table('tb_preinscricao')
            ->select('Codigo', 'polo_id', 'AlunoCacuaco', 'desconto', 'codigo_curso_pagamento')
            ->where('user_id', $aluno1->admissao->preinscricao->user_id)
            ->first();

        $matricula = DB::table('tb_matriculas')
            ->join('tb_admissao', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
            ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
            ->select('tb_matriculas.Codigo as codigo_matricula', 'tb_matriculas.Codigo_Curso as codigo_curso_matricula')
            ->where('tb_preinscricao.Codigo', $candidato->Codigo)->first();

        $data['ano_lectivo_sem_cadeiras_inscritas'] = DB::table('tb_grade_curricular_aluno')
            ->where('codigo_matricula', $matricula->codigo_matricula)
            ->where('codigo_ano_lectivo', $ano)
            ->count();

        /* COMENTEI NO DIA 04.11.2022, POIS NÃO CONSIDERAVA A PROPINA DO CURSO DA MATRICULA EM ALGUNS CASOS*/
        // $curso = DB::table('tb_cursos')->join('tb_preinscricao', 'tb_cursos.Codigo', '=', 'tb_preinscricao.Curso_Candidatura')->select('tb_cursos.Designacao as curso')->where('tb_preinscricao.Codigo', $candidato->Codigo)->first();
        $curso = DB::table('tb_cursos')->select('tb_cursos.Designacao as curso')->where('Codigo', $matricula->codigo_curso_matricula)->first();


        $data['propina'] = DB::table('tb_tipo_servicos')
            ->select('Descricao', 'Preco', 'TipoServico', 'Codigo', 'valor_anterior')
            ->where('Descricao', 'like', 'propina ' . $curso->curso . '%')
            ->where('cacuaco', $candidato->AlunoCacuaco)
            ->where('codigo_ano_lectivo', $ano)
            ->first();

        //dd($data['propina'],$curso->curso,$candidato->AlunoCacuaco,$ano);
        $collection = collect([]);
        $collection1 = collect([]);

        try {
            $cursoPrincipal = $data['propina']->Descricao;
            //code...
        } catch (\Throwable $th) {
            $cursoPrincipal = null;
        }

        $temExcepcao = DB::table('curso_pagamento_excepcao')
            ->select('*')->where('codigo_matricula', $matricula->codigo_matricula)
            ->first();

        $aplicarDesconto = '';
        $data['transferencia_curso'] = 'NAO';
        if ($temExcepcao && $temExcepcao->data_fim >= date('Y-m-d')) {

            $data['transferencia_curso'] = $temExcepcao->Obs;

            $aplicarDesconto = 'SIM';
            $cursoPagamento = DB::table('tb_cursos')
                ->select('tb_cursos.Designacao')
                ->where('Codigo', $temExcepcao->codigo_curso_pagamento)
                ->first();

            $propina = DB::table('tb_tipo_servicos')
                ->select('Descricao', 'Preco', 'TipoServico', 'Codigo', 'valor_anterior')
                ->where('Descricao', 'like', 'propina ' . $cursoPagamento->Designacao . '%')
                ->where('cacuaco', $candidato->AlunoCacuaco)->where('codigo_ano_lectivo', $ano)
                ->first();


            $collection->push([
                'Descricao' => $cursoPrincipal,
                'Preco' => $propina->Preco, 'TipoServico' => $propina->TipoServico,
                'Codigo' => $propina->Codigo,
                'valor_anterior' => $propina->valor_anterior
            ]);

            $data['propina'] = $collection->first();
        }

        //Desconto desconto = valor_total_com_reajuste - valor_total_sem_reajuste até dia 30.10.2021

        $data_actual = date('Y-m-d'); //  Carbon::now();
        $data_limite = "2021-10-30";

        if (date($data_actual) <= date($data_limite) && ($ano == $anoCorrente)) {
            $propina1 = DB::table('tb_tipo_servicos')
                ->select('Descricao', 'Preco', 'TipoServico', 'Codigo', 'valor_anterior')
                ->where('Descricao', 'like', 'propina ' . $curso->curso . '%')
                ->where('cacuaco', $candidato->AlunoCacuaco)
                ->where('codigo_ano_lectivo', $ano)
                ->first();

            $collection1->push(['Descricao' => $cursoPrincipal, 'Preco' => $propina1->Preco, 'TipoServico' => $propina1->TipoServico, 'Codigo' => $propina1->Codigo, 'valor_anterior' => $propina1->valor_anterior]);

            $data['propina'] = $collection1->first();
        }


        if ($this->extenso->finalista($aluno1->admissao->preinscricao->user_id) > 0 && $this->extenso->finalista($aluno1->admissao->preinscricao->user_id) <= 3 && $candidato->AlunoCacuaco == 'NAO' && $ano == $anoCorrente) {

            $data['propina'] = DB::table('tb_tipo_servicos')
                ->select('Descricao', 'Preco', 'TipoServico', 'Codigo', 'valor_anterior')
                ->where('Descricao', 'like', 'propina ' . $curso->curso . '%')
                ->where('cacuaco', 'NAO')
                ->where('codigo_ano_lectivo', $ano)
                ->first();


            if ($aplicarDesconto == 'SIM') {

                $propina1 = DB::table('tb_tipo_servicos')
                    ->select('Descricao', 'Preco', 'TipoServico', 'Codigo', 'valor_anterior')
                    ->where('Descricao', 'like', 'propina ' . $cursoPagamento->Designacao . '%')
                    ->where('cacuaco', 'NAO')
                    ->where('codigo_ano_lectivo', $ano)
                    ->first();

                $collection1->push(['Descricao' => $cursoPrincipal, 'Preco' => $propina1->Preco, 'TipoServico' => $propina1->TipoServico, 'Codigo' => $propina1->Codigo, 'valor_anterior' => $propina1->valor_anterior]);

                $data['propina'] = $collection1->first();
            }
        } elseif ($this->extenso->finalista($aluno1->admissao->preinscricao->user_id) > 0 && $this->extenso->finalista($aluno1->admissao->preinscricao->user_id) <= 3 && $candidato->AlunoCacuaco == 'SIM' && $ano == $anoCorrente) {

            $data['propina'] = DB::table('tb_tipo_servicos')
                ->select('Descricao', 'Preco', 'TipoServico', 'Codigo', 'valor_anterior')
                ->where('Descricao', 'like', 'propina ' . $curso->curso . '%')
                ->where('cacuaco', 'SIM')
                ->where('codigo_ano_lectivo', $ano)
                ->first();


            if ($aplicarDesconto == 'SIM') {

                $propina1 = DB::table('tb_tipo_servicos')
                    ->select('Descricao', 'Preco', 'TipoServico', 'Codigo', 'valor_anterior')
                    ->where('Descricao', 'like', 'propina ' . $cursoPagamento->Designacao . '%')
                    ->where('cacuaco', 'SIM')
                    ->where('codigo_ano_lectivo', $ano)
                    ->first();

                $collection1->push(['Descricao' => $cursoPrincipal, 'Preco' => $propina1->Preco, 'TipoServico' => $propina1->TipoServico, 'Codigo' => $propina1->Codigo, 'valor_anterior' => $propina1->valor_anterior]);

                $data['propina'] = $collection1->first();
            }
        }




        $data['ano'] = $ano;
        $data['desconto'] = $candidato->desconto;


        //cal_days_in_month(CAL_GREGORIAN, $i, 2016) pegar os dias dos meses
        $data['mesesApagar'] = $this->mesesPagar(date('Y-m-d'), $tipo = 1, $mes_id = 0, $ano, $aluno1->admissao->preinscricao->user_id);

        $data['diaAtual'] = date('d');
        $data['mesAtual'] = date('m');
        $data['anoAtual'] = $anoCorrente;
        $data['taxa_nov21_jul22'] = 0;
        $data['desconto_alunos_agro_pecuaria'] = 0;
        $data['desconto_de_anuidade'] = 0;



        if ($candidato->AlunoCacuaco == 'NAO') {
            $taxa_nov21_jul22 = $this->descontoService->descontoNov21Jul22();
            $taxa_desconto_agro = $this->descontoService->descontoAgropecuaria();
            $taxa_desconto_anuidade = $this->descontoService->descontoAnuidade();

            // if ($taxa_nov21_jul22 && $alunoLogado->codigo_tipo_candidatura == 1) {
            //     if (($alunoLogado->anoLectivo >= 18) && ($alunoLogado->estado_matricula != 'inactivo') && ($alunoLogado->Codigo_Turno == 6)) {
            //         $data['taxa_nov21_jul22'] = $taxa_nov21_jul22->taxa;
            //     }
            // }
            
            $taxa_desconto_incentivo = $this->descontoService->descontosAlunosEspeciaisIncentivos($matricula->codigo_matricula);

            // desconto de incentivo para alunos noturnos
            if ($taxa_nov21_jul22 && $alunoLogado->codigo_tipo_candidatura == 1) {
                if (($alunoLogado->anoLectivo >= 18 && $alunoLogado->estado_matricula != 'inactivo' && $alunoLogado->Codigo_Turno == 6) || ($taxa_desconto_incentivo)) {
                    $data['taxa_nov21_jul22'] = $taxa_nov21_jul22->taxa;
                }
            }

            if ($taxa_desconto_agro) {
                if (($alunoLogado->anoLectivo == $taxa_desconto_agro->ano_lectivo_id && $alunoLogado->curso_matricula == $taxa_desconto_agro->curso_id && $alunoLogado->codigo_tipo_candidatura == $taxa_desconto_agro->tipo_candidatura_id) && ($alunoLogado->estado_matricula != 'inactivo')) {
                    $data['desconto_alunos_agro_pecuaria'] = ($taxa_desconto_agro->taxa / 100);
                }
            }

            // desconto de anuidade
            if ((int)$ano == $anoCorrente && $taxa_desconto_anuidade && $alunoLogado->codigo_tipo_candidatura == 1) {
                if (($alunoLogado->estado_matricula != 'inactivo')) {
                    $data['desconto_de_anuidade'] = ($taxa_desconto_anuidade->taxa / 100);
                }
            } elseif (((int)$ano == $this->anoAtualPrincipal->cicloMestrado()->Codigo || (int)$ano == $this->anoAtualPrincipal->cicloDoutoramento()->Codigo) && $taxa_desconto_anuidade && $alunoLogado->codigo_tipo_candidatura != 1) {
                if (($alunoLogado->estado_matricula != 'inactivo')) {
                    $data['desconto_de_anuidade'] = ($taxa_desconto_anuidade->taxa / 100);
                }
            }
        }

        return Response()->json($data);
    }

    public function mesesPagar($data, $tipo, $mes_id, $codigo_anoLectivo, $user_id)
    {
        $alunoLogado = $this->alunoRepository->dadosAlunoLogado($user_id);

        $dt = Carbon::now('Africa/Luanda');

        if ($tipo == 1) {

            if ($alunoLogado->codigo_tipo_candidatura == 1) {
                $meses_temp = DB::table('mes_temp')
                    ->select('id as id_mes', 'designacao as mes', 'data_limite as data', 'data_final', 'prestacao')
                    ->where('activo', 1)->where('ano_lectivo', $codigo_anoLectivo)->where('isencao', 0)->get();
            } else {

                $meses_temp = DB::table('mes_temp')
                    ->select('id as id_mes', 'designacao as mes', 'data_limite as data', 'data_final', 'prestacao')
                    ->where('activo_posgraduacao', 1)->where('ano_lectivo', $codigo_anoLectivo)->where('isencao', 0)->get();
            }
        } elseif ($tipo == 2) {

            if ($alunoLogado->codigo_tipo_candidatura == 1) {
                $meses_temp = DB::table('mes_temp')
                    ->select('id as id_mes', 'designacao as mes', 'data_limite as data', 'data_final', 'prestacao')
                    ->where('activo', 1)->where('ano_lectivo', $codigo_anoLectivo)->where('isencao', 0)->where('id', $mes_id)->get();
            } else {

                $meses_temp = DB::table('mes_temp')
                    ->select('id as id_mes', 'designacao as mes', 'data_limite as data', 'data_final', 'prestacao')
                    ->where('activo_posgraduacao', 1)->where('ano_lectivo', $codigo_anoLectivo)->where('isencao', 0)->where('id', $mes_id)->get();
            }
        }

        $array = json_decode($meses_temp, true);

        $dif = 0;
        $mesesApagar = collect([]);
        $taxa = 0;
        $posicao = '';
        foreach ($array as $key => $value) {
            $prestacoes_isentas_multa = $this->propinaService->checkIsencaoMulta($this->alunoRepository->dadosAlunoLogado($user_id)->matricula, $value['id_mes'], $codigo_anoLectivo);
            $isencao_multa_global = $this->propinaService->checkIsencaoMultaGlobal($alunoLogado->codigo_tipo_candidatura); //Isenção de multa global por tipo de candidatura

            try {
                $encode_ultimo_pagamento = json_encode($this->mesUltimo(request(), auth()->user()->id), JSON_FORCE_OBJECT);
                $decode_ultimo_pagamento = json_decode($encode_ultimo_pagamento, true);
                $actual_data = Carbon::parse($dt->toDateString());
                $data_ultimo_pagamento = Carbon::parse($decode_ultimo_pagamento['original']['DataRegisto'])->toDateString();
            } catch (\Throwable $th) {
                $encode_ultimo_pagamento = null;
                $decode_ultimo_pagamento = null;
                $data_ultimo_pagamento = null;
            }

            if ($data > $value['data'] && $isencao_multa_global == false) {
                if ($prestacoes_isentas_multa == false) {

                    $taxa = $this->parametroTaxaMulta($data, $value['data'], $value['data_final'], $meses_temp, $key);

                    if ($alunoLogado->codigo_tipo_candidatura != 1) {
                        if ($actual_data->diffInDays($data_ultimo_pagamento, true) > 30) {
                            $taxa = $this->parametroTaxaMulta($data, $data_ultimo_pagamento, $value['data_final'], $meses_temp, $key);
                        } else {
                            $taxa = 0;
                        }
                    }
                } else {
                    $taxa = 0;
                }
            } else {
                $taxa = 0;
            }

            $mesesApagar->push([
                'codigo' => $value['id_mes'],
                'mes' => $value['mes'],
                'data' => $value['data'],
                'prestacao' => $value['prestacao'],
                'taxa' => $taxa
            ]);
        }

        return $mesesApagar;
    }

    public function parametroTaxaMulta($data_banco, $datalimite, $data_final, $meses_temp, $key)
    {

        $parametroMulta = DB::table('tb_parametros_multa')->select('percentagem')->get();

        $array1 = json_decode($parametroMulta, true);
        $percentagem = collect([]);
        //dd(Carbon::now()->lastOfMonth()->endOfDay());
        $data_limite = new Carbon(date($datalimite));
        $data_actual =  new Carbon(date($data_banco));

        $taxa = 0;
        if (date($datalimite) < date($data_banco) && $data_actual->month == $data_limite->month) {
            //dd(1);
            $parametroMulta = DB::table('tb_parametros_multa')
                ->select('percentagem', 'codigo')->where('codigo', 1)->first();
            // taxa 5
            return $parametroMulta->percentagem;
        } else if ($data_limite->diffInMonths($data_actual, false) <= 1 && $data_actual > $data_limite /*$data_actual->month>$data_limite->month */) {
            ////dd(2);
            $parametroMulta = DB::table('tb_parametros_multa')
                ->select('percentagem', 'codigo')->where('codigo', 2)->first();
            //$taxa=7;
            return $parametroMulta->percentagem;
        } else if ($data_limite->diffInMonths($data_actual, false) >= 2) {
            //dd(3);
            $parametroMulta = DB::table('tb_parametros_multa')
                ->select('percentagem', 'codigo')->where('codigo', 3)->first();
            return $parametroMulta->percentagem;
            //$taxa=10;

        }
        return $taxa;
    }

    public function ciclos()
    {
        $data['ciclo_mestrado'] = DB::table('tb_ano_lectivo')
            ->where('Designacao', 'Ciclo Mestrado')->select('Codigo', 'Designacao')
            ->get();

        $data['ciclo_doutoramento'] = DB::table('tb_ano_lectivo')
            ->where('Designacao', 'Ciclo Doutoramento')->select('Codigo', 'Designacao')
            ->get();

        return Response()->json($data);
    }

    public function anoLectivoActual()
    {
        $data['ano_lectivo_actual'] = $this->anoAtualPrincipal->index();

        return Response()->json($data);
    }

    public function verificarCaixaAberto()
    {
        $verificar_caixa_aberto = Caixa::where('operador_id', Auth::user()->codigo_importado)->where('status', 'aberto')->first();

        $message = "Por favor! antes de sair do sistema pedimos que faça o fecho do caixa que abriu.";
        $messag2 = "Gostariamos de lembrar ao caro utilizador que não fez o fecho do caixa que abriu.";
        $message3 = "Conta encerrada com sucesso.";

        if ($verificar_caixa_aberto) {
            return response()->json(['message' => $message, 'status' => 201]);
        } else {
            return response()->json(['message' => $message3, 'status' => 200]);
        }
    }

    public function teste($pagamento_id = '731492', $operador_id)
    {
        //Api para validação de pagamento via ADMIN JSF
        $user = auth()->user();

        // $response = Http::get("http://10.10.50.112/mutue/maf/validacao_pagamento?pkPagamento={$pagamento_id}&pkUtilizador={$operador_id}");

        // $data = $response->json();
        $pagamento = Pagamento::findOrFail($pagamento_id);
        $ano = AnoLectivo::where('estado', 'Activo')->first();
        
        if($ano){
        
            if ($pagamento) {
    
                $pagamento->estado = 1;
                $pagamento->update();
    
                $preinscricao = Preinscricao::leftJoin('tb_admissao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
                    ->leftJoin('tb_matriculas', 'tb_admissao.Codigo', '=', 'tb_matriculas.Codigo_Aluno')
                    ->select('tb_matriculas.Codigo AS codigo_matricula','tb_preinscricao.Codigo AS codigo_preinscricao')
                    ->findOrFail($pagamento->Codigo_PreInscricao);
                if ($preinscricao) {
                    
                    $grades = GradeCurricularAluno::where('codigo_matricula', $preinscricao->codigo_matricula)->where('codigo_ano_lectivo', $ano->Codigo)->get();
                    if($grades){
                        foreach($grades as $grade){
                            $update = GradeCurricularAluno::findOrFail($grade->codigo);
                            $update->Codigo_Status_Grade_Curricular = 2;
                            $update->update();
                        }
                    }
                
                }
            }
        }

        // return $data;
    }
}
