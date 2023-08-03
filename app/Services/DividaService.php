<?php

namespace App\Services;

use DB;
use App\Repositories\AlunoRepository;
use App\Http\Controllers\ClassesAuxiliares\anoAtual;
use App\Models\Matricula;
use App\Models\PreInscricao;
use App\Services\PropinaService;
use App\Services\BolsaService;
use App\Services\AnoLectivoService;
use App\User;
use Auth;

class DividaService
{
  public $alunoRepository;
  public $mesesPagarPropina;
  public $anoLectivoService;
  public $anoAtualPrincipal; //para classe
  public $bolsaService;
  public $codigoAnoCorrente; //variavel
  public function __construct()
  {
    $this->alunoRepository = new AlunoRepository();
    $this->mesesPagarPropina = new PropinaService();
    $this->anoLectivoService = new AnoLectivoService();
    $this->anoAtualPrincipal = new anoAtual();
    $this->bolsaService = new BolsaService();
    $this->codigoAnoCorrente = $this->anoAtualPrincipal->index();
  }
  public function Anolectivo($ano_lectivo)
  {
    $ano = DB::table('tb_ano_lectivo')->where('Codigo', $ano_lectivo)->first();
    return $ano;
  }

  public function pagouOutubro($codigo_inscricao) // outubro de 2020-2021
  {

    $pagamento = DB::table('tb_pagamentos')
      ->join('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', '=', 'tb_pagamentos.Codigo')
      ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_pagamentos.Codigo_PreInscricao')
      ->join('factura', 'factura.Codigo', '=', 'tb_pagamentos.codigo_factura')
      ->join('factura_items', 'factura.Codigo', '=', 'factura_items.CodigoFactura')
      ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', '=', 'factura_items.CodigoProduto')
      ->where('tb_preinscricao.Codigo', $codigo_inscricao)
      ->where('tb_pagamentos.AnoLectivo', 1)
      ->where('tb_pagamentosi.mes_temp_id', 5)
      ->where('factura.estado', '!=', 3)
      ->where('tb_tipo_servicos.TipoServico', 'Mensal')
      ->where('tb_pagamentos.estado', 1)->select(

        'tb_pagamentosi.mes_temp_id'
      )
      ->first();

    return $pagamento;
  }

  public function mesesPagosPorAnoPropina($ano_lectivo, $codigo_inscricao)
  {

    $user = PreInscricao::findOrFail($codigo_inscricao);

    if ($user->codigo_tipo_candidatura == 1) {
      $ano_lectivo = $ano_lectivo;
    } elseif ($user->codigo_tipo_candidatura == 2) {
      $ano_lectivo = $this->anoAtualPrincipal->cicloMestrado()->Codigo;
    } else {
      $ano_lectivo = $this->anoAtualPrincipal->cicloDoutoramento()->Codigo;
    }

    $dados_ano = $this->Anolectivo($ano_lectivo);
    /*Houve uma epoca em que a designacao do ano lectivo para 2020 era so mesmo "2020", mas mudou-se
     2020-2021 sem se alterar na bd. Essa bd nao ajuda!*/
    /* estou a filtrar pelo ano do pagamentosi porque nos pagamentos de negociacao de dividas na tb_pagamentos o ano lectivo
     esta o ano corrente, portanto tenho que filtrar pelo ano correcto que é o da tb_pagamentosi*/

    $pagamentos = DB::table('tb_pagamentos')->join('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', '=', 'tb_pagamentos.Codigo')
    ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', '=', 'tb_pagamentosi.Codigo_Servico')
      ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_pagamentos.Codigo_PreInscricao')->join('mes_temp', 'mes_temp.id', 'tb_pagamentosi.mes_temp_id')->select(DB::raw(
        'ANY_VALUE(tb_pagamentosi.Valor_Pago) as valor,ANY_VALUE(tb_pagamentosi.mes_temp_id) as codigo_mes,ANY_VALUE(mes_temp.designacao) as mes'
      ))
      ->where('tb_preinscricao.Codigo', $codigo_inscricao)->where('tb_pagamentosi.Ano', $dados_ano->Designacao)
      ->where('tb_tipo_servicos.TipoServico', 'Mensal')->where('tb_pagamentos.estado', 1)->where('tb_pagamentos.corrente', 1)->groupBy('tb_pagamentosi.mes_temp_id')->get();
    // o parametro ano tem que estar com os pagamentosi.Ano porque o ano de pagamento de negociacao de divida é o ano corrente
    //dd($pagamentos);
    return $pagamentos;
  }

  public function mesesPagosPorAnoPropinaAPI($codigo_matricula, $ano_lectivo, $codigo_inscricao)
  {

    $user = DB::table('tb_matriculas')
      ->join('tb_admissao', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')

      ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
      //->select('tb_admissao.pre_incricao as admitido')
      ->select(
        'tb_matriculas.*',
        'tb_preinscricao.Codigo as codigo_inscricao',
        'tb_preinscricao.AlunoCacuaco as aluno_cacuaco',
        'tb_preinscricao.desconto as desconto',
        'tb_preinscricao.codigo_tipo_candidatura',
        'tb_preinscricao.user_id'
      )
      ->where('tb_matriculas.Codigo', $codigo_matricula)->first();

    if ($user->codigo_tipo_candidatura == 1) {
      $ano_lectivo = $ano_lectivo;
    } elseif ($user->codigo_tipo_candidatura == 2) {
      $ano_lectivo = $this->anoAtualPrincipal->cicloMestrado()->Codigo;
    } else {
      $ano_lectivo = $this->anoAtualPrincipal->cicloDoutoramento()->Codigo;
    }

    $dados_ano = $this->Anolectivo($ano_lectivo);
    /*Houve uma epoca em que a designacao do ano lectivo para 2020 era so mesmo "2020", mas mudou-se
     2020-2021 sem se alterar na bd. Essa bd nao ajuda!*/
    /* estou a filtrar pelo ano do pagamentosi porque nos pagamentos de negociacao de dividas na tb_pagamentos o ano lectivo
     esta o ano corrente, portanto tenho que filtrar pelo ano correcto que é o da tb_pagamentosi*/

    $pagamentos = DB::table('tb_pagamentos')->join('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', '=', 'tb_pagamentos.Codigo')->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', '=', 'tb_pagamentosi.Codigo_Servico')
      ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_pagamentos.Codigo_PreInscricao')->join('mes_temp', 'mes_temp.id', 'tb_pagamentosi.mes_temp_id')->select(DB::raw(
        'ANY_VALUE(tb_pagamentosi.Valor_Pago) as valor,ANY_VALUE(tb_pagamentosi.mes_temp_id) as codigo_mes,ANY_VALUE(mes_temp.designacao) as mes'
      ))->where('tb_preinscricao.Codigo', $codigo_inscricao)->where('tb_pagamentosi.Ano', $dados_ano->Designacao)
      ->where('tb_tipo_servicos.TipoServico', 'Mensal')->where('tb_pagamentos.estado', 1)->where('tb_pagamentos.corrente', 1)->groupBy('tb_pagamentosi.mes_temp_id')->get();
    // o parametro ano tem que estar com os pagamentosi.Ano porque o ano de pagamento de negociacao de divida é o ano corrente
    //dd($pagamentos);
    return $pagamentos;
  }


  public function getPrestacoesPorAnoLectivo($codigo_anoLectivo, $arrayMesesPagos, $codigo_matricula)
  {

    $aluno = Matricula::with(['admissao.preinscricao'])->findOrFail($codigo_matricula);

    if ($aluno->admissao->preinscricao->codigo_tipo_candidatura == 1) {
      $codigo_anoLectivo = $codigo_anoLectivo;
    } elseif ($user->admissao->preinscricao->codigo_tipo_candidatura == 2) {
      $codigo_anoLectivo = $this->anoAtualPrincipal->cicloMestrado()->Codigo;
    } else {
      $codigo_anoLectivo = $this->anoAtualPrincipal->cicloDoutoramento()->Codigo;
    }

    $isencao = DB::table('tb_isencoes')
      ->where('mes_temp_id', '!=', null)
      ->where('codigo_matricula', $aluno->Codigo)
      ->where('estado_isensao', 'Activo')
      ->where('codigo_anoLectivo', $codigo_anoLectivo)
      ->select('tb_isencoes.mes_temp_id as mes_temp_id')
      ->get();

    $isencaoIds = $isencao->pluck('mes_temp_id');

    $value = $aluno->admissao->preinscricao->codigo_tipo_candidatura;

    $prestacoes = DB::table('mes_temp')->where('ano_lectivo', $codigo_anoLectivo)->where(function ($q) use($value) {
      if ($value == 1) {
        $q->where('activo', 1);
      } else {
        $q->where('activo_posgraduacao', 1);
      }
    })->whereNotIn('id', $isencaoIds)->whereNotIn('id', $arrayMesesPagos)->orderBy('id', 'asc')->get();

    return $prestacoes;
  }


  public function getPrestacoesPorAnoLectivoAPI($codigo_matricula, $codigo_anoLectivo, $arrayMesesPagos, $dataAtual)
  {
    // $aluno = $this->alunoRepository->dadosAlunoLogado();

    $user = DB::table('tb_matriculas')
      ->join('tb_admissao', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')

      ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
      //->select('tb_admissao.pre_incricao as admitido')
      ->select(
        'tb_matriculas.*',
        'tb_preinscricao.Codigo as codigo_inscricao',
        'tb_preinscricao.AlunoCacuaco as aluno_cacuaco',
        'tb_preinscricao.desconto as desconto',
        'tb_preinscricao.codigo_tipo_candidatura',
        'tb_preinscricao.user_id'
      )
      ->where('tb_matriculas.Codigo', $codigo_matricula)->first();

    if ($user->codigo_tipo_candidatura == 1) {
      $codigo_anoLectivo = $codigo_anoLectivo;
    } elseif ($user->codigo_tipo_candidatura == 2) {
      $codigo_anoLectivo = $this->anoAtualPrincipal->cicloMestrado()->Codigo;
    } else {
      $codigo_anoLectivo = $this->anoAtualPrincipal->cicloDoutoramento()->Codigo;
    }

    $isencao = DB::table('tb_isencoes')->where('mes_temp_id', '!=', null)->where('codigo_matricula', $codigo_matricula)
      ->where('estado_isensao', 'Activo')->where('codigo_anoLectivo', $codigo_anoLectivo)->select('tb_isencoes.mes_temp_id as mes_temp_id')->get();

    $isencaoIds = $isencao->pluck('mes_temp_id');

    $user->codigo_tipo_candidatura;

    if ($user->codigo_tipo_candidatura == 1) {

      $prestacoes = DB::table('mes_temp')->where('ano_lectivo', $codigo_anoLectivo)
        ->where('activo', 1)
        ->whereNotIn('id', $isencaoIds)->whereNotIn('id', $arrayMesesPagos)->where('data_inicial', '<=', $dataAtual)->orderBy('id', 'asc')->get();
    } else {

      $prestacoes = DB::table('mes_temp')->where('ano_lectivo', $codigo_anoLectivo)->where('activo_posgraduacao', 1)
        ->whereNotIn('id', $isencaoIds)
        ->whereNotIn('id', $arrayMesesPagos)->orderBy('id', 'asc')->get();
    }

    return $prestacoes;
  }
  // para anos anteriores a 2020
  public function getPrestacoesAnosAnterioresPorAnoLectivo($codigo_anoLectivo, $user_id)
  {
    $aluno = $this->alunoRepository->dadosAlunoLogado($user_id);

    $user = PreInscricao::where('tb_preinscricao.user_id', $user_id)->first();

    $isencao = DB::table('tb_isencoes')->where('mes_id', '!=', null)->where('codigo_matricula', $aluno->matricula)
      ->where('estado_isensao', 'Activo')
      ->where('codigo_anoLectivo', $codigo_anoLectivo)->select('tb_isencoes.mes_id as mes_id')->get();

    $isencaoIds = $isencao->pluck('mes_id');

    return $isencaoIds;
  }


  public function propinaAluno($codigo_inscricao, $aluno_cacuaco, $ano_lectivo)
  {

    $preinscricao = PreInscricao::findOrFail($codigo_inscricao);

    if ($preinscricao->codigo_tipo_candidatura == 1) {
      $ano_lectivo = $ano_lectivo;
    } elseif ($preinscricao->codigo_tipo_candidatura == 2) {
      $ano_lectivo = $this->anoAtualPrincipal->cicloMestrado()->Codigo;
    } else {
      $ano_lectivo = $this->anoAtualPrincipal->cicloDoutoramento()->Codigo;
    }

    $curso = DB::table('tb_cursos')->join('tb_preinscricao', 'tb_cursos.Codigo', '=', 'tb_preinscricao.Curso_Candidatura')
    ->select('tb_cursos.Designacao as curso', 'tb_cursos.Codigo as codigo_curso')
    ->where('tb_preinscricao.Codigo', $preinscricao->Codigo)
    ->first();

    $propina = DB::table('tb_tipo_servicos')
    ->select('Descricao', 'Preco', 'TipoServico', 'Codigo')
    ->where('Descricao', 'like', 'propina ' . $curso->curso . '%')
    ->where('cacuaco', $aluno_cacuaco)
    ->where('codigo_ano_lectivo', $ano_lectivo)
    ->first();


    return $propina;
  }

  public function confirmacao($codigo_matricula) // ultima confirmacao anterior ao ano corrente
  {

    $anoCorrente = $this->anoAtualPrincipal->index();
    $confirmacao = DB::table('tb_confirmacoes')
      ->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', '=', 'tb_confirmacoes.Codigo_Ano_lectivo')
      ->join('tb_matriculas', 'tb_matriculas.Codigo', '=', 'tb_confirmacoes.Codigo_Matricula')
      ->where('tb_matriculas.Codigo', $codigo_matricula)
      ->where('tb_confirmacoes.Codigo_Ano_lectivo', '!=', $anoCorrente)
      ->select(DB::raw('
    ANY_VALUE(tb_ano_lectivo.Codigo) as ultimoAnoInscritoId,ANY_VALUE(tb_ano_lectivo.Designacao) as ultimoAnoInscritoDesig'))->OrderBy('tb_ano_lectivo.ordem', 'desc')->first();
    //->select(DB::raw('max(tb_ano_lectivo.ordem),
    //dd($confirmacao);
    return $confirmacao;
  }

  public function confirmacaoAPI($codigo_matricula) // ultima confirmacao anterior ao ano corrente
  {

    $anoCorrente = $this->anoAtualPrincipal->index();
    $confirmacao = DB::table('tb_confirmacoes')
      ->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', '=', 'tb_confirmacoes.Codigo_Ano_lectivo')
      ->join('tb_matriculas', 'tb_matriculas.Codigo', '=', 'tb_confirmacoes.Codigo_Matricula')
      ->where('tb_matriculas.Codigo', $codigo_matricula)
      // ->where('tb_confirmacoes.Codigo_Ano_lectivo', '!=',$anoCorrente)
      ->select(DB::raw('
    ANY_VALUE(tb_ano_lectivo.Codigo) as ultimoAnoInscritoId,ANY_VALUE(tb_ano_lectivo.Designacao) as ultimoAnoInscritoDesig'))->OrderBy('tb_ano_lectivo.ordem', 'desc')->first();
    //->select(DB::raw('max(tb_ano_lectivo.ordem),
    //dd($confirmacao);
    return $confirmacao;
  }


  public function dividasNovaVersao($codigo_matricula) // dividas apartir do ano 2020
  {

    $aluno = Matricula::with(['admissao.preinscricao'])->findOrFail($codigo_matricula);

    $user  = $aluno;

    if ($user->admissao->preinscricao->codigo_tipo_candidatura == 1) {
      $matricula1 = DB::table('tb_matriculas')
      ->join('tb_admissao', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
      ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
      ->select('tb_matriculas.*', 'tb_preinscricao.Codigo as codigo_inscricao', 'tb_preinscricao.AlunoCacuaco as aluno_cacuaco', 'tb_preinscricao.desconto as desconto')
      ->where('tb_preinscricao.user_id', $user->admissao->preinscricao->user_id)
      ->first();

      $curso = DB::table('tb_cursos')->join('tb_preinscricao', 'tb_cursos.Codigo', '=', 'tb_preinscricao.Curso_Candidatura')
      ->select('tb_cursos.Designacao as curso', 'tb_cursos.Codigo as codigo_curso')
      ->where('tb_preinscricao.Codigo', $matricula1->codigo_inscricao)
      ->first();



      $anoCorrente = $this->anoAtualPrincipal->index();

      $data['anoAtual'] = $anoCorrente;
      $data['anoCorrente'] = DB::table('tb_ano_lectivo')
        ->where('Codigo', $anoCorrente)
        ->first();


      $maiorAno = DB::table('tb_inscricoes_ano_anterior')->join('tb_matriculas', 'tb_matriculas.Codigo', '=', 'tb_inscricoes_ano_anterior.codigo_matricula')
      ->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', '=', 'tb_inscricoes_ano_anterior.codigo_ano_lectivo')
      ->select(DB::raw('max(tb_ano_lectivo.Designacao) as ano_designacao, ANY_VALUE(tb_ano_lectivo.Codigo) as maior'))
      ->where('tb_inscricoes_ano_anterior.codigo_matricula', $matricula1->Codigo)
      ->where('tb_inscricoes_ano_anterior.status', 1)
      ->first();

      $inscricaoAnosAnteriores = DB::table('tb_inscricoes_ano_anterior')
      ->join('tb_matriculas', 'tb_matriculas.Codigo', '=', 'tb_inscricoes_ano_anterior.codigo_matricula')
      ->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', '=', 'tb_inscricoes_ano_anterior.codigo_ano_lectivo')
      ->select('tb_ano_lectivo.Designacao as ano_designacao', 'tb_inscricoes_ano_anterior.codigo_ano_lectivo as ano_lectivo')
      ->where('tb_inscricoes_ano_anterior.codigo_matricula', $matricula1->Codigo) //->where('tb_inscricoes_ano_anterior.codigo_ano_lectivo', $maiorAno->maior)
      ->get();

      $arrayAnos = json_decode($inscricaoAnosAnteriores, true);

      $collection = collect([]);

      if ($maiorAno->maior) {
        $anoLectivoBolsa = DB::table('tb_ano_lectivo')
          ->where('Codigo', $maiorAno->maior)
          ->first();

        $bolseiro = DB::table('tb_bolseiro_siiuma')->where('tb_bolseiro_siiuma.codigo_matricula', $matricula1->Codigo)->where('tb_bolseiro_siiuma.ano', $anoLectivoBolsa->Designacao)
        ->select('*')->first();
      }

      $diplomado = DB::table('tb_matriculas')->where('estado_matricula', 'diplomado')->where('Codigo', $matricula1->Codigo)->select('*')->first();

      $data['meses'] = DB::table('meses_calendario')->where('id', 7)->get();


      $desconto_bolseiro = 0;
      $total = 0;
      $desconto = 0;
      $valorComDesconto = 0;
      $desconto_preinscricao = 0;
      $taxa_desconto = 0;
      $bolsa = '';



      foreach ($arrayAnos as $key => $ano) {

        $bolseiro = DB::table('tb_bolseiro_siiuma')->where('tb_bolseiro_siiuma.codigo_matricula', $matricula1->Codigo)
        ->where('tb_bolseiro_siiuma.ano', $ano['ano_designacao'])
        ->select('*')
        ->first();
        // $bolseiro = DB::table('tb_bolseiro_siiuma')->where('tb_bolseiro_siiuma.codigo_matricula', $matricula1->Codigo)->where('tb_bolseiro_siiuma.ano', 2012)->select('*')->first();
        $mesesPagos = DB::table('tb_pagamentos')
        ->join('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', '=', 'tb_pagamentos.Codigo')
        ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', '=', 'tb_pagamentosi.Codigo_Servico')
          ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_pagamentos.Codigo_PreInscricao')
          ->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', 'tb_pagamentos.AnoLectivo')
          ->select('tb_pagamentosi.Mes as mes', 'tb_pagamentosi.Valor_Pago as valor', 'tb_ano_lectivo.Designacao as ano', 'tb_pagamentos.estado as estado_pagamento', 'tb_pagamentosi.mes_id as codigo_mes')
          ->where('tb_preinscricao.Codigo', $matricula1->codigo_inscricao)
          ->where('tb_pagamentosi.Ano', $ano['ano_designacao'])
          ->where('tb_tipo_servicos.TipoServico', 'Mensal')
          ->where('tb_pagamentos.estado', 1)
          ->distinct('tb_pagamentosi.Mes')
          ->get();
        //->where('tb_pagamentos.estado',1)

        $mesesIds = $mesesPagos->pluck('codigo_mes');
        $array = json_decode($mesesIds, true);

        $ano_lectivo = DB::table('tb_ano_lectivo')
          ->where('Codigo', $ano['ano_lectivo'])->select('Designacao', 'Codigo')
          ->first();

        $propina = DB::table('tb_tipo_servicos')->select('Descricao', 'Preco', 'TipoServico', 'Codigo')->where('Descricao', 'like', 'propina ' . $curso->curso . '%')->where('cacuaco', $matricula1->aluno_cacuaco)->where('codigo_ano_lectivo', $ano['ano_lectivo'])->first();

        if ($ano_lectivo->Codigo != $data['anoAtual'] && sizeof($inscricaoAnosAnteriores) > 0 && $propina) {

          $ano_lectivo = DB::table('tb_ano_lectivo')
            ->where('Codigo', $ano['ano_lectivo'])->select('Designacao')
            ->first();

          if (!$bolseiro || ($bolseiro && $bolseiro->desconto != 100)) {

            if (!$diplomado) {

              $mesesIsentos = $this->getPrestacoesAnosAnterioresPorAnoLectivo($ano['ano_lectivo'], $user->admissao->preinscricao->user_id);
              //$arrayIsentos=json_decode($mesesIsentos->pluck('codigo'),true);

              $mesesNaoPagos = DB::table('propina_por_curso')
                ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', '=', 'propina_por_curso.codigo_servico')
                ->join('meses', 'meses.codigo', 'propina_por_curso.mes_id')
                ->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', 'tb_tipo_servicos.codigo_ano_lectivo')
                ->select(DB::raw('tb_tipo_servicos.Descricao as servico,meses.mes as mes_propina,meses.codigo as codigo_mes,tb_ano_lectivo.Designacao as ano,
              tb_ano_lectivo.Codigo as codigo_anoLectivo,propina_por_curso.codigo_servico as codigo_propina,((tb_tipo_servicos.Preco*0.1)+tb_tipo_servicos.Preco) as total,
              propina_por_curso.codigo_servico,tb_tipo_servicos.Preco as valor,tb_tipo_servicos.Preco*0.1 as multa'))
                ->where('tb_tipo_servicos.Codigo', $propina->Codigo)
                ->where('tb_tipo_servicos.cacuaco', $matricula1->aluno_cacuaco)
                ->where('tb_tipo_servicos.codigo_ano_lectivo', $ano['ano_lectivo'])
                ->whereNotIn('propina_por_curso.mes_id', $array)
                ->whereNotIn('propina_por_curso.mes_id', $mesesIsentos)
                ->distinct('meses.mes')->get();

              $arrayNP = json_decode($mesesNaoPagos, true);

              foreach ($arrayNP as $key => $mes) {


                if ($bolseiro && $bolseiro->desconto != 100 && $bolseiro->desconto != 0) {
                  $taxa_desconto = $bolseiro->desconto;
                  $bolsa = $bolseiro->instituicao;
                  $desconto_bolseiro = $mes['valor'] * ($bolseiro->desconto / 100);

                  $desconto = $desconto_bolseiro;

                  $valorComDesconto = $mes['valor'] - $desconto;

                  $mes['multa'] = $valorComDesconto * 0.1;

                  $total = $valorComDesconto + $mes['multa'];
                } elseif ($matricula1 && $matricula1->desconto > 0) {
                  $taxa_desconto = $matricula1->desconto;
                  $desconto_preinscricao = $mes['valor'] * ($matricula1->desconto / 100);

                  $desconto = $desconto_preinscricao;

                  $valorComDesconto = $mes['valor'] - $desconto;

                  $mes['multa'] = $valorComDesconto * 0.1;

                  $total = $valorComDesconto + $mes['multa'];
                } else {

                  $desconto = 0;
                  $total = $mes['total'];
                }
                $desconto_finalista = $this->pegar_finalista($mes['codigo_anoLectivo'], $codigo_matricula);

                //dd($confirmacao);
                $collection->push(['codGradeCurricular' => '', 'codFacturaOutrosServicos' => '', 'valor' => $mes['valor'], 'multa' => $mes['multa'], 'total' => $total, 'servico' => $mes['servico'], 'mes_propina' => $mes['mes_propina'], 'mes_temp_id' => null, 'n_prestacao' => $mes['codigo_mes'], 'ano_lectivo' => $mes['ano'], 'taxa_multa' => 10, 'taxa_desconto' => $taxa_desconto, 'bolsa' => $bolsa, 'codigo_propina' => $mes['codigo_propina'], 'codigo_anoLectivo' => $mes['codigo_anoLectivo'], 'desconto' => $desconto]);
              }
            } //FIM IF DO DIPLOMADO

          }

          //

        }
      }

      //Novas dívidas
      $dividas = collect([]);
      //$anoCorrente=$this->anoAtualPrincipal->index();
      $aluno = $this->alunoRepository->dadosAlunoPorMatricula($codigo_matricula);

      // se teve confirmado e pagou outubro tem divida
      $confirmacao = $this->confirmacao($codigo_matricula);
      $diplomado = DB::table('tb_matriculas')->where('estado_matricula', 'diplomado')->where('Codigo', $codigo_matricula)->select('*')->first();

      // && sizeof($mesesPagos)>0
      $pagamentoOutubro = $this->pagouOutubro($aluno->codigo_inscricao);

      //dd($pagamentoOutubro);
      //dd($pagamentoOutubro);
      if (!$diplomado) {
        if (($confirmacao && $confirmacao->ultimoAnoInscritoId == 1 && $pagamentoOutubro) || ($confirmacao && $confirmacao->ultimoAnoInscritoId != 1 && (!(int)($confirmacao->ultimoAnoInscritoDesig <= 2019)))) {
          $mesesPagos = $this->mesesPagosPorAnoPropina($confirmacao->ultimoAnoInscritoId, $aluno->codigo_inscricao);
          $mesesNaoPagos = $this->getPrestacoesPorAnoLectivo($confirmacao->ultimoAnoInscritoId, $mesesPagos->pluck('codigo_mes'), $codigo_matricula);
          $propina = $this->propinaAluno($aluno->codigo_inscricao, $aluno->AlunoCacuaco, $confirmacao->ultimoAnoInscritoId);

          $bolseiro = $this->bolsaService->Bolsa($codigo_matricula, $confirmacao->ultimoAnoInscritoId);

          /*$bolseiro = DB::table('tb_bolseiros')->join('tb_tipo_bolsas','tb_tipo_bolsas.codigo','tb_bolseiros.codigo_tipo_bolsa')->where('tb_bolseiros.codigo_matricula', $codigo_matricula)->where('tb_bolseiros.codigo_anoLectivo', $confirmacao->ultimoAnoInscritoId)->where('status',0)->select('*','tb_tipo_bolsas.designacao as tipo_bolsa')->first();*/
          //bolsa status 0- bolsa ativa e status 1 bolsa desativa
          $taxaMultaMeses = $this->mesesPagarPropina->mesesPagar(date('Y-m-d'), 1, $mes = 0, $confirmacao->ultimoAnoInscritoId, $codigo_matricula);
          $arrayMesesNPagos = json_decode($mesesNaoPagos, true);
          $anoLectivo = DB::table('tb_ano_lectivo')->where('Codigo', $confirmacao->ultimoAnoInscritoId)->first();

          if ($propina && (!$bolseiro || ($bolseiro && $bolseiro->desconto != 100))) {
            foreach ($arrayMesesNPagos as $key => $mes) {

              $desconto_finalista = $this->pegar_finalista($confirmacao->ultimoAnoInscritoId, $codigo_matricula);

              //dd($confirmacao);

              $desconto_bolseiro = 0;
              $total = 0;
              $desconto = 0;
              $valorComDesconto = 0;
              $desconto_preinscricao = 0;
              $multa = 0;
              $taxa_desconto = 0;
              $bolsa = '';
              $mesNPago = $taxaMultaMeses->where('codigo', $mes['id'])->first();


              if ($mesNPago) {
                $semestre = $mes['semestre'];
                if ($aluno->codigo_tipo_candidatura != 1) {
                  $semestre = $mes['semestre_posgraduacao'];
                }

                if ($bolseiro && $bolseiro->desconto != 100 && $bolseiro->desconto != 0) {

                  $bolsaSemestre =  $this->bolsaService->prestacoesPorBolsaSemestreParaDivida($confirmacao->ultimoAnoInscritoId, $mes['id'], $semestre);

                  $taxa_desconto = $bolseiro->desconto;

                  $bolsa = $bolseiro->tipo_bolsa;
                  $desconto_bolseiro = $propina->Preco * ($bolseiro->desconto / 100);
                  if ($bolsaSemestre && $anoLectivo->ordem >= 17) {
                    $taxa_desconto = $bolsaSemestre['desconto'];
                    $desconto_bolseiro = $propina->Preco * ($bolsaSemestre['desconto'] / 100);
                  }
                  $desconto = $desconto_bolseiro;


                  $valorComDesconto = $propina->Preco - $desconto;

                  $multa = $valorComDesconto * ($mesNPago['taxa'] / 100);

                  $total = $valorComDesconto + $multa;
                } elseif ($aluno && $aluno->desconto > 0) {
                  $taxa_desconto = $aluno->desconto;
                  $desconto_preinscricao = $propina->Preco * ($aluno->desconto / 100);

                  $desconto = $desconto_preinscricao;

                  $valorComDesconto = $propina->Preco - $desconto;

                  $multa = $valorComDesconto * ($mesNPago['taxa'] / 100);
                  $total = $valorComDesconto + $multa;
                } else {

                  $desconto = 0;
                  $taxa_desconto = 0;
                  if ($desconto_finalista > 0 && $desconto_finalista <= 3) {
                    $desconto = $propina->Preco * 0.5;
                    $taxa_desconto = 50;
                  }
                  $multa = ($propina->Preco - $desconto) * ($mesNPago['taxa'] / 100);
                  $total = ($propina->Preco - $desconto) + $multa;
                }

                $dividas->push(['codGradeCurricular' => '', 'codFacturaOutrosServicos' => '', 'valor' => $propina->Preco, 'multa' => $multa, 'total' => $total, 'servico' => $propina->Descricao, 'mes_propina' => $mesNPago['mes'], 'mes_temp_id' => $mesNPago['codigo'], 'n_prestacao' => $mesNPago['prestacao'], 'ano_lectivo' => $confirmacao->ultimoAnoInscritoDesig, 'taxa_multa' => $mesNPago['taxa'], 'taxa_desconto' => $taxa_desconto, 'bolsa' => $bolsa, 'codigo_propina' => $propina->Codigo, 'codigo_anoLectivo' => $confirmacao->ultimoAnoInscritoId, 'desconto' => $desconto]);
              }
            }
          }
        }
      }


      $collection1 = $collection->concat($dividas);
      //dd($dividas);
      return $collection1;
    } else {

      if ($user->codigo_tipo_candidatura == 2) {
        $ano_lectivo_ciclo = $this->anoAtualPrincipal->cicloMestrado()->Codigo;
      } else {
        $ano_lectivo_ciclo = $this->anoAtualPrincipal->cicloDoutoramento()->Codigo;
      }

      $matricula1 = DB::table('tb_matriculas')
        ->join('tb_admissao', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')

        ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
        //->select('tb_admissao.pre_incricao as admitido')
        ->select('tb_matriculas.*', 'tb_preinscricao.Codigo as codigo_inscricao', 'tb_preinscricao.AlunoCacuaco as aluno_cacuaco', 'tb_preinscricao.desconto as desconto')
        ->where('tb_preinscricao.user_id', $user->user_id)->first();

      $curso = DB::table('tb_cursos')->join('tb_preinscricao', 'tb_cursos.Codigo', '=', 'tb_preinscricao.Curso_Candidatura')
      ->select('tb_cursos.Designacao as curso', 'tb_cursos.Codigo as codigo_curso')
      ->where('tb_preinscricao.Codigo', $matricula1->codigo_inscricao)
      ->first();

      $anoCorrente = $this->anoAtualPrincipal->index();

      $maiorAno = DB::table('tb_inscricoes_ano_anterior')
      ->join('tb_matriculas', 'tb_matriculas.Codigo', '=', 'tb_inscricoes_ano_anterior.codigo_matricula')
      ->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', '=', 'tb_inscricoes_ano_anterior.codigo_ano_lectivo')
      ->select(DB::raw('max(tb_ano_lectivo.Designacao) as ano_designacao, ANY_VALUE(tb_ano_lectivo.Codigo) as maior'))
      ->where('tb_inscricoes_ano_anterior.codigo_matricula', $matricula1->Codigo)
      ->where('tb_inscricoes_ano_anterior.status', 1)
      ->first();

      $collection = collect([]);

      if ($maiorAno->maior) {
        $anoLectivoBolsa = DB::table('tb_ano_lectivo')
          ->where('Codigo', $maiorAno->maior)
          ->first();
      }

      $diplomado = DB::table('tb_matriculas')
      ->where('estado_matricula', 'diplomado')
      ->where('Codigo', $matricula1->Codigo)
      ->select('*')
      ->first();


      $desconto_bolseiro = 0;
      $total = 0;
      $desconto = 0;
      $valorComDesconto = 0;
      $desconto_preinscricao = 0;
      $taxa_desconto = 0;
      $bolsa = '';

      if ($user->codigo_tipo_candidatura == 2) {
        $meses = DB::table('mes_temp')->where('activo_posgraduacao', 1)->select('id')->limit(24)->get();
      } else {
        $meses = DB::table('mes_temp')->where('activo_posgraduacao', 1)->select('id')->get();
      }


      $mesesCiclo = $meses->pluck('id');

      $ano_lectivo = DB::table('tb_ano_lectivo')->where('Codigo', $user->anoLectivo)->select('Designacao', 'Codigo', 'ordem')->first();

      $ano['ano_lectivo'] = $ano_lectivo->Codigo;

      $anoCorrente = $this->anoAtualPrincipal->index();

      $anoActual =  DB::table('tb_ano_lectivo')->where('Codigo', $anoCorrente)->select('Designacao', 'Codigo', 'ordem')->first();


      if ($ano_lectivo->ordem >= 15) {


        $mesesPagos = DB::table('factura')
          ->join('factura_items', 'factura_items.CodigoFactura', 'factura.Codigo')
          ->join('tb_pagamentos', 'tb_pagamentos.codigo_factura', 'factura.Codigo')
          ->join('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', 'tb_pagamentos.Codigo')
          ->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', 'tb_pagamentos.AnoLectivo')
          ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', '=', 'tb_pagamentosi.Codigo_Servico')
          ->select(
            'tb_pagamentosi.Mes as mes',
            'tb_pagamentosi.Valor_Pago as valor',
            'tb_ano_lectivo.Designacao as ano',
            'tb_pagamentos.estado as estado_pagamento',
            'tb_pagamentosi.mes_temp_id as codigo_mes'
          )
          ->where('factura.CodigoMatricula', $matricula1->Codigo)->where('tb_pagamentos.estado', 1)
          ->where('tb_tipo_servicos.TipoServico', 'Mensal')->distinct('tb_pagamentosi.mes_temp_id')->get();
      } else {

        $mesesPagos = DB::table('tb_pagamentos')
          ->join('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', '=', 'tb_pagamentos.Codigo')
          ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', '=', 'tb_pagamentosi.Codigo_Servico')
          ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_pagamentos.Codigo_PreInscricao')
          ->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', 'tb_pagamentos.AnoLectivo')
          ->select(
            'tb_pagamentosi.Mes as mes',
            'tb_pagamentosi.Valor_Pago as valor',
            'tb_ano_lectivo.Designacao as ano',
            'tb_pagamentos.estado as estado_pagamento',
            'tb_pagamentosi.mes_temp_id as codigo_mes'
          )
          ->where('tb_preinscricao.Codigo', $matricula1->codigo_inscricao)
          ->where('tb_tipo_servicos.TipoServico', 'Mensal')
          ->where('tb_pagamentos.estado', 1)->distinct('tb_pagamentosi.Mes')->get();
      }



      $mesesIds = $mesesPagos->pluck('codigo_mes');

      $array = json_decode($mesesIds, true);

      $propina = DB::table('tb_tipo_servicos')->select('Descricao', 'Preco', 'TipoServico', 'Codigo')
        ->where('Descricao', 'like', 'propina ' . $curso->curso . '%')
        ->where('cacuaco', $matricula1->aluno_cacuaco)->where('codigo_ano_lectivo', $ano_lectivo_ciclo)->first();

      $bolseiro = $this->bolsaService->bolsaPosGraduacao($codigo_matricula, $ano_lectivo_ciclo);

      if ($propina && (!$bolseiro || ($bolseiro && $bolseiro->desconto != 100))) {
        // if ($propina) {

        $isencao = DB::table('tb_isencoes')->where('mes_temp_id', '!=', null)->where('codigo_matricula', $matricula1->Codigo)
          ->where('estado_isensao', 'Activo')
          ->where('codigo_anoLectivo', '>=', $ano_lectivo->Codigo)->select('tb_isencoes.mes_temp_id as mes_id')->get();

        $mesesIsentos = $isencao->pluck('mes_id');

        $collectionMDadosPagamento = collect([]);

        $dadosPagamento = DB::table('tb_tipo_servicos')
          ->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', 'tb_tipo_servicos.codigo_ano_lectivo')
          ->select(DB::raw('tb_tipo_servicos.Descricao as servico, tb_ano_lectivo.Designacao as ano,
            tb_ano_lectivo.Codigo as codigo_anoLectivo,tb_tipo_servicos.Codigo as codigo_propina,((tb_tipo_servicos.Preco*0.1)+tb_tipo_servicos.Preco) as total,
            tb_tipo_servicos.Codigo as codigo_servico,tb_tipo_servicos.Preco as valor,tb_tipo_servicos.Preco*0.1 as multa'))
          ->where('tb_tipo_servicos.Codigo', $propina->Codigo)
          ->where('tb_tipo_servicos.cacuaco', $matricula1->aluno_cacuaco)
          ->where('tb_tipo_servicos.codigo_ano_lectivo', $ano_lectivo_ciclo)
          ->first();

        $naoPagos = DB::table('mes_temp')->whereIn('id', $mesesCiclo)->whereNotIn('id', $array)->whereNotIn('id', $mesesIsentos)->select('designacao as mes_propina', 'id as codigo_mes')->get();

        $arrayMesesNaoPago = json_decode($naoPagos, true);

        foreach ($arrayMesesNaoPago as $key => $mes) {
          $collectionMDadosPagamento->push([
            'valor' => $dadosPagamento->valor,
            'multa' => $dadosPagamento->multa,
            'total' => $dadosPagamento->total,
            'multa' => $dadosPagamento->multa,
            'servico' => $dadosPagamento->servico,
            'mes_propina' => $mes['mes_propina'],
            'codigo_mes' => $mes['codigo_mes'],
            'codigo_propina' => $dadosPagamento->codigo_propina
          ]);
        }
        $mesesNaoPagos = $collectionMDadosPagamento;

        $arrayNP = json_decode($mesesNaoPagos, true);

        foreach ($arrayNP as $key => $mes) {


          if ($bolseiro && $bolseiro->desconto != 100 && $bolseiro->desconto != 0) {

            $taxa_desconto = $bolseiro->desconto;

            $bolsa = $bolseiro->tipo_bolsa;

            $desconto_bolseiro = $mes['valor'] * ($bolseiro->desconto / 100);

            $desconto = $desconto_bolseiro;

            $valorComDesconto = $mes['valor'] - $desconto;

            $mes['multa'] = $valorComDesconto * 0.1;

            $total = $valorComDesconto + $mes['multa'];
          } else if ($matricula1 && $matricula1->desconto > 0) {
            $taxa_desconto = $matricula1->desconto;
            $desconto_preinscricao = $mes['valor'] * ($matricula1->desconto / 100);

            $desconto = $desconto_preinscricao;

            $valorComDesconto = $mes['valor'] - $desconto;

            $mes['multa'] = $valorComDesconto * 0.1;

            $total = $valorComDesconto + $mes['multa'];
          } else {

            $desconto = 0.0;
            $total = $mes['total'];
          }

          //dd($confirmacao);
          $collection->push(['codGradeCurricular' => '', 'codFacturaOutrosServicos' => '', 'valor' => $mes['valor'], 'multa' => $mes['multa'], 'total' => $total, 'servico' => $mes['servico'], 'mes_propina' => $mes['mes_propina'], 'mes_temp_id' => null, 'n_prestacao' => $mes['codigo_mes'], 'ano_lectivo' => $anoActual->Designacao, 'taxa_multa' => 10, 'taxa_desconto' => $taxa_desconto, 'bolsa' => $bolsa, 'codigo_propina' => $mes['codigo_propina'], 'codigo_anoLectivo' => $anoActual->Codigo, 'desconto' => $desconto]);
        }
      }
      return $collection;
    }
  }

  public function pegar_finalista($ano_lectivo, $codigo_matricula)
  {

    $ultimo_ano_lecivo = $this->anoLectivoService->getUltimoAnoLectivoInscrito($codigo_matricula)->Codigo;
    $id = auth()->user()->id;
    $collection = collect([]);


    $cadeirasRestantes = 0;
    $aluno = DB::table('tb_matriculas')
      ->join('tb_admissao', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
      ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')->where('tb_matriculas.Codigo', $codigo_matricula)->select('tb_matriculas.Codigo as matricula', 'tb_matriculas.Codigo_Curso as curso_matricula', 'tb_preinscricao.Curso_Candidatura as curso_preinscricao')->first();


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
      ->where('tb_grade_curricular.status', 1)
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
      ->where('tb_grade_curricular.status', 1)
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
      ->where('tb_grade_curricular.status', 1)
      ->where('tb_grade_curricular_aluno.codigo_matricula', $aluno->matricula)
      ->distinct('disciplina')->get()->count();

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
        ->where('tb_grade_curricular.status', 1)
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
      ->where('tb_grade_curricular.status', 1)
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
        $cadeirasRestantes = $cadeirasRestantes + $cadeirasEliminadaAnoCorrente;
      }
    }

    return $cadeirasRestantes;
  }

  public function pegar_finalistaAPI($codigo_matricula, $ano_lectivo)
  {
    // $ultimo_ano_lecivo = $this->anoLectivoService->getUltimoAnoLectivoInscrito()->Codigo;

    $ano_letivo_designacao = DB::table('tb_confirmacoes')
      ->leftJoin('tb_grade_curricular_aluno', 'tb_grade_curricular_aluno.codigo_confirmacao', 'tb_confirmacoes.Codigo')
      ->join('tb_ano_lectivo', 'tb_confirmacoes.Codigo_Ano_lectivo', '=', 'tb_ano_lectivo.Codigo')
      ->select('tb_ano_lectivo.Designacao')
      ->where('tb_confirmacoes.Codigo_Matricula', $codigo_matricula)
      // ->where('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', '!=',4)
      // ->where('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', '!=', 5)
      ->orderBy('tb_confirmacoes.Codigo_Ano_lectivo', 'DESC')
      ->first();
    if ($ano_letivo_designacao == null) {
      $ano_letivo_designacao = DB::table('tb_inscricoes_ano_anterior')
        ->join('tb_ano_lectivo', 'tb_inscricoes_ano_anterior.codigo_ano_lectivo', '=', 'tb_ano_lectivo.Codigo')
        ->select('tb_ano_lectivo.Designacao')
        ->where('tb_inscricoes_ano_anterior.codigo_matricula', $codigo_matricula)
        ->orderBy('tb_inscricoes_ano_anterior.codigo_ano_lectivo', 'DESC')
        ->first();
    }



    $ultimo_ano_lecivo = DB::table('tb_ano_lectivo')->select('*')->where('Designacao', $ano_letivo_designacao->Designacao)->orderBy('Designacao', 'DESC')->first();

    $ultimo_ano_lecivo = $ultimo_ano_lecivo->Codigo;

    $collection = collect([]);


    $cadeirasRestantes = 0;

    $aluno = DB::table('tb_matriculas')
      ->join('tb_admissao', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
      ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
      ->where('tb_matriculas.Codigo', $codigo_matricula)
      ->select(
        'tb_matriculas.Codigo as matricula',
        'tb_matriculas.Codigo_Curso as curso_matricula',
        'tb_preinscricao.Curso_Candidatura as curso_preinscricao'
      )->first();




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
      ->where('tb_grade_curricular.status', 1)
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
      ->where('tb_grade_curricular.status', 1)
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
      ->where('tb_grade_curricular.status', 1)
      ->where('tb_grade_curricular_aluno.codigo_matricula', $aluno->matricula)
      ->distinct('disciplina')->get()->count();

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
        ->where('tb_grade_curricular.status', 1)
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
      ->where('tb_grade_curricular.status', 1)
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
        $cadeirasRestantes = $cadeirasRestantes + $cadeirasEliminadaAnoCorrente;
      }
    }

    return $cadeirasRestantes;
  }

  public function DividasAntigas($codigo_matricula) // anterior a 2020
  {
    $aluno = Matricula::with(['admissao.preinscricao'])->findOrFail($codigo_matricula);

    $user  = $aluno;

    $matricula = DB::table('tb_matriculas')
      ->join('tb_admissao', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
      ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
      ->select('tb_matriculas.*', 'tb_preinscricao.Codigo as codigo_inscricao', 'tb_preinscricao.AlunoCacuaco as aluno_cacuaco', 'tb_preinscricao.desconto as desconto')
      ->where('tb_preinscricao.user_id', $user->admissao->preinscricao->user_id)
      ->first();

    $curso = DB::table('tb_cursos')->join('tb_preinscricao', 'tb_cursos.Codigo', '=', 'tb_preinscricao.Curso_Candidatura')
    ->select('tb_cursos.Designacao as curso', 'tb_cursos.Codigo as codigo_curso')
    ->where('tb_preinscricao.Codigo', $matricula->codigo_inscricao)
    ->first();

    $anoCorrente = $this->anoAtualPrincipal->index();

    $data['anoAtual'] = $anoCorrente;
    $data['anoCorrente'] = DB::table('tb_ano_lectivo')
      ->where('Codigo', $anoCorrente)
      ->first();
    $maiorAno = DB::table('tb_inscricoes_ano_anterior')->join('tb_matriculas', 'tb_matriculas.Codigo', '=', 'tb_inscricoes_ano_anterior.codigo_matricula')
    ->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', '=', 'tb_inscricoes_ano_anterior.codigo_ano_lectivo')
      ->select(DB::raw('max(tb_ano_lectivo.Designacao) as ano_designacao, ANY_VALUE(tb_ano_lectivo.Codigo) as maior'))
      ->where('tb_inscricoes_ano_anterior.codigo_matricula', $matricula->Codigo)
      ->where('tb_inscricoes_ano_anterior.status', 1)
      ->first();
    $inscricaoAnosAnteriores = DB::table('tb_inscricoes_ano_anterior')->join('tb_matriculas', 'tb_matriculas.Codigo', '=', 'tb_inscricoes_ano_anterior.codigo_matricula')->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', '=', 'tb_inscricoes_ano_anterior.codigo_ano_lectivo')
      ->select('tb_ano_lectivo.Designacao as ano_designacao', 'tb_inscricoes_ano_anterior.codigo_ano_lectivo as ano_lectivo')->where('tb_inscricoes_ano_anterior.codigo_matricula', $matricula->Codigo)->where('tb_inscricoes_ano_anterior.codigo_ano_lectivo', $maiorAno->maior)
      ->get();
    $arrayAnos = json_decode($inscricaoAnosAnteriores, true);
    $collection = collect([]);
    if ($maiorAno->maior) {
      $anoLectivoBolsa = DB::table('tb_ano_lectivo')
        ->where('Codigo', $maiorAno->maior)
        ->first();


      $bolseiro = DB::table('tb_bolseiro_siiuma')->where('tb_bolseiro_siiuma.codigo_matricula', $matricula->Codigo)->where('tb_bolseiro_siiuma.ano', $anoLectivoBolsa->Designacao)->select('*')->first();
    }

    $diplomado = DB::table('tb_matriculas')->where('estado_matricula', 'diplomado')->where('Codigo', $matricula->Codigo)->select('*')->first();
    $data['meses'] = DB::table('meses_calendario')->where('id', 7)->get();


    $desconto_bolseiro = 0;
    $total = 0;
    $desconto = 0;
    $valorComDesconto = 0;
    $desconto_preinscricao = 0;
    $taxa_desconto = 0;
    $bolsa = '';
    foreach ($arrayAnos as $key => $ano) {

      $mesesPagos = DB::table('tb_pagamentos')->join('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', '=', 'tb_pagamentos.Codigo')->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', '=', 'tb_pagamentosi.Codigo_Servico')
        ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_pagamentos.Codigo_PreInscricao')->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', 'tb_pagamentos.AnoLectivo')->select('tb_pagamentosi.Mes as mes', 'tb_pagamentosi.Valor_Pago as valor', 'tb_ano_lectivo.Designacao as ano', 'tb_pagamentos.estado as estado_pagamento', 'tb_pagamentosi.mes_id as codigo_mes')->where('tb_preinscricao.Codigo', $matricula->codigo_inscricao)->where('tb_pagamentosi.Ano', $ano['ano_designacao'])
        ->where('tb_tipo_servicos.TipoServico', 'Mensal')->where('tb_pagamentos.estado', 1)->distinct('tb_pagamentosi.Mes')->get();
      //->where('tb_pagamentos.estado',1)
      $mesesIds = $mesesPagos->pluck('codigo_mes');
      $array = json_decode($mesesIds, true);

      $ano_lectivo = DB::table('tb_ano_lectivo')
        ->where('Codigo', $ano['ano_lectivo'])->select('Designacao', 'Codigo')
        ->first();

      $propina = DB::table('tb_tipo_servicos')->select('Descricao', 'Preco', 'TipoServico', 'Codigo')->where('Descricao', 'like', 'propina ' . $curso->curso . '%')->where('cacuaco', $matricula->aluno_cacuaco)->where('codigo_ano_lectivo', $ano['ano_lectivo'])->first();

      if ($ano_lectivo->Codigo != $data['anoAtual'] && sizeof($inscricaoAnosAnteriores) > 0 && $propina) {

        $ano_lectivo = DB::table('tb_ano_lectivo')
          ->where('Codigo', $ano['ano_lectivo'])->select('Designacao')
          ->first();

        if (!$bolseiro || ($bolseiro && $bolseiro->desconto != 100)) {

          if (!$diplomado) {

            $mesesIsentos = $this->getPrestacoesAnosAnterioresPorAnoLectivo($ano['ano_lectivo'], $user->admissao->preinscricao->user_id);

            $mesesNaoPagos = DB::table('propina_por_curso')->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', '=', 'propina_por_curso.codigo_servico')->join('meses', 'meses.codigo', 'propina_por_curso.mes_id')->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', 'tb_tipo_servicos.codigo_ano_lectivo')->select(DB::raw('tb_tipo_servicos.Descricao as servico,meses.mes as mes_propina,meses.codigo as codigo_mes,tb_ano_lectivo.Designacao as ano,tb_ano_lectivo.Codigo as codigo_anoLectivo,propina_por_curso.codigo_servico as codigo_propina,((tb_tipo_servicos.Preco*0.1)+tb_tipo_servicos.Preco) as total, propina_por_curso.codigo_servico,tb_tipo_servicos.Preco as valor,tb_tipo_servicos.Preco*0.1 as multa'))->where('tb_tipo_servicos.Codigo', $propina->Codigo)->where('tb_tipo_servicos.cacuaco', $matricula->aluno_cacuaco)->where('tb_tipo_servicos.codigo_ano_lectivo', $ano['ano_lectivo'])->whereNotIn('propina_por_curso.mes_id', $array)->whereNotIn('propina_por_curso.mes_id', $mesesIsentos)->distinct('meses.mes')->get();

            $arrayNP = json_decode($mesesNaoPagos, true);

            foreach ($arrayNP as $key => $mes) {


              if ($bolseiro && $bolseiro->desconto != 100 && $bolseiro->desconto != 0) {
                $taxa_desconto = $bolseiro->desconto;
                $bolsa = $bolseiro->instituicao;
                $desconto_bolseiro = $mes['valor'] * ($bolseiro->desconto / 100);

                $desconto = $desconto_bolseiro;

                $valorComDesconto = $mes['valor'] - $desconto;

                $mes['multa'] = $valorComDesconto * 0.1;

                $total = $valorComDesconto + $mes['multa'];
              } elseif ($matricula && $matricula->desconto > 0) {
                $taxa_desconto = $matricula->desconto;
                $desconto_preinscricao = $mes['valor'] * ($matricula->desconto / 100);

                $desconto = $desconto_preinscricao;

                $valorComDesconto = $mes['valor'] - $desconto;

                $mes['multa'] = $valorComDesconto * 0.1;

                $total = $valorComDesconto + $mes['multa'];
              } else {

                $desconto = 0;
                $total = $mes['total'];
              }

              $collection->push(['valor' => $mes['valor'], 'multa' => $mes['multa'], 'total' => $total, 'servico' => $mes['servico'], 'mes_propina' => $mes['mes_propina'], 'mes_temp_id' => null, 'n_prestacao' => $mes['codigo_mes'], 'ano_lectivo' => $mes['ano'], 'taxa_multa' => 10, 'taxa_desconto' => $taxa_desconto, 'bolsa' => $bolsa, 'codigo_propina' => $mes['codigo_propina'], 'codigo_anoLectivo' => $mes['codigo_anoLectivo'], 'desconto' => $desconto]);
            }
          } //FIM IF DO DIPLOMADO

        }
      }
    }

    return $collection;
  }

  public function DividasAntigasAlunoSemUser($codigo_matricula) // anterior a 2020
  {

    $matricula = DB::table('tb_matriculas')
      ->join('tb_admissao', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')

      ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
      //->select('tb_admissao.pre_incricao as admitido')
      ->select('tb_preinscricao.user_id', 'tb_matriculas.*', 'tb_preinscricao.Codigo as codigo_inscricao', 'tb_preinscricao.AlunoCacuaco as aluno_cacuaco', 'tb_preinscricao.desconto as desconto')
      ->where('tb_matriculas.Codigo', $codigo_matricula)->first();

    $curso = DB::table('tb_cursos')->join('tb_preinscricao', 'tb_cursos.Codigo', '=', 'tb_preinscricao.Curso_Candidatura')->select('tb_cursos.Designacao as curso', 'tb_cursos.Codigo as codigo_curso')->where('tb_preinscricao.Codigo', $matricula->codigo_inscricao)->first();

    $anoCorrente = $this->anoAtualPrincipal->index();
    $data['anoAtual'] = $anoCorrente;
    $data['anoCorrente'] = DB::table('tb_ano_lectivo')
      ->where('Codigo', $anoCorrente)
      ->first();



    $maiorAno = DB::table('tb_inscricoes_ano_anterior')->join('tb_matriculas', 'tb_matriculas.Codigo', '=', 'tb_inscricoes_ano_anterior.codigo_matricula')->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', '=', 'tb_inscricoes_ano_anterior.codigo_ano_lectivo')
      ->select(DB::raw('max(tb_ano_lectivo.Designacao) as ano_designacao, ANY_VALUE(tb_ano_lectivo.Codigo) as maior'))->where('tb_inscricoes_ano_anterior.codigo_matricula', $matricula->Codigo)->where('tb_inscricoes_ano_anterior.status', 1)
      ->first();


    $inscricaoAnosAnteriores = DB::table('tb_inscricoes_ano_anterior')->join('tb_matriculas', 'tb_matriculas.Codigo', '=', 'tb_inscricoes_ano_anterior.codigo_matricula')->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', '=', 'tb_inscricoes_ano_anterior.codigo_ano_lectivo')
      ->select('tb_ano_lectivo.Designacao as ano_designacao', 'tb_inscricoes_ano_anterior.codigo_ano_lectivo as ano_lectivo')->where('tb_inscricoes_ano_anterior.codigo_matricula', $matricula->Codigo)->where('tb_inscricoes_ano_anterior.codigo_ano_lectivo', $maiorAno->maior)
      ->get();


    $arrayAnos = json_decode($inscricaoAnosAnteriores, true);


    $collection = collect([]);
    if ($maiorAno->maior) {
      $anoLectivoBolsa = DB::table('tb_ano_lectivo')
        ->where('Codigo', $maiorAno->maior)
        ->first();


      $bolseiro = DB::table('tb_bolseiro_siiuma')->where('tb_bolseiro_siiuma.codigo_matricula', $matricula->Codigo)->where('tb_bolseiro_siiuma.ano', $anoLectivoBolsa->Designacao)->select('*')->first();
    }

    $diplomado = DB::table('tb_matriculas')->where('estado_matricula', 'diplomado')->where('Codigo', $matricula->Codigo)->select('*')->first();


    $data['meses'] = DB::table('meses_calendario')->where('id', 7)->get();


    $desconto_bolseiro = 0;
    $total = 0;
    $desconto = 0;
    $valorComDesconto = 0;
    $desconto_preinscricao = 0;
    $taxa_desconto = 0;
    $bolsa = '';
    foreach ($arrayAnos as $key => $ano) {

      $mesesPagos = DB::table('tb_pagamentos')->join('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', '=', 'tb_pagamentos.Codigo')->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', '=', 'tb_pagamentosi.Codigo_Servico')
        ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_pagamentos.Codigo_PreInscricao')->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', 'tb_pagamentos.AnoLectivo')->select('tb_pagamentosi.Mes as mes', 'tb_pagamentosi.Valor_Pago as valor', 'tb_ano_lectivo.Designacao as ano', 'tb_pagamentos.estado as estado_pagamento', 'tb_pagamentosi.mes_id as codigo_mes')->where('tb_preinscricao.Codigo', $matricula->codigo_inscricao)->where('tb_pagamentosi.Ano', $ano['ano_designacao'])
        ->where('tb_tipo_servicos.TipoServico', 'Mensal')->where('tb_pagamentos.estado', 1)->distinct('tb_pagamentosi.Mes')->get();
      //->where('tb_pagamentos.estado',1)
      $mesesIds = $mesesPagos->pluck('codigo_mes');
      $array = json_decode($mesesIds, true);

      $ano_lectivo = DB::table('tb_ano_lectivo')
        ->where('Codigo', $ano['ano_lectivo'])->select('Designacao', 'Codigo')
        ->first();

      $propina = DB::table('tb_tipo_servicos')->select('Descricao', 'Preco', 'TipoServico', 'Codigo')->where('Descricao', 'like', 'propina ' . $curso->curso . '%')->where('cacuaco', $matricula->aluno_cacuaco)->where('codigo_ano_lectivo', $ano['ano_lectivo'])->first();

      if ($ano_lectivo->Codigo != $data['anoAtual'] && sizeof($inscricaoAnosAnteriores) > 0 && $propina) {

        $ano_lectivo = DB::table('tb_ano_lectivo')
          ->where('Codigo', $ano['ano_lectivo'])->select('Designacao')
          ->first();

        if (!$bolseiro || ($bolseiro && $bolseiro->desconto != 100)) {

          if (!$diplomado) {

            $mesesIsentos = $this->getPrestacoesAnosAnterioresPorAnoLectivo($ano['ano_lectivo'], $matricula->user_id);
            //$arrayIsentos=json_decode($mesesIsentos->pluck('codigo'),true);

            $mesesNaoPagos = DB::table('propina_por_curso')->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', '=', 'propina_por_curso.codigo_servico')->join('meses', 'meses.codigo', 'propina_por_curso.mes_id')->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', 'tb_tipo_servicos.codigo_ano_lectivo')->select(DB::raw('tb_tipo_servicos.Descricao as servico,meses.mes as mes_propina,meses.codigo as codigo_mes,tb_ano_lectivo.Designacao as ano,tb_ano_lectivo.Codigo as codigo_anoLectivo,propina_por_curso.codigo_servico as codigo_propina,((tb_tipo_servicos.Preco*0.1)+tb_tipo_servicos.Preco) as total, propina_por_curso.codigo_servico,tb_tipo_servicos.Preco as valor,tb_tipo_servicos.Preco*0.1 as multa'))->where('tb_tipo_servicos.Codigo', $propina->Codigo)->where('tb_tipo_servicos.cacuaco', $matricula->aluno_cacuaco)->where('tb_tipo_servicos.codigo_ano_lectivo', $ano['ano_lectivo'])->whereNotIn('propina_por_curso.mes_id', $array)->whereNotIn('propina_por_curso.mes_id', $mesesIsentos)->distinct('meses.mes')->get();

            $arrayNP = json_decode($mesesNaoPagos, true);

            foreach ($arrayNP as $key => $mes) {


              if ($bolseiro && $bolseiro->desconto != 100 && $bolseiro->desconto != 0) {
                $taxa_desconto = $bolseiro->desconto;
                $bolsa = $bolseiro->instituicao;
                $desconto_bolseiro = $mes['valor'] * ($bolseiro->desconto / 100);

                $desconto = $desconto_bolseiro;

                $valorComDesconto = $mes['valor'] - $desconto;

                $mes['multa'] = $valorComDesconto * 0.1;

                $total = $valorComDesconto + $mes['multa'];
              } elseif ($matricula && $matricula->desconto > 0) {
                $taxa_desconto = $matricula->desconto;
                $desconto_preinscricao = $mes['valor'] * ($matricula->desconto / 100);

                $desconto = $desconto_preinscricao;

                $valorComDesconto = $mes['valor'] - $desconto;

                $mes['multa'] = $valorComDesconto * 0.1;

                $total = $valorComDesconto + $mes['multa'];
              } else {

                $desconto = 0;
                $total = $mes['total'];
              }

              $collection->push(['valor' => $mes['valor'], 'multa' => $mes['multa'], 'total' => $total, 'servico' => $mes['servico'], 'mes_propina' => $mes['mes_propina'], 'mes_temp_id' => null, 'n_prestacao' => $mes['codigo_mes'], 'ano_lectivo' => $mes['ano'], 'taxa_multa' => 10, 'taxa_desconto' => $taxa_desconto, 'bolsa' => $bolsa, 'codigo_propina' => $mes['codigo_propina'], 'codigo_anoLectivo' => $mes['codigo_anoLectivo'], 'desconto' => $desconto]);
            }
          } //FIM IF DO DIPLOMADO

        }
      }
    }

    return $collection;
  }

  public function dividaOutrosServicos($codigo_matricula)
  {

    $dividas = collect([]);
    $aluno = $this->alunoRepository->dadosAlunoPorMatricula($codigo_matricula);
    // se teve confirmado e pagou outubro tem divida
    $confirmacao = $this->confirmacao($codigo_matricula);
    $data = [];
    // && sizeof($mesesPagos)>0
    $pagamentoOutubro = $this->pagouOutubro($aluno->codigo_inscricao);

    if (($confirmacao && $confirmacao->ultimoAnoInscritoId == 1 && $pagamentoOutubro) || ($confirmacao && $confirmacao->ultimoAnoInscritoId != 1 && (!(int)($confirmacao->ultimoAnoInscritoDesig <= 2019)))) {

      $faturasPagas = DB::table('factura')
        ->join('tb_matriculas', 'tb_matriculas.Codigo', 'factura.CodigoMatricula')
        ->join('inscricao_avaliacoes', 'inscricao_avaliacoes.codigo_factura', 'factura.Codigo')
        ->where('inscricao_avaliacoes.codigo_ano_lectivo', $confirmacao->ultimoAnoInscritoId)
        ->where('factura.corrente', 1)
        ->where('tb_matriculas.Codigo', $codigo_matricula)
        ->whereIn('inscricao_avaliacoes.codigo_factura', DB::table('tb_pagamentos')->select('tb_pagamentos.codigo_factura'))
        ->select('inscricao_avaliacoes.codigo_factura')
        ->distinct('codigo_factura')->get();


      $array = $faturasPagas->pluck('codigo_factura');


      $outrosServicos = DB::table('inscricao_avaliacoes')
        ->join('factura', 'factura.Codigo', '=', 'inscricao_avaliacoes.codigo_factura')
        ->join('factura_items', 'factura_items.CodigoFactura', '=', 'factura.Codigo')
        ->join('tb_grade_curricular_aluno_avaliacoes', 'tb_grade_curricular_aluno_avaliacoes.grade_curricular_aluno', '=', 'inscricao_avaliacoes.codigo_grade_aluno')
        ->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', 'factura.ano_lectivo')
        ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', 'factura_items.CodigoProduto')
        ->leftJoin('tb_grade_curricular', 'tb_grade_curricular.Codigo', 'tb_tipo_servicos.codigo_grade_currilular')
        ->leftJoin('tb_disciplinas', 'tb_disciplinas.Codigo', 'tb_grade_curricular.Codigo_Disciplina')
        ->where('inscricao_avaliacoes.codigo_matricula', $codigo_matricula)
        ->where('inscricao_avaliacoes.codigo_ano_lectivo', $confirmacao->ultimoAnoInscritoId)
        // ->where('inscricao_avaliacoes.codigo_tipo_avaliacao', 7)
        // ->where('tb_grade_curricular_aluno_avaliacoes.tipo_avaliacao', 7)
        ->where('inscricao_avaliacoes.estado', '!=', 'anulado')
        ->where('factura.estado', '!=', 1)
        ->where('factura.corrente', 1)
        ->whereNotIn('factura.Codigo', $array)
        ->select('factura.ValorAPagar as apagar', 'tb_grade_curricular.Codigo as codGradeCurricular', 'factura.Codigo as codFacturaOutrosServicos', 'factura_items.preco as valor', 'factura_items.Multa as multa', 'factura_items.descontoProduto as descontoProduto', 'factura_items.Total as total', 'tb_disciplinas.Designacao as servico', 'factura.ano_lectivo as cod_ano_lectivo', 'tb_ano_lectivo.Designacao as ano_lectivo', 'inscricao_avaliacoes.codigo_tipo_avaliacao as tipo_avaliacao', 'tb_tipo_servicos.Codigo as cod_servico')
        ->distinct('codGradeCurricular')
        ->get();
      $apagar = 0;
      $arrayRecurso = json_decode($outrosServicos, true);
      foreach ($arrayRecurso as $key => $value) {
        $apagar += $value['apagar'];
        $servico = "";
        if ($value['tipo_avaliacao'] == 7) {

          $servico = 'Rec. ' . $value['servico'];
        } elseif ($value['tipo_avaliacao'] == 22) {
          $servico = 'Melhoria. ' . $value['servico'];
        } elseif ($value['tipo_avaliacao'] == 11) {
          $servico = 'Exame Especial. ' . $value['servico'];
        }
        if ($value['codGradeCurricular']) {
          $dividas->push(['codGradeCurricular' => $value['codGradeCurricular'], 'codFacturaOutrosServicos' => $value['codFacturaOutrosServicos'], 'valor' => $value['valor'], 'multa' => $value['multa'], 'total' => $value['total'], 'servico' => $servico, 'mes_propina' => '', 'mes_temp_id' => '', 'n_prestacao' => '', 'ano_lectivo' => $value['ano_lectivo'], 'taxa_multa' => '', 'taxa_desconto' => '', 'bolsa' => '', 'codigo_propina' => $value['cod_servico'], 'codigo_anoLectivo' => $value['cod_ano_lectivo'], 'desconto' => $value['descontoProduto']]);
        }
      }

    }
    $data = $dividas;



    return $data;
  }

  public function  dividasFacturasAnoCorrente()
  {
    $data['faturas'] = DB::table('factura')->join('tb_matriculas', 'tb_matriculas.Codigo', 'factura.CodigoMatricula')
      ->join('tb_admissao', 'tb_admissao.codigo', 'tb_matriculas.Codigo_Aluno')
      ->join('tb_preinscricao', 'tb_preinscricao.Codigo', 'tb_admissao.pre_incricao')
      ->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', 'factura.ano_lectivo')
      ->select(
        'factura.Codigo as codigo_factura',
        'factura.DataFactura as DataFactura',
        'factura.TotalPreco as total',
        'factura.ValorAPagar as apagar',
        'factura.Referencia as referencia',
        'factura.Desconto as desconto',
        'factura.TotalMulta as TotalMulta',
        'tb_ano_lectivo.Designacao as ano',
        'factura.codigo_descricao as codigo_descricao',
        'factura.ValorEntregue as ValorEntregue',
        'factura.estado as estado_factura'
      )
      ->where('tb_preinscricao.user_id', auth()->user()->id)
      ->where('factura.corrente', 1)
      ->where('factura.estado', '!=', 3)
      //->whereColumn('factura.ValorEntregue', '<', 'factura.ValorAPagar')
      ->where('factura.ano_lectivo', $this->codigoAnoCorrente)
      ->distinct('factura.Codigo')
      ->orderBy('factura.Codigo')->get();

    $data['total_divida'] = 0;
    if ($data['faturas']->sum('ValorEntregue') <= $data['faturas']->sum('apagar')) {

      $data['total_divida'] = (($data['faturas']->sum('apagar')) - ($data['faturas']->sum('ValorEntregue')));
    }

    return $data['total_divida'];
  }
  public function confirmacaoAnoCorrente($codigo_matricula) // confirmacao para o ano corrente
  {
    // so precisava do ano. Fiz assim para nao alterar muito o codigo
    $confirmacao = DB::table('tb_confirmacoes')
      ->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', '=', 'tb_confirmacoes.Codigo_Ano_lectivo')
      ->join('tb_matriculas', 'tb_matriculas.Codigo', '=', 'tb_confirmacoes.Codigo_Matricula')
      ->where('tb_matriculas.Codigo', $codigo_matricula)
      ->where('tb_confirmacoes.Codigo_Ano_lectivo',  $this->codigoAnoCorrente)
      ->select('tb_ano_lectivo.Codigo as ano_lectivo_id', 'tb_ano_lectivo.Designacao as ano_lectivo_designacao')->first();


    return $confirmacao;
  }

  public function  dividasPropinaAnoCorrente($codigo_matricula) // numero de matricula para casos de api
  {
    $dividas = collect([]);
    //$anoCorrente=$this->anoAtualPrincipal->index();
    $aluno = $this->alunoRepository->dadosAlunoPorMatricula($codigo_matricula);
    // se teve confirmado e pagou outubro tem divida
    $confirmacao = $this->confirmacaoAnoCorrente($codigo_matricula); // confirmacao para o ano corrente
    $diplomado = DB::table('tb_matriculas')->where('estado_matricula', 'diplomado')->where('Codigo', $codigo_matricula)->select('*')->first();

    if (!$diplomado) {
      if ($confirmacao) {
        $mesesPagos = $this->mesesPagosPorAnoPropina($confirmacao->ano_lectivo_id, $aluno->codigo_inscricao);
        $mesesNaoPagos = $this->getPrestacoesPorAnoLectivo($confirmacao->ano_lectivo_id, $mesesPagos->pluck('codigo_mes'), $codigo_matricula);
        $propina = $this->propinaAluno($aluno->codigo_inscricao, $aluno->AlunoCacuaco, $confirmacao->ano_lectivo_id); //  propina do curso do aluno
        // $bolseiro = $this->bolsaService->Bolsa($codigo_matricula, $confirmacao->ano_lectivo_id);
        $bolseiro = $this->bolsaService->BolsaPorSemestre1($codigo_matricula, $confirmacao->ano_lectivo_id, 1);
        $bolseiro2 = $this->bolsaService->BolsaPorSemestre2($codigo_matricula, $confirmacao->ano_lectivo_id, 2);

        //bolsa status 0- bolsa ativa e status 1 bolsa desativa
        $taxaMultaMeses = $this->mesesPagarPropina->mesesPagar(date('Y-m-d'), 1, $mes = 0, $confirmacao->ano_lectivo_id, $codigo_matricula);
        $arrayMesesNPagos = json_decode($mesesNaoPagos, true);

        $desconto_finalista = $this->pegar_finalista($confirmacao->ano_lectivo_id, $codigo_matricula);

        if ($propina && (!$bolseiro || ($bolseiro && ($bolseiro->desconto > 0 && $bolseiro->desconto < 100)))) {

          $mes_temp = DB::table('mes_temp')->where('semestre', 1)->where('activo', 1)->get();

          $mes_temp_primeiro_semestre = json_decode($mes_temp, true);

          foreach ($arrayMesesNPagos as $key => $mes) {

            foreach ($mes_temp_primeiro_semestre as $key => $mes_semestre) {

              if ($mes['id'] == $mes_semestre['id']) {

                $desconto_bolseiro = 0;
                $total = 0;
                $desconto = 0;
                $valorComDesconto = 0;
                $desconto_preinscricao = 0;
                $multa = 0;
                $taxa_desconto = 0;
                $bolsa = '';
                $mesNPago = $taxaMultaMeses->where('codigo', $mes['id'])->first();
                //dd($confirmacao);
                if ($mesNPago && $mesNPago['taxa'] > 0) {

                  if ($bolseiro && ($bolseiro->desconto != 100 || $bolseiro->desconto != 0)) {
                    $taxa_desconto = $bolseiro->desconto;
                    $bolsa = $bolseiro->tipo_bolsa;
                    $desconto_bolseiro = $propina->Preco * ($bolseiro->desconto / 100);


                    $desconto = $desconto_bolseiro;

                    $valorComDesconto = $propina->Preco - $desconto;

                    $multa = $valorComDesconto * ($mesNPago['taxa'] / 100);

                    $total = $valorComDesconto + $multa;
                  } elseif ($aluno && $aluno->desconto > 0) {
                    $taxa_desconto = $aluno->desconto;
                    $desconto_preinscricao = $propina->Preco * ($aluno->desconto / 100);

                    $desconto = $desconto_preinscricao;

                    $valorComDesconto = $propina->Preco - $desconto;

                    $multa = $valorComDesconto * ($mesNPago['taxa'] / 100);
                    $total = $valorComDesconto + $multa;
                  } else {

                    $desconto = 0;
                    $taxa_desconto = 0;

                    if ($desconto_finalista > 0 && $desconto_finalista <= 3) {
                      $desconto = $propina->Preco * 0.5;
                      $taxa_desconto = 50;
                    }
                    // $multa = $propina->Preco * ($mesNPago['taxa'] / 100);
                    // $total = $propina->Preco + $multa;

                    $multa = ($propina->Preco - $desconto) * ($mesNPago['taxa'] / 100);
                    $total = ($propina->Preco - $desconto) + $multa;
                  }

                  $dividas->push(['valor' => $propina->Preco, 'multa' => $multa, 'total' => $total, 'servico' => $propina->Descricao, 'mes_propina' => $mesNPago['mes'], 'mes_temp_id' => $mesNPago['codigo'], 'n_prestacao' => $mesNPago['prestacao'], 'ano_lectivo' => $confirmacao->ano_lectivo_designacao, 'taxa_multa' => $mesNPago['taxa'], 'taxa_desconto' => $taxa_desconto, 'bolsa' => $bolsa, 'codigo_propina' => $propina->Codigo, 'codigo_anoLectivo' => $confirmacao->ano_lectivo_id, 'desconto' => $desconto]);
                }
              }
            }
          }
        }


        if ($propina && (!$bolseiro2 || ($bolseiro2 && ($bolseiro2->desconto > 0 && $bolseiro2->desconto < 100)))) {

          $mes_temp = DB::table('mes_temp')->where('semestre', 2)->where('activo', 1)->get();

          $mes_temp_primeiro_semestre = json_decode($mes_temp, true);

          foreach ($arrayMesesNPagos as $key => $mes) {

            foreach ($mes_temp_primeiro_semestre as $key => $mes_semestre) {

              if ($mes['id'] == $mes_semestre['id']) {

                $desconto_bolseiro = 0;
                $total = 0;
                $desconto = 0;
                $valorComDesconto = 0;
                $desconto_preinscricao = 0;
                $multa = 0;
                $taxa_desconto = 0;
                $bolsa = '';
                $mesNPago = $taxaMultaMeses->where('codigo', $mes['id'])->first();
                //dd($confirmacao);
                if ($mesNPago && $mesNPago['taxa'] > 0) {

                  if ($bolseiro2 && ($bolseiro2->desconto != 100 || $bolseiro2->desconto != 0)) {
                    $taxa_desconto = $bolseiro2->desconto;
                    $bolsa = $bolseiro2->tipo_bolsa;
                    $desconto_bolseiro = $propina->Preco * ($bolseiro2->desconto / 100);


                    $desconto = $desconto_bolseiro;

                    $valorComDesconto = $propina->Preco - $desconto;

                    $multa = $valorComDesconto * ($mesNPago['taxa'] / 100);

                    $total = $valorComDesconto + $multa;
                  } elseif ($aluno && $aluno->desconto > 0) {
                    $taxa_desconto = $aluno->desconto;
                    $desconto_preinscricao = $propina->Preco * ($aluno->desconto / 100);

                    $desconto = $desconto_preinscricao;

                    $valorComDesconto = $propina->Preco - $desconto;

                    $multa = $valorComDesconto * ($mesNPago['taxa'] / 100);
                    $total = $valorComDesconto + $multa;
                  } else {

                    $desconto = 0;
                    $taxa_desconto = 0;

                    if ($desconto_finalista > 0 && $desconto_finalista <= 3) {
                      $desconto = $propina->Preco * 0.5;
                      $taxa_desconto = 50;
                    }
                    // $multa = $propina->Preco * ($mesNPago['taxa'] / 100);
                    // $total = $propina->Preco + $multa;

                    $multa = ($propina->Preco - $desconto) * ($mesNPago['taxa'] / 100);
                    $total = ($propina->Preco - $desconto) + $multa;
                  }

                  //$total=(double)$total;

                  $dividas->push(['valor' => $propina->Preco, 'multa' => $multa, 'total' => $total, 'servico' => $propina->Descricao, 'mes_propina' => $mesNPago['mes'], 'mes_temp_id' => $mesNPago['codigo'], 'n_prestacao' => $mesNPago['prestacao'], 'ano_lectivo' => $confirmacao->ano_lectivo_designacao, 'taxa_multa' => $mesNPago['taxa'], 'taxa_desconto' => $taxa_desconto, 'bolsa' => $bolsa, 'codigo_propina' => $propina->Codigo, 'codigo_anoLectivo' => $confirmacao->ano_lectivo_id, 'desconto' => $desconto]);
                }
              }
            }
          }
        }
      }
    }
    //dd(3);
    return $dividas;
  }

  public function  dividasPropinaAnoCorrenteBackup($codigo_matricula) // numero de matricula para casos de api
  {
    $dividas = collect([]);
    //$anoCorrente=$this->anoAtualPrincipal->index();
    $aluno = $this->alunoRepository->dadosAlunoPorMatricula($codigo_matricula);
    // se teve confirmado e pagou outubro tem divida
    $confirmacao = $this->confirmacaoAnoCorrente($codigo_matricula); // confirmacao para o ano corrente
    $diplomado = DB::table('tb_matriculas')->where('estado_matricula', 'diplomado')->where('Codigo', $codigo_matricula)->select('*')->first();

    if (!$diplomado) {
      if ($confirmacao) {
        $mesesPagos = $this->mesesPagosPorAnoPropina($confirmacao->ano_lectivo_id, $aluno->codigo_inscricao);
        $mesesNaoPagos = $this->getPrestacoesPorAnoLectivo($confirmacao->ano_lectivo_id, $mesesPagos->pluck('codigo_mes'),$codigo_matricula);
        $propina = $this->propinaAluno($aluno->codigo_inscricao, $aluno->AlunoCacuaco, $confirmacao->ano_lectivo_id); //  propina do curso do aluno
        // $bolseiro = $this->bolsaService->Bolsa($codigo_matricula, $confirmacao->ano_lectivo_id);
        $bolseiro = $this->bolsaService->BolsaPorSemestre1($codigo_matricula, $confirmacao->ano_lectivo_id, 1);
        $bolseiro2 = $this->bolsaService->BolsaPorSemestre2($codigo_matricula, $confirmacao->ano_lectivo_id, 2);

        //bolsa status 0- bolsa ativa e status 1 bolsa desativa
        $taxaMultaMeses = $this->mesesPagarPropina->mesesPagar(date('Y-m-d'), 1, $mes = 0, $confirmacao->ano_lectivo_id, $codigo_matricula);
        $arrayMesesNPagos = json_decode($mesesNaoPagos, true);



        if ($propina && (!$bolseiro || ($bolseiro && ($bolseiro->desconto > 0 && $bolseiro->desconto < 100)))) {
          foreach ($arrayMesesNPagos as $key => $mes) {
            $desconto_bolseiro = 0;
            $total = 0;
            $desconto = 0;
            $valorComDesconto = 0;
            $desconto_preinscricao = 0;
            $multa = 0;
            $taxa_desconto = 0;
            $bolsa = '';
            $mesNPago = $taxaMultaMeses->where('codigo', $mes['id'])->first();
            //dd($confirmacao);
            if ($mesNPago && $mesNPago['taxa'] > 0) {


              if ($bolseiro && $bolseiro->desconto != 100 || $bolseiro->desconto != 0) {
                $taxa_desconto = $bolseiro->desconto;
                $bolsa = $bolseiro->tipo_bolsa;
                $desconto_bolseiro = $propina->Preco * ($bolseiro->desconto / 100);


                $desconto = $desconto_bolseiro;

                $valorComDesconto = $propina->Preco - $desconto;

                $multa = $valorComDesconto * ($mesNPago['taxa'] / 100);

                $total = $valorComDesconto + $multa;
              } elseif ($aluno && $aluno->desconto > 0) {
                $taxa_desconto = $aluno->desconto;
                $desconto_preinscricao = $propina->Preco * ($aluno->desconto / 100);

                $desconto = $desconto_preinscricao;

                $valorComDesconto = $propina->Preco - $desconto;

                $multa = $valorComDesconto * ($mesNPago['taxa'] / 100);
                $total = $valorComDesconto + $multa;
              } else {

                $desconto = 0;
                $taxa_desconto = 0;
                $multa = $propina->Preco * ($mesNPago['taxa'] / 100);
                $total = $propina->Preco + $multa;
              }

              //$total=(double)$total;

              $dividas->push(['valor' => $propina->Preco, 'multa' => $multa, 'total' => $total, 'servico' => $propina->Descricao, 'mes_propina' => $mesNPago['mes'], 'mes_temp_id' => $mesNPago['codigo'], 'n_prestacao' => $mesNPago['prestacao'], 'ano_lectivo' => $confirmacao->ano_lectivo_designacao, 'taxa_multa' => $mesNPago['taxa'], 'taxa_desconto' => $taxa_desconto, 'bolsa' => $bolsa, 'codigo_propina' => $propina->Codigo, 'codigo_anoLectivo' => $confirmacao->ano_lectivo_id, 'desconto' => $desconto]);
            }
          }
        }
      }
    }
    //dd(3);
    return $dividas;
  }

  public function DividasTodosAnos($numero_matricula, $tipo)
  {
    $aluno = $this->alunoRepository->dadosAlunoPorMatricula($numero_matricula);
    $pagamentoOutubro = $this->pagouOutubro($aluno->codigo_inscricao); //outubro de 2020-2021

    $dividasNovaVersao = $this->dividasNovaVersao($numero_matricula);
    // dd("Passou");

    $outrosServicos = $this->dividaOutrosServicos($numero_matricula);
    $dividaAntiga = $this->DividasAntigas($numero_matricula); // anterior ao ano 2020

    $dividas = $dividasNovaVersao->mergeRecursive($outrosServicos);

    $dividasRecurso = $this->dividaOutrosServicos($numero_matricula);

    if ($tipo == 2) {
      $dividas = sizeof($dividasNovaVersao->mergeRecursive($outrosServicos));
      if ($pagamentoOutubro) { // regra orientada. Se o estudante ja pagou outubro de 2020, o sistema não deve lhe cobrar divida de anos anteriores a 2020
        $dividas = sizeof($dividasNovaVersao);
      }
      if (sizeOf($outrosServicos) > 0) {
        $dividas = $dividas + sizeOf($outrosServicos);
      }
    }

    return $dividas;
  }

  public function cobrarFaturaNegociacao($codigo_matricula)
  {


    $negociacao = DB::table('factura')->join('tb_matriculas', 'tb_matriculas.Codigo', 'factura.CodigoMatricula')->join('tb_admissao', 'tb_admissao.Codigo', '=', 'tb_matriculas.Codigo_Aluno')->join('tb_pagamentos', 'tb_pagamentos.codigo_factura', '=', 'factura.Codigo')->join('negociacao_dividas', 'negociacao_dividas.codigo_fatura', '=', 'factura.Codigo')->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')->where('factura.CodigoMatricula', $codigo_matricula)->where('factura.codigo_descricao', 5)->where('factura.estado', '!=', 3)->where('factura.estado', 2)->where('negociacao_dividas.estado', 1)->select('negociacao_dividas.id_mes_final as mes_final', 'negociacao_dividas.created_at as data_negociacao', 'factura.ValorEntregue as ValorEntregue', 'factura.ValorAPagar as ValorAPagar', 'factura.Codigo as codigo_fatura', 'negociacao_dividas.estado as estado_negociacao', 'negociacao_dividas.id_mes_inicial as mes_inicial')->first();


    if ($negociacao && $negociacao->estado_negociacao == 1) {
      $horaPrazo = date('H:i:s', strtotime($negociacao->data_negociacao));
      $diaPrazo = date('d', strtotime($negociacao->data_negociacao));
      $zero = 0;
      //$dataPrazo=date('Y-m-d',strtotime($negociacao->data_negociacao.'+5 months'));
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

  public function removeData()
  {
  }
}
