<?php

namespace App\Services;

use App\Http\Controllers\ClassesAuxiliares\anoAtual;
use DB;
use App\Repositories\AlunoRepository;
use App\Services\AnoLectivoService;

class BolsaService
{
  public $alunoRepository;
  public $anoAtualPrincipal;
  public $anoLectivoService;
  public function __construct()
  {
    $this->alunoRepository = new AlunoRepository();
    $this->anoAtualPrincipal = new anoAtual();
    $this->anoLectivoService = new AnoLectivoService();
  }

  public function Bolsa($codigo_matricula, $codigo_anoLectivo)
  {

    $anoLectivoSelecionado = DB::table('tb_ano_lectivo')->where('Codigo', $codigo_anoLectivo)->first();
    $condicoes = [];
    $codigo_semestre_ativo = $this->anoLectivoService->semestreActivo();

    if ($anoLectivoSelecionado->ordem >= 17) { // aplicacao da nova abordagem das bolsas apenas para os anos lectivos a partir da ordem 17
      array_push($condicoes, ['tb_bolseiros.semestre', $codigo_semestre_ativo]);
    }

    // estado ativo é 0 e desativado é 1
    $bolseiro = DB::table('tb_bolseiros')->join('tb_tipo_bolsas', 'tb_tipo_bolsas.codigo', 'tb_bolseiros.codigo_tipo_bolsa')
      ->where('tb_bolseiros.codigo_matricula', $codigo_matricula)
      ->where('tb_bolseiros.codigo_anoLectivo', $codigo_anoLectivo)
      ->where($condicoes)
      ->where('status',  0)->select('*', 'tb_tipo_bolsas.designacao as tipo_bolsa')->first(); //Abordagem do ano actual
    return $bolseiro;
  }
  public function BolsaPorSemestre1($codigo_matricula, $codigo_anoLectivo, $semestre_id)
  {

    // estado ativo é 0 e desativado é 1
    $bolseiro = DB::table('tb_bolseiros')->join('tb_tipo_bolsas', 'tb_tipo_bolsas.codigo', 'tb_bolseiros.codigo_tipo_bolsa')
      ->where('tb_bolseiros.codigo_matricula', $codigo_matricula)
      ->where('tb_bolseiros.codigo_anoLectivo', $codigo_anoLectivo)
      ->where('tb_bolseiros.semestre', $semestre_id)
      //  ->where('status', $this->anoAtualPrincipal->index()==$codigo_anoLectivo ? 0 : 1)->select('*','tb_tipo_bolsas.designacao as tipo_bolsa')->first(); //Abordagem do ano 2021-2022 
      ->where('status', 0)->select('*', 'tb_tipo_bolsas.designacao as tipo_bolsa')->first(); //Abordagem do ano Actual


    return $bolseiro;
  }
  public function BolsaPorSemestre2($codigo_matricula, $codigo_anoLectivo, $semestre_id)
  {

    // estado ativo é 0 e desativado é 1
    $bolseiro = DB::table('tb_bolseiros')->join('tb_tipo_bolsas', 'tb_tipo_bolsas.codigo', 'tb_bolseiros.codigo_tipo_bolsa')
      ->where('tb_bolseiros.codigo_matricula', $codigo_matricula)
      ->where('tb_bolseiros.codigo_anoLectivo', $codigo_anoLectivo)
      ->where('tb_bolseiros.semestre', $semestre_id)
      //  ->where('status', $this->anoAtualPrincipal->index()==$codigo_anoLectivo ? 0 : 1)->select('*','tb_tipo_bolsas.designacao as tipo_bolsa')->first(); //Abordagem do ano 2021-2022 
      ->where('status', 0)->select('*', 'tb_tipo_bolsas.designacao as tipo_bolsa')->first(); //Abordagem do ano Actual


    return $bolseiro;
  }

  public function BolsaPrimeiroSemestre($codigo_matricula, $codigo_anoLectivo)
  {

    $codigo_semestre_ativo = $this->anoLectivoService->semestreActivo();
    // estado ativo é 0 e desativado é 1
    $bolseiro = DB::table('tb_bolseiros')->join('tb_tipo_bolsas', 'tb_tipo_bolsas.codigo', 'tb_bolseiros.codigo_tipo_bolsa')
      ->where('tb_bolseiros.codigo_matricula', $codigo_matricula)
      ->where('tb_bolseiros.codigo_anoLectivo', $codigo_anoLectivo)
      ->where('tb_bolseiros.semestre', 1)
      //  ->where('status', $this->anoAtualPrincipal->index()==$codigo_anoLectivo ? 0 : 1)->select('*','tb_tipo_bolsas.designacao as tipo_bolsa')->first(); //Abordagem do ano 2021-2022 
      ->where('status', 0)->select('*', 'tb_tipo_bolsas.designacao as tipo_bolsa')->first(); //Abordagem do ano Actual 

    return $bolseiro;
  }
  public function BolsaPorSemestre($codigo_matricula, $codigo_anoLectivo, $semestre_id)
  {


    // estado ativo é 0 e desativado é 1
    $bolseiro = DB::table('tb_bolseiros')->join('tb_tipo_bolsas', 'tb_tipo_bolsas.codigo', 'tb_bolseiros.codigo_tipo_bolsa')
      ->where('tb_bolseiros.codigo_matricula', $codigo_matricula)
      ->where('tb_bolseiros.codigo_anoLectivo', $codigo_anoLectivo)
      ->where('tb_bolseiros.semestre', $semestre_id)
      //  ->where('status', $this->anoAtualPrincipal->index()==$codigo_anoLectivo ? 0 : 1)->select('*','tb_tipo_bolsas.designacao as tipo_bolsa')->first(); //Abordagem do ano 2021-2022 
      ->where('status', 0)->select('*', 'tb_tipo_bolsas.designacao as tipo_bolsa')->first(); //Abordagem do ano Actual


    return $bolseiro;
  }

  public function bolsaPosGraduacao($codigo_matricula, $codigo_anoLectivo)
  {

    $bolseiro = DB::table('tb_bolseiros')->join('tb_tipo_bolsas', 'tb_tipo_bolsas.codigo', 'tb_bolseiros.codigo_tipo_bolsa')
      ->where('tb_bolseiros.codigo_matricula', $codigo_matricula)
      ->where('tb_bolseiros.codigo_anoLectivo', $codigo_anoLectivo)
      ->where('status', 0)->select('*', 'tb_tipo_bolsas.designacao as tipo_bolsa')->first(); //Abordagem do ano Actual
    return $bolseiro;
  }

  public function BolsaPorSemestreCemPorCento($codigo_matricula, $codigo_anoLectivo, $semestre_id)
  {


    // estado ativo é 0 e desativado é 1
    $bolseiro = DB::table('tb_bolseiros')->join('tb_tipo_bolsas', 'tb_tipo_bolsas.codigo', 'tb_bolseiros.codigo_tipo_bolsa')
      ->where('tb_bolseiros.codigo_matricula', $codigo_matricula)
      ->where('tb_bolseiros.codigo_anoLectivo', $codigo_anoLectivo)
      ->where('tb_bolseiros.semestre', $semestre_id)
      ->whereIn('tb_bolseiros.desconto', [0, 100])
      //  ->where('status', $this->anoAtualPrincipal->index()==$codigo_anoLectivo ? 0 : 1)->select('*','tb_tipo_bolsas.designacao as tipo_bolsa')->first(); //Abordagem do ano 2021-2022 
      ->where('status', 0)->select('*', 'tb_tipo_bolsas.designacao as tipo_bolsa')->first(); //Abordagem do ano Actual


    return $bolseiro;
  }

  public function prestacoesPorBolsaSemestre($codigo_anoLectivo, $user_id)
  {

    $aluno = $this->alunoRepository->dadosAlunoLogado($user_id);

    //$bolsa= $this->Bolsa($aluno->matricula,$codigo_anoLectivo);

    $meses = [];
    $codigo_semestres = [1, 2];
    //if($bolsa){
    //$codigo_semestre= [$bolsa->semestre];
    /*  if($bolsa->semestre==2 && $this->BolsaPrimeiroSemestre($aluno->matricula,$codigo_anoLectivo)){
      $codigo_semestre=[1,2];
    } */
    //}
    if ($aluno->codigo_tipo_candidatura == 1) {

      $meses = DB::table('mes_temp')->where('ano_lectivo', $codigo_anoLectivo)->where('activo', 1)->whereIn('semestre', $codigo_semestres)->get();
    } else {

      $meses = DB::table('mes_temp')->where('ano_lectivo', $codigo_anoLectivo)->where('activo_posgraduacao', 1)->whereIn('semestre_posgraduacao', $codigo_semestres)->get();
    }



    //$array = json_decode($meses,true);
    $collection = collect([]);
    $desconto = 0;
    $codigo_semestre_ativo = $this->anoLectivoService->semestreActivo();

    foreach ($meses as $key => $value) {
      $semestre = $value->semestre;
      if ($aluno->codigo_tipo_candidatura != 1) {
        $semestre = $value->semestre_posgraduacao;
      }
      $bolsa = $this->BolsaPorSemestre($aluno->matricula, $codigo_anoLectivo, $semestre); // se tem bolsa no semestre ativo ou // se tem bolsa no semestre nao ativo
      if ($bolsa) {
        $desconto = $bolsa->desconto;
      } else {
        $desconto = 0;
      }


      $collection->push([
        'codigo' => $value->id,
        'mes' => $value->designacao,
        'desconto' => $desconto
      ]);
    }

    return $collection;
  }

  public function prestacoesPorBolsaSemestreParaDivida($codigo_anoLectivo, $mes_id, $semestre_id, $user_id)
  {

    $aluno = $this->alunoRepository->dadosAlunoLogado($user_id);
    $collection = collect([]);

    if ($aluno->codigo_tipo_candidatura == 1) {

      $mes = DB::table('mes_temp')->where('ano_lectivo', $codigo_anoLectivo)->where('id', $mes_id)->where('activo', 1)->where('semestre', $semestre_id)->first();
    } else {

      $mes = DB::table('mes_temp')->where('ano_lectivo', $codigo_anoLectivo)->where('id', $mes_id)->where('activo_posgraduacao', 1)->where('semestre_posgraduacao', $semestre_id)->first();
    }
    $bolsa = $this->BolsaPorSemestre($aluno->matricula, $codigo_anoLectivo, $semestre_id);


    $result = null;
    if ($mes && $bolsa) {

      $collection->push([
        'codigo' => $mes->id,
        'mes' => $mes->designacao,
        'desconto' => $bolsa->desconto
      ]);

      $result = $collection->first();
    }

    return $result;
  }
  public function prestacoesPorBolsaSemestreParaDividaAPI($codigo_matricula, $codigo_anoLectivo, $mes_id, $semestre_id)
  {

    $aluno = DB::table('tb_matriculas')
      ->join('tb_admissao', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
      ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
      ->where('tb_matriculas.Codigo', $codigo_matricula)
      ->select('tb_matriculas.Codigo as matricula', 'tb_matriculas.Codigo_Curso as curso_matricula', 'tb_matriculas.estado_matricula as estado_matricula', 'tb_preinscricao.Curso_Candidatura as curso_preinscricao', 'tb_preinscricao.Codigo_Turno as turno_id', 'tb_preinscricao.polo_id as polo_id', 'tb_preinscricao.Codigo as codigo_inscricao', 'tb_preinscricao.Nome_Completo', 'tb_preinscricao.codigo_tipo_candidatura as codigo_tipo_candidatura', 'tb_preinscricao.user_id', 'tb_preinscricao.saldo', 'tb_preinscricao.anoLectivo as anoLectivo', 'tb_preinscricao.Codigo_Turno as Codigo_Turno')->first();

    $collection = collect([]);

    if ($aluno->codigo_tipo_candidatura == 1) {

      $mes = DB::table('mes_temp')->where('ano_lectivo', $codigo_anoLectivo)->where('id', $mes_id)->where('activo', 1)->where('semestre', $semestre_id)->first();
    } else {

      $mes = DB::table('mes_temp')->where('ano_lectivo', $codigo_anoLectivo)->where('id', $mes_id)->where('activo_posgraduacao', 1)->where('semestre_posgraduacao', $semestre_id)->first();
    }
    $bolsa = $this->BolsaPorSemestre($aluno->matricula, $codigo_anoLectivo, $semestre_id);


    $result = null;
    if ($mes && $bolsa) {

      $collection->push([
        'codigo' => $mes->id,
        'mes' => $mes->designacao,
        'desconto' => $bolsa->desconto
      ]);

      $result = $collection->first();
    }

    return $result;
  }

  // isencao para os bolseiros
  public function isencaoServicoPorInstituicao($codigo_matricula, $codigo_anoLectivo, $sigla_servico)
  {

    $bolseiro = $this->Bolsa($codigo_matricula, $codigo_anoLectivo);
    $isento = null;
    if ($bolseiro) {
      $isento = DB::table('tb_isencao_instituicao')
        ->where('tb_isencao_instituicao.codigo_instituicao', $bolseiro->codigo_Instituicao)->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', '=', 'tb_isencao_instituicao.codigo_servico')
        ->where('tb_isencao_instituicao.codigo_ano_lectivo', $codigo_anoLectivo)->where('tb_tipo_servicos.codigo_ano_lectivo', $codigo_anoLectivo)->where('tipo_bolsa', $bolseiro->codigo_tipo_bolsa)
        ->where('tb_tipo_servicos.sigla', $sigla_servico)->select('*')->first();
    }
    return $isento;
  }

}
