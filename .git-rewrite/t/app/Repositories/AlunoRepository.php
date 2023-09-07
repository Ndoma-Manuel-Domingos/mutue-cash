<?php

namespace App\Repositories;

use App\Http\Controllers\ClassesAuxiliares\anoAtual;
use Auth;
use DB;
use App\Services\AnoLectivoService;

class AlunoRepository
{


  public $anoLectivoService;
  public $anoAtualPrincipal;

  public function __construct()
  {
    $this->anoLectivoService = new AnoLectivoService();
    $this->anoAtualPrincipal = new anoAtual();
  }

  public function dadosAlunoLogado($user_id)
  {

    // $id = Auth::user()->id;

    $aluno = DB::table('tb_matriculas')
      ->join('tb_admissao', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
      ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
      ->where('tb_preinscricao.user_id', $user_id)
      ->select('tb_preinscricao.Codigo as preinscricao','tb_matriculas.Codigo as matricula', 'tb_matriculas.Codigo_Curso as curso_matricula', 'tb_matriculas.estado_matricula as estado_matricula', 'tb_preinscricao.Curso_Candidatura as curso_preinscricao', 'tb_preinscricao.Codigo_Turno as turno_id', 'tb_preinscricao.polo_id as polo_id', 'tb_preinscricao.Codigo as codigo_inscricao', 'tb_preinscricao.Nome_Completo', 'tb_preinscricao.codigo_tipo_candidatura as codigo_tipo_candidatura','tb_preinscricao.user_id','tb_preinscricao.saldo','tb_preinscricao.anoLectivo as anoLectivo', 'tb_preinscricao.Codigo_Turno as Codigo_Turno')->first();

    return $aluno;
  }

  public function alunosMatriculadosPorCurso($candidatos)
  {

    $alunos = DB::table('tb_matriculas')
      ->join('tb_admissao', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
      ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
      ->whereIn('tb_preinscricao.Codigo', $candidatos)
      ->where('tb_matriculas.estado_matricula', '!=', 'diplomado')->get();

    return $alunos;
  }

  //Pega aluno pelo campos do User
  public function getAlunoByDadosUser($numero_documento,$telefone)
  {

    $aluno = DB::table('tb_matriculas')
      ->join('tb_admissao', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
      ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
      ->where('tb_preinscricao.Bilhete_Identidade', $numero_documento)
      ->orWhere('tb_preinscricao.Contactos_Telefonicos', $telefone)
      ->select('tb_matriculas.Codigo as matricula', 'tb_matriculas.Codigo_Curso as curso_matricula', 'tb_matriculas.estado_matricula as estado_matricula', 'tb_preinscricao.Curso_Candidatura as curso_preinscricao', 'tb_preinscricao.Codigo_Turno as turno_id', 'tb_preinscricao.polo_id as polo_id', 'tb_preinscricao.Codigo as codigo_candidato', 'tb_preinscricao.Nome_Completo', 'tb_preinscricao.codigo_tipo_candidatura as codigo_tipo_candidatura','tb_preinscricao.user_id','tb_preinscricao.saldo');

    return $aluno;
  }
  
  //Pega candidato admitido
  public function admissao()
  {

    $admitido = DB::table('tb_admissao')->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
      //->select('tb_admissao.pre_incricao as admitido')
      ->select('tb_admissao.*')
      ->where('tb_preinscricao.user_id', auth()->user() ? auth()->user()->id : null)->first();

    return $admitido;
  }

  public function dadosAlunoPorMatricula($codigo_matricula)
  {

    $aluno = DB::table('tb_matriculas')
      ->join('tb_admissao', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
      ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
      ->select('tb_matriculas.Codigo as matricula', 'tb_matriculas.Codigo_Curso as curso_matricula', 'tb_preinscricao.Curso_Candidatura as curso_preinscricao', 
      'tb_preinscricao.Codigo_Turno as turno_id', 'tb_preinscricao.polo_id as polo_id',
       'tb_preinscricao.Codigo as codigo_inscricao', 'tb_preinscricao.AlunoCacuaco',
        'tb_preinscricao.desconto', 'tb_preinscricao.Nome_Completo'
        ,'tb_preinscricao.Email as email','tb_preinscricao.Morada_Completa as endereco',
        'tb_preinscricao.Contactos_Telefonicos as telefone', 'tb_preinscricao.codigo_tipo_candidatura as codigo_tipo_candidatura')
      ->where('tb_matriculas.Codigo', $codigo_matricula)->first();

    return $aluno;
  }

  public function getAlunoPorPreinscricao($codigo_preinscricao)
  {

    $aluno = DB::table('tb_preinscricao')
      ->select('tb_preinscricao.Nome_Completo','tb_preinscricao.Email as email','tb_preinscricao.Morada_Completa as endereco','tb_preinscricao.Contactos_Telefonicos as telefone')
      ->where('Codigo', $codigo_preinscricao)->first();

    return $aluno;
  }

  public function verificaConfirmacaoNoAnoCorrente($id_user)// patricio
  {
    $aluno = $this->dadosAlunoLogado($id_user);

    $confirmacao_ano_corrente = DB::table('tb_confirmacoes')->where('Codigo_Matricula', $aluno->matricula)->where('Codigo_Ano_lectivo', $this->anoAtualPrincipal->index())->first();
    $codigo_semestreActivo = $this->anoLectivoService->semestreActivo();
    $cadeirasInscritas = DB::table('tb_grade_curricular_aluno')
      ->join('tb_grade_curricular', 'tb_grade_curricular_aluno.codigo_grade_curricular', '=', 'tb_grade_curricular.Codigo')
      ->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')
      ->where('codigo_matricula', $aluno->matricula)->whereIn('Codigo_Status_Grade_Curricular', [2,3])
      ->where('codigo_ano_lectivo', $this->anoAtualPrincipal->index())->first();

    $planoCurricular = $this->planoCurricularActualDoAluno(auth()->user()->id);

    if (!$cadeirasInscritas) {
      if ($planoCurricular == 1) {
        $confirmacao_ano_corrente = 1; //Permitir ver tudo o estudante que tem inscrição em TFC
      } else {
        $confirmacao_ano_corrente = null; //Não Permitir ver tudo o estudante que não tem inscrição em TFC
      }
    } elseif (!$confirmacao_ano_corrente && $cadeirasInscritas) {
      if ($codigo_semestreActivo) {
       
        $cadeirasSemestreActivo = DB::table('tb_grade_curricular_aluno')
          ->join('tb_grade_curricular', 'tb_grade_curricular_aluno.codigo_grade_curricular', '=', 'tb_grade_curricular.Codigo')
          ->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')
          ->where('tb_semestres.Codigo', $codigo_semestreActivo)
          ->where('codigo_matricula', $aluno->matricula)->whereIn('Codigo_Status_Grade_Curricular', [2,3])
          ->where('codigo_ano_lectivo', $this->anoAtualPrincipal->index())->first();
         
        if ($cadeirasSemestreActivo) {
          $confirmacao_ano_corrente = null;
        } elseif (!$cadeirasSemestreActivo) {
          $confirmacao_ano_corrente = 1;
        }
        if ($planoCurricular == 1) {
          $confirmacao_ano_corrente = 1; //Permitir ver tudo o estudante que tem inscrição em TFC
        } elseif($planoCurricular==0 && $cadeirasSemestreActivo) {
          $confirmacao_ano_corrente = null; //Não Permitir ver tudo o estudante que não tem inscrição em TFC
        }
      } elseif (!$codigo_semestreActivo) { // se não existir semestre activo, retira-se a obrigacao de fazer selecao de horários
        $confirmacao_ano_corrente = 1;
      }
    }

    return $confirmacao_ano_corrente;
  }

  public function verificaConfirmacaoNoAnoAnterior($id_user)// patricio
  {
    $aluno = $this->dadosAlunoLogado($id_user);
    $anoAnterior = $this->anoAtualPrincipal->index() - 1;

    $confirmacao_ano_anterior = DB::table('tb_confirmacoes')->where('Codigo_Matricula', $aluno->matricula)->where('Codigo_Ano_lectivo', $anoAnterior)->first();
   
    $cadeirasInscritasAnoAnterior = DB::table('tb_grade_curricular_aluno')
      ->join('tb_grade_curricular', 'tb_grade_curricular_aluno.codigo_grade_curricular', '=', 'tb_grade_curricular.Codigo')
      ->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')
      ->where('codigo_matricula', $aluno->matricula)->whereIn('Codigo_Status_Grade_Curricular', [2,3])
      ->where('codigo_ano_lectivo', $anoAnterior)->first();

    if($confirmacao_ano_anterior || $cadeirasInscritasAnoAnterior)
    {
       $cadeirasInscritasAnoAnterior = 1;
    }
    else{
      $cadeirasInscritasAnoAnterior = null;
    }
    return $cadeirasInscritasAnoAnterior;
  } 

  public function verificaConfirmacaoNosAnosAnteriores($id_user)// patricio
  {
    $aluno = $this->dadosAlunoLogado($id_user);
    $anoAnterior = $this->anoAtualPrincipal->anosAnteriores();

    $alunoInscritoAnosAnteriores = DB::table('tb_inscricoes_ano_anterior')->where('codigo_matricula', $aluno->matricula)->where('codigo_ano_lectivo', 15)->select('codigo_ano_lectivo')->distinct('codigo_ano_lectivo')->count();
    
    $cadeirasInscritasAnosAnterioresConfirmacao = DB::table('tb_confirmacoes')->where('Codigo_Matricula', $aluno->matricula)
    //->where('Codigo_Ano_lectivo', $anoAnterior)->select('Codigo_Ano_lectivo')->distinct('Codigo_Ano_lectivo')->count();
    ->whereIn('codigo_ano_lectivo', $anoAnterior->pluck('Codigo'))->select('Codigo_Ano_lectivo')->distinct('Codigo_Ano_lectivo')->count();


    $confirmacao_anos_anteriores = DB::table('tb_grade_curricular_aluno')
      ->join('tb_grade_curricular', 'tb_grade_curricular_aluno.codigo_grade_curricular', '=', 'tb_grade_curricular.Codigo')
      ->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')
      ->where('codigo_matricula', $aluno->matricula)->whereIn('Codigo_Status_Grade_Curricular', [2,3])
      ->whereIn('codigo_ano_lectivo', $anoAnterior->pluck('Codigo'))
      ->get();

    /*if(filled($confirmacao_anos_anteriores) || $cadeirasInscritasAnosAnteriores>0 || $alunoInscritoAnosAnteriores > 0)
    {
      $cadeirasInscritasAnosAnteriores=1;
    }
    else{
      $cadeirasInscritasAnosAnteriores = 0;
    }*/

    
    $cadeirasInscritasAnosAnteriores = 0;

    if($cadeirasInscritasAnosAnterioresConfirmacao>0){
      $cadeirasInscritasAnosAnteriores = 1;
    }
    return $cadeirasInscritasAnosAnteriores;
  }

  public function planoCurricularComTfc($id_user)// patricio
  {
    $aluno = $this->dadosAlunoLogado($id_user);

    $gradesIsentas = DB::table('isencao_exame_especial')->get();
    $gradesIsentas1 = $gradesIsentas->pluck('grade_curricular_id');
    $arrayGradesIsentas = json_decode($gradesIsentas1, true);

    $temTfc = DB::table('tb_grade_curricular')
      ->join('tb_grade_curricular_aluno', 'tb_grade_curricular_aluno.codigo_grade_curricular', '=', 'tb_grade_curricular.Codigo')
      ->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')
      ->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')
      ->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')
      ->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')
      ->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')
      ->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina', 'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano', 'tb_duracao.codigo as codigo_duracao')
      ->distinct('disciplina')
      ->where('tb_disciplinas.Designacao', 'like', '%Trabalho de Fim de Curso%')
      ->where('tb_grade_curricular.status', 1)
      ->where('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', 2)
      ->where('tb_grade_curricular_aluno.codigo_matricula', $aluno->matricula)
      // ->where('tb_grade_curricular_aluno.codigo_ano_lectivo', $this->anoAtualPrincipal->index())
      ->whereNotIn('tb_grade_curricular.Codigo', $arrayGradesIsentas)->get();

    return $temTfc;
  }

  public function planoCurricularActualDoAluno($id)// patricio
  {

    $collection = collect([]);


    $cadeirasRestantes = 0;
    $aluno = DB::table('tb_matriculas')
      ->join('tb_admissao', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
      ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')->where('tb_preinscricao.user_id', $id)->select('tb_matriculas.Codigo as matricula', 'tb_matriculas.Codigo_Curso as curso_matricula', 'tb_preinscricao.Curso_Candidatura as curso_preinscricao')->first();


    $gradesIsentas = DB::table('isencao_exame_especial')->get();
    $gradesIsentas1 = $gradesIsentas->pluck('grade_curricular_id');
    $arrayGradesIsentas = json_decode($gradesIsentas1, true);


    $planoCurricular = DB::table('tb_grade_curricular')->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')->where('tb_grade_curricular.Codigo_Curso', $aluno->curso_matricula)/*->where('tb_disciplinas.Designacao', 'not like', '%Trabalho de Fim de Curso%')*/->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina', 'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano', 'tb_duracao.codigo as codigo_duracao')->distinct('disciplina')->where('tb_grade_curricular.status', 1)->whereNotIn('tb_grade_curricular.Codigo', $arrayGradesIsentas)->get();

    $collection = collect([]);


    //dd($planoCurricular);
    $planoCurricular1 = DB::table('tb_grade_curricular')->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')->where('tb_grade_curricular.Codigo_Curso', $aluno->curso_preinscricao)/*->where('tb_disciplinas.Designacao', 'not like', '%Trabalho de Fim de Curso%')*/->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina', 'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano', 'tb_duracao.codigo as codigo_duracao')->distinct('disciplina')->where('tb_grade_curricular.status', 1)->whereNotIn('tb_grade_curricular.Codigo', $arrayGradesIsentas)->get();

    $cadeirasEliminadas1 = DB::table('tb_grade_curricular')->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')->join('tb_grade_curricular_aluno', 'tb_grade_curricular_aluno.codigo_grade_curricular', '=', 'tb_grade_curricular.Codigo')->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina', 'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano', 'tb_grade_curricular.valor_inscricao as valor_cadeira', 'tb_duracao.codigo as codigo_duracao')->where('tb_grade_curricular.Codigo_Curso', $aluno->curso_preinscricao)->where('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', 3)->where('tb_grade_curricular_aluno.codigo_matricula', $aluno->matricula)->where('tb_grade_curricular.status', 1)->distinct('disciplina')->get();


    $cadeirasEliminadas = DB::table('tb_grade_curricular')->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')->join('tb_grade_curricular_aluno', 'tb_grade_curricular_aluno.codigo_grade_curricular', '=', 'tb_grade_curricular.Codigo')->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina', 'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano', 'tb_grade_curricular.valor_inscricao as valor_cadeira', 'tb_duracao.codigo as codigo_duracao')->where('tb_grade_curricular.Codigo_Curso', $aluno->curso_matricula)->where('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', 3)->where('tb_grade_curricular.status', 1)->where('tb_grade_curricular_aluno.codigo_matricula', $aluno->matricula)->distinct('disciplina')->get();
    $planoCurricularCountSemestral = $planoCurricular->where('codigo_duracao', 1)->count();
    $planoCurricularCountAnual = $planoCurricular->where('codigo_duracao', 2)->count() * 2;
    $cadeirasEliminadasCountSemestral = $cadeirasEliminadas->where('codigo_duracao', 1)->count();
    $cadeirasEliminadasCountAnual = $cadeirasEliminadas->where('codigo_duracao', 2)->count() * 2;
    $planoCurricular1CountSemestral = $planoCurricular1->where('codigo_duracao', 1)->count();
    $planoCurricular1CountAnual = $planoCurricular1->where('codigo_duracao', 2)->count() * 2;
    $cadeirasEliminadas1CountSemestral = $cadeirasEliminadas1->where('codigo_duracao', 1)->count();
    $cadeirasEliminadas1CountAnual = $cadeirasEliminadas1->where('codigo_duracao', 2)->count() * 2;
    //$naoFinalista = 100;
    $resultado = 0;
    if ($aluno) {
      if (($aluno->curso_preinscricao == 1 || $aluno->curso_preinscricao == 5 || $aluno->curso_preinscricao == 9 || $aluno->curso_matricula == 28 || $aluno->curso_matricula == 29 || $aluno->curso_matricula == 30 || $aluno->curso_matricula == 31 || $aluno->curso_matricula == 32 || $aluno->curso_matricula == 33 || $aluno->curso_matricula == 34 || $aluno->curso_matricula == 35)) { //SE O ALUNO ESTA INSCRITO EM UM CURSO DE ESPECIALIDADE
        if (($aluno->curso_preinscricao == 1 || $aluno->curso_preinscricao == 5 || $aluno->curso_preinscricao == 9) && ($aluno->curso_preinscricao == $aluno->curso_matricula)) {


          $cadeirasRestantes = ($planoCurricularCountSemestral + $planoCurricularCountAnual) - ($cadeirasEliminadasCountSemestral + $cadeirasEliminadasCountAnual);

          //dd($cadeirasEliminadas);

        } elseif ($aluno->curso_matricula == 28 || $aluno->curso_matricula == 29 || $aluno->curso_matricula == 30 || $aluno->curso_matricula == 31 || $aluno->curso_matricula == 32 || $aluno->curso_matricula == 33 || $aluno->curso_matricula == 34 || $aluno->curso_matricula == 35) {

          if ($aluno->curso_preinscricao != $aluno->curso_matricula) {
            $cadeirasRestantes = ($planoCurricular1CountSemestral + $planoCurricular1CountAnual + $planoCurricularCountSemestral + $planoCurricularCountAnual) - ($cadeirasEliminadasCountSemestral + $cadeirasEliminadasCountAnual + $cadeirasEliminadas1CountSemestral + $cadeirasEliminadas1CountAnual);
          }
        } else {
          //ESTUDANTE EMIGRADO
          $cadeirasRestantes = ($planoCurricular1CountSemestral + $planoCurricular1CountAnual) - ($cadeirasEliminadasCountSemestral + $cadeirasEliminadasCountAnual);
        }
      } else { // SE O ALUNO NAO ESTA INSCRITO EM UM CURSO DE ESPECIALIDADE

        $cadeirasRestantes = ($planoCurricular1CountSemestral + $planoCurricular1CountAnual) - ($cadeirasEliminadasCountSemestral + $cadeirasEliminadasCountAnual);
      }
    }
    //dd($cadeirasRestantes);
    if ($cadeirasRestantes <= 1) {
      $resultado = 1;
    }

    return $resultado;
  }

  public function getIsencaoMes_tempIds($codigo_anoLectivo, $id_user)// patricio
  {

    $aluno = $this->dadosAlunoLogado($id_user);

    $isencao = DB::table('tb_isencoes')
      ->where('mes_temp_id', '!=', null)
      ->where('codigo_anoLectivo', $codigo_anoLectivo)
      ->where('codigo_matricula', $aluno->matricula)
      ->where('estado_isensao', 'Activo')
      ->select('mes_temp_id as mes_temp_id')->get();

    $isencaoMes_tempIds = $isencao->pluck('mes_temp_id');

    return $isencaoMes_tempIds;
  }

  public function getIsencaoMesIds($codigo_anoLectivo, $id_user)
  {

    $aluno = $this->dadosAlunoLogado($id_user);

    $isencao = DB::table('tb_isencoes')
      ->where('mes_id', '!=', null)
      ->where('codigo_anoLectivo', $codigo_anoLectivo)
      ->where('codigo_matricula', $aluno->matricula)
      ->where('estado_isensao', 'Activo')
      ->select('mes_id as mes_id')->get();

    $isencaoMesIds = $isencao->pluck('mes_id');

    return $isencaoMesIds;
  }


  public function getQtdPrestacoesAlunoPorAnoLectivo($factura_id)// patricio
  {

    $codigo_anoLectivo = DB::table('factura')->where('Codigo', $factura_id)->first()->ano_lectivo;
    $anosLectivo = $this->anoLectivoService->AnosLectivo($codigo_anoLectivo);
    $isencaoMes_tempIds = $this->getIsencaoMes_tempIds($codigo_anoLectivo);
    $isencaoMesIds = $this->getIsencaoMesIds($codigo_anoLectivo);
    $verificaPagamentoMarco = $this->verificaPagamentoMarco($codigo_anoLectivo);
    $mes_temp_marco = DB::table('mes_temp')->where('ano_lectivo', $codigo_anoLectivo)->where('activo', 1)->whereNotIn('id', $isencaoMes_tempIds)->orderBy('id', 'asc')->select('id as mes_temp_id')->get();

    if (auth()->user()->preinscricao->codigo_tipo_candidatura == 1) {
      if ($verificaPagamentoMarco) {
        $array_meses_id = json_decode($mes_temp_marco->pluck('mes_temp_id'), true);
        array_push($array_meses_id, 1);
        $qtdPrestacoes = DB::table('mes_temp')->where('ano_lectivo', $codigo_anoLectivo)->whereIn('activo', [0, 1])->whereIn('id', $array_meses_id)->orderBy('id', 'asc')->limit(10)->get();
      } else {
        $qtdPrestacoes = DB::table('mes_temp')->where('ano_lectivo', $codigo_anoLectivo)->where('activo', 1)->whereNotIn('id', $isencaoMes_tempIds)->orderBy('id', 'asc')->get();

        if ((int)$anosLectivo->Designacao <= 2019 && $anosLectivo->Designacao!= $this->anoAtualPrincipal->cicloMestrado()->Designacao && $anosLectivo->Designacao != $this->anoAtualPrincipal->cicloDoutoramento()->Designacao) {
          $qtdPrestacoes = DB::table('meses')->whereNotIn('codigo', $isencaoMesIds)->orderBy('codigo', 'asc')->get();
        }
      }
    } else {
      $qtdPrestacoes = DB::table('mes_temp')->where('ano_lectivo', $codigo_anoLectivo)->where('activo_posgraduacao', 1)->whereNotIn('id', $isencaoMes_tempIds)->orderBy('id', 'asc')->get();
    }

    return $qtdPrestacoes;
  }

  public function verificaPagamentoMarco($codigo_anoLectivo, $pre_inscricao)
  {
    $marcoPagamento = DB::table('tb_pagamentos')
      ->join('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', '=', 'tb_pagamentos.Codigo')
      ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_pagamentos.Codigo_PreInscricao')
      ->join('factura', 'factura.Codigo', '=', 'tb_pagamentos.codigo_factura')
      ->join('factura_items', 'factura.Codigo', '=', 'factura_items.CodigoFactura')
      ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', '=', 'factura_items.CodigoProduto')
      ->where('tb_preinscricao.Codigo', $pre_inscricao)
      ->where('tb_pagamentos.AnoLectivo', $codigo_anoLectivo)
      ->where('tb_pagamentosi.mes_temp_id', 1)
      ->where('factura.estado', '!=', 3)
      ->where('tb_tipo_servicos.TipoServico', 'Mensal')
      ->where('tb_pagamentos.estado', 1)->select('tb_pagamentosi.mes_temp_id')
      ->first();

    return $marcoPagamento;
  }



  public function isencoesAluno($codigo_anoLectivo, $id_user)
  {

    $aluno = $this->dadosAlunoLogado($id_user);

    $isencoes = DB::table('tb_isencoes')
      ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', '=', 'tb_isencoes.codigo_servico')
      ->leftJoin('mes_temp', 'mes_temp.id', '=', 'tb_isencoes.mes_temp_id')
      ->leftJoin('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', '=', 'mes_temp.ano_lectivo')
      ->where('tb_isencoes.codigo_anoLectivo', $codigo_anoLectivo)
      ->where('tb_isencoes.codigo_matricula', $aluno->matricula)
      ->where('tb_isencoes.estado_isensao', 'Activo')
      ->select('mes_temp.prestacao as prestacao', 'tb_tipo_servicos.Descricao as servico', 'tb_ano_lectivo.designacao as ano')->get();


    return $isencoes;
  }
}
