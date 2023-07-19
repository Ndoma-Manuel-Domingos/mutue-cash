<?php

namespace App\Services;

use App\Candidato;
use App\Factura;
use App\FacturaItens;
use \Carbon\Carbon;
use DB;
use App\Repositories\AlunoRepository;
use App\Http\Controllers\ClassesAuxiliares\anoAtual;
use App\Models\Matricula;
use App\PagamentoItem;
use App\Prestacao;
use App\PrestacaoAntiga;
use App\User;

class PropinaService
{
  public $alunoRepository;
  public $anoCorrente;
  public $servicosService;
  public function __construct()
  {
    $this->alunoRepository = new AlunoRepository();
    $this->anoCorrente = new anoAtual();
    $this->servicosService = new ServicosService();
  }

  public function mesesPagar($data, $tipo, $mes_id, $codigo_anoLectivo, $codigo_matricula = null)
  {
    $aluno = Matricula::with(['admissao.preinscricao'])->findOrFail($codigo_matricula);

    if ($aluno->admissao->preinscricao->codigo_tipo_candidatura == 1) {
      $codigo_anoLectivo = $codigo_anoLectivo;
    } elseif ($aluno->admissao->preinscricao->codigo_tipo_candidatura == 2) {
      $codigo_anoLectivo = $this->anoCorrente->cicloMestrado()->Codigo;
    } else {
      $codigo_anoLectivo = $this->anoCorrente->cicloDoutoramento()->Codigo;
    }
    //dd($aluno);
    if ($tipo == 1) {
      $meses_temp = DB::table('mes_temp')
        ->select('id as id_mes', 'designacao as mes', 'data_limite as data', 'data_final', 'prestacao')
        ->where(function ($q) use ($aluno) {
          if ($aluno->admissao->preinscricao->codigo_tipo_candidatura == 1) {
            $q->where('activo', 1);
          } else {
            $q->where('activo_posgraduacao', 1);
          }
        })->where('ano_lectivo', $codigo_anoLectivo)->where('isencao', 0)->get();
    } elseif ($tipo == 2) {
      $meses_temp = DB::table('mes_temp')
        ->select('id as id_mes', 'designacao as mes', 'data_limite as data', 'data_final', 'prestacao')
        ->where(function ($q) use ($aluno) {
          if ($aluno->admissao->preinscricao->codigo_tipo_candidatura == 1) {
            $q->where('activo', 1);
          } else {
            $q->where('activo_posgraduacao', 1);
          }
        })->where('ano_lectivo', $codigo_anoLectivo)->where('isencao', 0)->where('id', $mes_id)->get();
    }


    $array = json_decode($meses_temp, true);

    $dif = 0;
    //$dataParametro=Carbon::createFromFormat('Y-m-d', $data);

    $mesesApagar = collect([]);
    $taxa = 0;
    $posicao = '';
    foreach ($array as $key => $value) {
      /* $date2 = Carbon::createFromFormat('Y-m-d', $value['data']); 
      $dif=$dataParametro->diffInDays($date2);*/

      $prestacoes_isentas_multa = $this->checkIsencaoMulta($aluno->Codigo, $value['id_mes'], $codigo_anoLectivo);

      if ($data > $value['data']) {
        if ($prestacoes_isentas_multa == false) {
          $taxa = $this->parametroTaxaMulta($data, $value['data'], $value['data_final'], $meses_temp, $key);
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
    /* $difference =$data_limite->diffInMonths($data_actual,false);
    $month = Carbon::now()->month; // 7 */
    //dd($difference);
    $taxa = 0;
    if (date($datalimite) < date($data_banco) && $data_actual->month == $data_limite->month) {

      $parametroMulta = DB::table('tb_parametros_multa')
        ->select('percentagem', 'codigo')->where('codigo', 1)->first();
      // taxa 5
      return $parametroMulta->percentagem;
    } else if ($data_limite->diffInMonths($data_actual, false) <= 1 && $data_actual > $data_limite/*$data_actual->month > $data_limite->month*/) {

      $parametroMulta = DB::table('tb_parametros_multa')
        ->select('percentagem', 'codigo')->where('codigo', 2)->first();
      //$taxa=7;
      return $parametroMulta->percentagem;
    } else if ($data_limite->diffInMonths($data_actual, false) >= 2) {

      $parametroMulta = DB::table('tb_parametros_multa')
        ->select('percentagem', 'codigo')->where('codigo', 3)->first();
      return $parametroMulta->percentagem;
      //$taxa=10;

    }
    return $taxa;
  }


  public function checkIsencao($matricula, $mes_id, $ano_lectivo_id)
  {
    $anoLectivo = DB::table('tb_ano_lectivo')->where('tb_ano_lectivo.Codigo', $ano_lectivo_id)
      ->first();

    $isencoes =  DB::table('tb_isencoes')->where(function ($q) use ($mes_id, $anoLectivo) {
      if ((int)$anoLectivo->Designacao <= 2019 && ($anoLectivo->Designacao != $this->anoCorrente->cicloMestrado()->Designacao) && ($anoLectivo->Designacao != $this->anoCorrente->cicloMestrado()->Designacao)) {
        $q->where('mes_id', $mes_id);
      } else {
        $q->where('mes_temp_id', $mes_id);
      }
    })->where('codigo_matricula', $matricula)->where('estado_isensao', 'Activo')->where('codigo_anoLectivo', $ano_lectivo_id)->get();

    return filled($isencoes);
  }

  public function checkIsencaoMulta($matricula, $mes_id, $ano_lectivo_id)
  {
    $anoLectivo = DB::table('tb_ano_lectivo')->where('tb_ano_lectivo.Codigo', $ano_lectivo_id)
      ->first();

    $isencoes =  DB::table('tb_isencoe_multa')
      ->where('mes_temp_id', $mes_id)
      ->where('codigo_matricula', $matricula)
      ->where('estado_isensao', 'Activo')
      // ->where('codigo_servico', $this->servicosService->servicoDePropinaPorCurso($ano_lectivo_id)->Codigo)
      ->where('codigo_anoLectivo', $ano_lectivo_id)->get();

    return filled($isencoes);
  }
  public function checkIsencaoMultaGlobal($codigo_tipo_candidatura)
  {

    $isencao_multa_global = DB::table('tb_isencao_multa_global')->where('estado_isencao', "Activo")->where('codigo_tipo_candidatura', $codigo_tipo_candidatura)->first();

    return filled($isencao_multa_global);
  }

  //Ndongala Nguinamau, propinas pagas totalmente ou isento
  public function propinasPagasTotalmente($id, $codigo_anoLectivo) //$id=user_id; 
  {

    $ano = $this->anoCorrente->index();

    $data['ano_lectivo'] = DB::table('tb_ano_lectivo')->where('tb_ano_lectivo.Codigo', $ano)
      ->first();
    $matricula = DB::table('tb_matriculas')
      ->join('tb_admissao', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
      ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
      ->select('tb_matriculas.*', 'tb_preinscricao.Codigo as codigo_inscricao', 'tb_preinscricao.AlunoCacuaco as aluno_cacuaco', 'tb_preinscricao.desconto as desconto', 'tb_preinscricao.codigo_curso_pagamento as codigo_curso_pagamento')
      ->where('tb_preinscricao.user_id', $id)
      ->first();

    //Abordagem do Ndongala para o painel
    $facturas = Factura::where('CodigoMatricula', $matricula->Codigo)
      ->where('ano_lectivo', $ano)
      ->whereHas('factura_itens', function ($q) {
        $q->where('mes_temp_id', '!=', null);
      })
      ->orderBy('DataFactura', 'desc')
      ->get();

    $prestacoes = collect([]);
    $prestacoes_por_ano = Prestacao::where('ano_lectivo', $ano)->where(function ($q) {
      if (auth()->user()->preinscricao->codigo_tipo_candidatura == 1) {

        $q->where('activo', 1);
      } else {

        $q->where('activo_posgraduacao', 1);
      }
    })->get();

    foreach ($prestacoes_por_ano as $key => $prestacao) {
      $prestacaoPaga = FacturaItens::whereHas('factura', function ($q) use ($matricula) {
        $q->where('CodigoMatricula', $matricula->Codigo)
          ->where('estado', '!=', 3); //de propinas
      })->where('mes_temp_id', $prestacao->id)->with('factura')->first();
      $prestacao['factura_item'] = $prestacaoPaga;
      //Verificar isenção
      $prestacao['isento'] = $this->checkIsencao($matricula->Codigo, $prestacao->id, $ano);

      $prestacoes->push($prestacao);
    }

    //filtrar apenas pagas na totalidade ou isentos
    $prestacoes_isentas = $prestacoes->where('isento', 1);
    $prestacoes = $prestacoes->whereNotNull('factura_item')->merge($prestacoes_isentas);


    if ((int)$data['ano_lectivo']->Designacao <= 2019) {
      $prestacoes_por_ano = PrestacaoAntiga::orderBy('codigo', 'asc')->get();

      foreach ($prestacoes_por_ano as $key => $prestacao) {


        $prestacaoPaga = PagamentoItem::whereHas('pagamento', function ($q) {
          $q->where('Codigo_PreInscricao', auth()->user()->preinscricao->Codigo)
            ->where('estado', 1);
        })->where('mes_id', $prestacao->codigo)->with('pagamento')->first();
        $prestacao['pagamento_item'] = $prestacaoPaga;
        //Verificar isenção

        $prestacao['isento'] = $this->checkIsencao($matricula->Codigo, $prestacao->codigo, $ano);

        $prestacoes->push($prestacao);
      }
    }

    $data['prestacoes'] = $prestacoes;
    $data['facturas'] = $facturas;


    return $data;
  }

  public function prestacaoPorAnoLectivo($codigo_anoLectivo, $numero_prestacao, $prazo = 1) // funcão generica, sem insencoes
  {

    $condicoes = [];
    if (auth()->user()->preinscricao->codigo_tipo_candidatura == 1) {
      array_push($condicoes, ['activo', 1]);
    } else {

      $condicoes = array_push($condicoes, ['activo_posgraduacao', 1]);
    }


    $mes = DB::table('mes_temp')->where('ano_lectivo', $codigo_anoLectivo)->where('prestacao', $numero_prestacao)->where($condicoes)->first();

    return $mes;
  }
}
