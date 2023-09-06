<?php

namespace App\Services;

use App\Candidato;
use App\CandidatoProva;
use App\Confirmacao;
use App\Factura;
use App\GradeCurricularAluno;
use App\HorarioProva;
use DB;
use App\Pagamento;
use App\Repositories\AlunoRepository;
use App\Http\Controllers\ClassesAuxiliares\anoAtual;
use App\Prova;

class InscricoesService
{
  public $alunoRepository;
  public $anoAtualPrincipal;

  public function __construct()
  {
    $this->alunoRepository = new AlunoRepository();
    $this->anoAtualPrincipal = new anoAtual();
  }


  private function aluno()
  {
    return $this->alunoRepository->dadosAlunoLogado();
  }
  public function limiteCadeiras()
  {

    $aluno = $this->alunoRepository->dadosAlunoLogado();

    $qtdCadeiras = $this->qtdcadeirasInscritas();




    $limite_inscricao = DB::table('inscricao_limite')->where('curso_id', $aluno->curso_matricula)->select('*')->first();
    if ($limite_inscricao) {

      if ($qtdCadeiras >= $limite_inscricao->limite_cadeira) {

        return 1;
      } else {
        return '';
      }
    } elseif (!$limite_inscricao) {
      if ($qtdCadeiras >= 14) {
        return 1;
      } else {

        return '';
      }
    }
  }

  public function qtdcadeirasInscritas()
  { // cadeiras inscritas no ano corrente
    $aluno = $this->alunoRepository->dadosAlunoLogado();
    $cadeirasInscritas = DB::table('tb_matriculas')
      ->join('tb_grade_curricular_aluno', 'tb_grade_curricular_aluno.codigo_matricula', '=', 'tb_matriculas.Codigo')
      ->join('tb_grade_curricular', 'tb_grade_curricular_aluno.codigo_grade_curricular', '=', 'tb_grade_curricular.Codigo')
      ->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')
      ->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')
      ->select('tb_matriculas.Codigo_Curso', 'tb_disciplinas.Designacao', 'tb_duracao.codigo as codigo_duracao')
      ->whereIn('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', [1, 2, 3])
      ->where('tb_grade_curricular_aluno.codigo_ano_lectivo', $this->anoAtualPrincipal->index())
      ->where('tb_matriculas.Codigo', $aluno->matricula)
      ->distinct('tb_disciplinas.Designacao')->get();

    $qtdAnual = $cadeirasInscritas->where('codigo_duracao', 2)->count() * 2;

    $qtdSemestral = $cadeirasInscritas->where('codigo_duracao', 1)->count();

    $qtdCadeiras = $qtdAnual + $qtdSemestral;

    return $qtdCadeiras;
  }
  public function pagamentoPendente($numero_matricula)
  {
    $pagamento = Pagamento::where('corrente', 1)
      ->whereIn('estado', [0, 2])->whereHas('fatura', function ($query) use ($numero_matricula) {
        $query->where('corrente', 1)->where('estado', '!=', 3)
          ->where('CodigoMatricula', $numero_matricula)->where('codigo_descricao', 3);
      })->first();


    return $pagamento;
  }

  public function gradeCurricularAlunoPendentes($numero_matricula)
  {
    
    $gradeCurricularAlunoPendente = DB::table('tb_grade_curricular_aluno')->where('tb_grade_curricular_aluno.codigo_matricula', $numero_matricula)
    ->select('tb_grade_curricular_aluno.codigo')
    ->where('tb_grade_curricular_aluno.codigo_ano_lectivo',  $this->anoAtualPrincipal->index())
    ->where('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', 4)->first();
    return $gradeCurricularAlunoPendente;
  }

  public function finalista($id)
  {



    $collection = collect([]);


    $cadeirasRestantes = 0;
    $aluno = DB::table('tb_matriculas')
      ->join('tb_admissao', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
      ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')->where('tb_preinscricao.user_id', $id)->select('tb_matriculas.Codigo as matricula', 'tb_matriculas.Codigo_Curso as curso_matricula', 'tb_preinscricao.Curso_Candidatura as curso_preinscricao')->first();


    $gradesIsentas = DB::table('isencao_exame_especial')->get();
    $gradesIsentas1 = $gradesIsentas->pluck('grade_curricular_id');
    $arrayGradesIsentas = json_decode($gradesIsentas1, true);


    $planoCurricular = DB::table('tb_grade_curricular')->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')->where('tb_grade_curricular.Codigo_Curso', $aluno->curso_matricula)->where('tb_disciplinas.Designacao', 'not like', '%Trabalho de Fim de Curso%')->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina', 'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano', 'tb_duracao.codigo as codigo_duracao')->distinct('disciplina')->where('tb_grade_curricular.status', 1)->whereNotIn('tb_grade_curricular.Codigo', $arrayGradesIsentas)->get();

    $collection = collect([]);


    //dd($planoCurricular);
    $planoCurricular1 = DB::table('tb_grade_curricular')->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')->where('tb_grade_curricular.Codigo_Curso', $aluno->curso_preinscricao)->where('tb_disciplinas.Designacao', 'not like', '%Trabalho de Fim de Curso%')->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina', 'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano', 'tb_duracao.codigo as codigo_duracao')->distinct('disciplina')->where('tb_grade_curricular.status', 1)->whereNotIn('tb_grade_curricular.Codigo', $arrayGradesIsentas)->get();

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
    if ($cadeirasRestantes <= 4) {

      $resultado = 1;
    }

    return $resultado;
  }






  public function inscricaoCursoEspecial($classe, $curso, $id)
  {

    $data['fezInscricao'] = '';
    $aluno = DB::table('tb_matriculas')
      ->join('tb_admissao', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
      ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')->where('tb_preinscricao.user_id', $id)->select('tb_matriculas.Codigo as matricula', 'tb_matriculas.Codigo_Curso as curso_matricula', 'tb_preinscricao.Codigo as Codigo_PreInscricao', 'tb_preinscricao.Curso_Candidatura as curso_preinscricao')->first();


    $planoCurricular = DB::table('tb_grade_curricular')->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')->where('tb_grade_curricular.Codigo_Curso', $aluno->curso_matricula)->where('tb_grade_curricular.status', 1)->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina', 'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano', 'tb_duracao.codigo as codigo_duracao')->distinct('disciplina')->get();

    $planoCurricular1 = DB::table('tb_grade_curricular')->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')->where('tb_grade_curricular.Codigo_Curso', $aluno->curso_preinscricao)->where('tb_grade_curricular.status', 1)->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina', 'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano', 'tb_duracao.codigo as codigo_duracao')->distinct('disciplina')->get();
    //dd($planoCurricular->where('codigo_ano',2)->count());
    $fezInscricao = DB::table('tb_grade_curricular_aluno')->where('codigo_matricula', $aluno->matricula)->where('Codigo_Status_Grade_Curricular', 4)->select('*')->first();

    if ($fezInscricao) {

      $data['fezInscricao'] = 'SIM';
    }

    //cadeiras eliminadas
    $cadeirasEliminadas = DB::table('tb_grade_curricular')->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')->join('tb_grade_curricular_aluno', 'tb_grade_curricular_aluno.codigo_grade_curricular', '=', 'tb_grade_curricular.Codigo')->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina', 'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano', 'tb_grade_curricular.valor_inscricao as valor_cadeira', 'tb_duracao.codigo as codigo_duracao')->where('tb_grade_curricular.Codigo_Curso', $aluno->curso_matricula)->where('tb_grade_curricular.status', 1)->where('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', 3)->where('tb_grade_curricular_aluno.codigo_matricula', $aluno->matricula)->orWhere('tb_grade_curricular.Codigo_Curso', $aluno->curso_preinscricao)->where('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', 3)->where('tb_grade_curricular_aluno.codigo_matricula', $aluno->matricula)->distinct('disciplina')->get();



    $gradesEliminadasIds = $cadeirasEliminadas->pluck('codigo_grade');
    $arrayEliminadas = json_decode($gradesEliminadasIds, true);

    $cadeirasEliminadas1 = DB::table('tb_grade_curricular')->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')->join('tb_grade_curricular_aluno', 'tb_grade_curricular_aluno.codigo_grade_curricular', '=', 'tb_grade_curricular.Codigo')->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina', 'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano', 'tb_grade_curricular.valor_inscricao as valor_cadeira', 'tb_duracao.codigo as codigo_duracao')->where('tb_grade_curricular.Codigo_Curso', $aluno->curso_preinscricao)->where('tb_grade_curricular.status', 1)->where('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', 3)->where('tb_grade_curricular_aluno.codigo_matricula', $aluno->matricula)->distinct('disciplina')->get();



    $gradesEliminadas1Ids = $cadeirasEliminadas1->pluck('codigo_grade');
    $arrayEliminadas1 = json_decode($gradesEliminadas1Ids, true);

    //cadeiras em curso
    $cadeirasEmCurso = DB::table('tb_grade_curricular')->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')->join('tb_grade_curricular_aluno', 'tb_grade_curricular_aluno.codigo_grade_curricular', '=', 'tb_grade_curricular.Codigo')->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina', 'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano')->where('tb_grade_curricular.Codigo_Curso', $aluno->curso_matricula)->where('tb_grade_curricular.status', 1)->where('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', 2)->where('tb_grade_curricular_aluno.codigo_matricula', $aluno->matricula)->distinct('disciplina')->get();

    $gradesEmCursoIds = $cadeirasEmCurso->pluck('codigo_grade');
    $arrayEmCurso = json_decode($gradesEmCursoIds, true);
    //cadeiras inseridas
    $data['cadeirasInseridas'] = DB::table('tb_grade_curricular')->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')->join('tb_grade_curricular_aluno', 'tb_grade_curricular_aluno.codigo_grade_curricular', '=', 'tb_grade_curricular.Codigo')->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina', 'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano', 'tb_grade_curricular_aluno.codigo as codigo_gc_aluno', 'tb_grade_curricular_aluno.canal as canal_gc_aluno', 'tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular as status_gc_aluno', 'tb_duracao.codigo as codigo_duracao', 'tb_grade_curricular_aluno.estado as estado_gc_aluno')->where('tb_grade_curricular.status', 1)->where('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', 4)->where('tb_grade_curricular_aluno.codigo_matricula', $aluno->matricula)->distinct('disciplina')->get();


    $gradesInseridasIds = $data['cadeirasInseridas']->pluck('codigo_grade');
    $arrayInseridas = json_decode($gradesInseridasIds, true);


    $cadeirasRestantes = DB::table('tb_grade_curricular')->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina', 'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano', 'tb_grade_curricular.valor_inscricao as valor_cadeira', 'tb_duracao.codigo as codigo_duracao')->where('tb_grade_curricular.status', 1)->where('tb_grade_curricular.Codigo_Curso', $aluno->curso_preinscricao)->whereNotIn('tb_grade_curricular.Codigo', $arrayEliminadas)->whereNotIn('tb_grade_curricular.Codigo', $arrayEmCurso)->whereNotIn('tb_grade_curricular.Codigo', $arrayInseridas)->distinct('disciplina')->get();

    //$collection = collect([]);
    $collectionP = ($planoCurricular);
    $collectionP1 = ($planoCurricular1);
    $collectionE = ($cadeirasEliminadas);
    $collectionE1 = ($cadeirasEliminadas1);
    $maiorClasse = DB::table('tb_grade_curricular')->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')->join('tb_grade_curricular_aluno', 'tb_grade_curricular_aluno.codigo_grade_curricular', '=', 'tb_grade_curricular.Codigo')->select(DB::raw('max(tb_classes.Codigo) as classe'))->where('tb_grade_curricular.status', 1)->where('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', 3)->where('tb_grade_curricular_aluno.codigo_matricula', $aluno->matricula)->distinct('disciplina')->first();
    $cadeirasPush = collect([]);
    $cadeirasAtrasoMaiorClasse = collect([]);
    $cadeirasAtrasoAnosA = collect([]);
    $arrayAtraso = collect([]);
    $cadeirasAPFpush = collect([]);
    $qdtAnualplanoCurr = 0;
    $qdtSemestralplanoCurr = 0;
    $qdtAnualConcluida = 0;
    $qdtSemestralConcluida = 0;
    $qdtAnualplanoCurr1 = 0;
    $qdtSemestralplanoCurr1 = 0;
    $collectionCR = ($cadeirasRestantes);
    $collectionCadeiraA = collect([]);
    $cadeirasAtraso = collect([]);
    $data['cadeirasAtraso'] = collect([]);
    $plano_maior_classe = collect([]);
    $qdtplanoCurr = 0;
    $qdtplanoCurr1 = 0;
    if ($maiorClasse && $maiorClasse->classe > 1) {
      //qtd da grade
      $plano_maior_classe = $collectionP->where('codigo_ano', $maiorClasse->classe);
      //dd($maiorClasse->classe);
      $qdtAnualplanoCurr = $collectionP->where('codigo_ano', $maiorClasse->classe)->count() * 2;


      $qdtSemestralplanoCurr = $collectionP->where('codigo_ano', $maiorClasse->classe)->where('codigo_duracao', 1)->count();


      $qdtplanoCurr = $qdtAnualplanoCurr + $qdtSemestralplanoCurr;

      //

      //qtd da grade com curso da preinscricao
      $qdtAnualplanoCurr1 = $collectionP1->where('codigo_ano', $maiorClasse->classe)->count() * 2;


      $qdtSemestralplanoCurr1 = $collectionP1->where('codigo_ano', $maiorClasse->classe)->where('codigo_duracao', 1)->count();


      $qdtplanoCurr1 = $qdtAnualplanoCurr1 + $qdtSemestralplanoCurr1;

      //
      //qtd concluida
      $qdtAnualConcluida = $collectionE->where('codigo_ano', $maiorClasse->classe)->where('codigo_duracao', 2)->count() * 2;
      $qdtSemestralConcluida = $collectionE->where('codigo_ano', $maiorClasse->classe)->where('codigo_duracao', 1)->count();
      $qdtConcluidaMaiorClasse = $qdtAnualConcluida + $qdtSemestralConcluida;
      //

      if (sizeof($plano_maior_classe) == 0) {

        $qdtplanoCurr = $qdtplanoCurr1;
      }

      $fatorAprovacaoMaiorClasse = (($qdtConcluidaMaiorClasse) / $qdtplanoCurr) * 100;

      if ($fatorAprovacaoMaiorClasse < 50) {

        $cadeirasAtrasoMaiorClasse = $collectionCR->where('codigo_ano', $maiorClasse->classe);


        //cadeiras em atraso

      }


      $cadeirasAnosA = $collectionE->where('codigo_ano', '<', $maiorClasse->classe);
      $CAAnterioresPush = json_decode($cadeirasAnosA, true);

      foreach ($CAAnterioresPush as $key => $cad) {

        $qdtAnualAnosA = $collectionE->where('codigo_ano', $cad['codigo_ano'])->where('codigo_duracao', 2)->count() * 2;
        $qdtSemestralAnosA = $collectionE->where('codigo_ano', $cad['codigo_ano'])->where('codigo_duracao', 1)->count();


        $qdtAnualplanoCAA = $collectionP1->where('codigo_ano', $cad['codigo_ano'])->where('codigo_duracao', 2)->count() * 2;


        $qdtSemestralplanoCAA = $collectionP1->where('codigo_ano', $cad['codigo_ano'])->where('codigo_duracao', 1)->count();
        //dd($qdtSemestralplanoCAA);
        $qdtplanoCAA = $qdtAnualplanoCAA + $qdtSemestralplanoCAA;

        $qdtConcluidaAnosA = $qdtAnualAnosA + $qdtSemestralAnosA;
        //

        $fatorAprovacaoAnosA = (($qdtConcluidaAnosA) / $qdtplanoCAA) * 100;
        if ($fatorAprovacaoAnosA < 100) {

          $cadeirasPorFazer = $collectionCR->where('codigo_ano', $cad['codigo_ano']);
          $CadeirasPF = json_decode($cadeirasPorFazer, true);
          foreach ($CadeirasPF as $key => $value1) {
            # code...
            $cadeirasAPFpush->push(['disciplina' => $value1['disciplina'], 'semestre' => $value1['semestre'], 'classe' => $value1['classe'], 'duracao_disciplina' => $value1['duracao_disciplina'], 'codigo_grade' => $value1['codigo_grade'], 'codigo_ano' => $value1['codigo_ano'], 'valor_cadeira' => $value1['valor_cadeira'], 'codigo_duracao' => $value1['codigo_duracao']]);;
          }
        }

        $cadeirasAtrasoAnosA = $cadeirasAPFpush->unique();

        $cadeirasAtraso = $cadeirasAtrasoMaiorClasse->mergeRecursive($cadeirasAtrasoAnosA);

        $cadeirasPush = json_decode($cadeirasAtraso, true);
      }

      foreach ($cadeirasPush as $key => $value) {

        $collectionCadeiraA->push(['disciplina' => $value['disciplina'], 'semestre' => $value['semestre'], 'classe' => $value['classe'], 'duracao_disciplina' => $value['duracao_disciplina'], 'codigo_grade' => $value['codigo_grade'], 'codigo_ano' => $value['codigo_ano'], 'valor_cadeira' => $value['valor_cadeira'], 'codigo_duracao' => $value['codigo_duracao'], 'status' => 0]);
      }


      $collectionCadeiraA->sortBy('codigo_ano');

      $data['cadeirasAtraso'] = $collectionCadeiraA;



      $gradesAtrasoIds = $data['cadeirasAtraso']->pluck('codigo_grade');
      $arrayAtraso = json_decode($gradesAtrasoIds, true);
    }


    $data['cadeiras'] = DB::table('tb_grade_curricular')->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina', 'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano', 'tb_grade_curricular.valor_inscricao as valor_cadeira', 'tb_duracao.codigo as codigo_duracao')->where('tb_grade_curricular.Codigo_Classe', $classe)->where('tb_grade_curricular.Codigo_Curso', $curso)->where('tb_grade_curricular.status', 1)->whereNotIn('tb_grade_curricular.Codigo', $arrayEliminadas)->whereNotIn('tb_grade_curricular.Codigo', $arrayAtraso)->whereNotIn('tb_grade_curricular.Codigo', $arrayEmCurso)->whereNotIn('tb_grade_curricular.Codigo', $arrayInseridas)->distinct('disciplina')->get();


    $limite_inscricao = DB::table('inscricao_limite')->where('curso_id', $aluno->curso_matricula)->select('*')->first();
    if ($limite_inscricao) {
      $data['limite_inscricao'] = $limite_inscricao;
    } elseif ($limite_inscricao) {
      $data['limite_inscricao'] = '';
    }

    $InseridasqtdAnual = $data['cadeirasInseridas']->where('codigo_duracao', 2)->count() * 2;

    $InseridasqtdSemestral = $data['cadeirasInseridas']->where('codigo_duracao', 1)->count();

    $data['tamanhoInseridas'] = $InseridasqtdAnual + $InseridasqtdSemestral;

    $AtrasoqtdAnual = $data['cadeirasAtraso']->where('codigo_duracao', 2)->count() * 2;
    $AtrasoqtdSemestral = $data['cadeirasAtraso']->where('codigo_duracao', 1)->count();
    $data['tamanhoAtraso'] = $AtrasoqtdAnual + $AtrasoqtdSemestral;


    return $data;
  }

  public function isencaoServico($sigla_servico, $codigo_anoLectivo)
  {

    $aluno = $this->alunoRepository->dadosAlunoLogado();
    //isencao de qualquer servico por estudante
    $isento = DB::table('tb_isencoes')->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', '=', 'tb_isencoes.codigo_servico')->where('tb_isencoes.codigo_matricula', $aluno->matricula)->where('tb_isencoes.codigo_anoLectivo', $codigo_anoLectivo)->where('tb_tipo_servicos.codigo_ano_lectivo', $codigo_anoLectivo)->where('estado_isensao', 'Activo')->where('tb_tipo_servicos.sigla', $sigla_servico)->select('*')->first();

    return $isento;
  }

  public function isencaoUCSegSemestre($sigla_servico, $codigo_anoLectivo)
  {

    $aluno = $this->alunoRepository->dadosAlunoLogado();

    $prazo_isencao = DB::table('tb_isencao_instituicao')->where('estado', 1)->where('codigo_instituicao', 9)->where('codigo_ano_lectivo', $codigo_anoLectivo)->first();
   
    $inscricao = DB::table('tb_grade_curricular_aluno')
    ->join('tb_grade_curricular', 'tb_grade_curricular.Codigo', 'tb_grade_curricular_aluno.codigo_grade_curricular')
    ->where('tb_grade_curricular_aluno.codigo_matricula', $aluno->matricula)
    ->where('tb_grade_curricular_aluno.codigo_ano_lectivo', $codigo_anoLectivo)
    ->where('tb_grade_curricular.Codigo_Semestre', 1)
    ->whereIn('Codigo_Status_Grade_Curricular',[2,3,1])
    ->get();

    if($prazo_isencao && count($inscricao)>0){
      $isento = 1;
    }else{
      $isento = null;
    }

    return $isento;
  }

  // isencao de multa de reconfirmacao fora do prazo
  public function isencaoMultaPorCurso($sigla_servico, $codigo_anoLectivo)
  {


    $aluno = $this->alunoRepository->dadosAlunoLogado();
    //isencao de qualquer servico por curso

    $isento = DB::table('tb_isencoes_curso')->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', '=', 'tb_isencoes_curso.codigo_servico')->where('tb_isencoes_curso.codigo_curso', $aluno->curso_matricula)->where('tb_isencoes_curso.codigo_ano_lectivo', $codigo_anoLectivo)->where('tb_tipo_servicos.codigo_ano_lectivo', $codigo_anoLectivo)->where('tb_tipo_servicos.sigla', $sigla_servico)->select('*')->first();


    return $isento;
  }


  public function verificarInscricaoAvaliacao($codigo_anoLectivo, $codigo_grade, $codigo_avaliacao, $codigo_matricula)
  {

    $condicoes = [];

    if ($codigo_avaliacao != 22) {
      array_push($condicoes, ['codigo_ano_lectivo', $codigo_anoLectivo]);
    }

    $inscricaoRecurso = DB::table('inscricao_avaliacoes')
      ->join('tb_grade_curricular', 'inscricao_avaliacoes.codigo_grade', '=', 'tb_grade_curricular.Codigo')
      ->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')
      ->select('inscricao_avaliacoes.*', 'tb_disciplinas.Designacao as disciplina')
      ->where('codigo_matricula', $codigo_matricula)
      ->where('codigo_grade', $codigo_grade)
      ->where($condicoes)
      ->where('estado', '!=', 'anulado')
      ->where('inscricao_avaliacoes.codigo_tipo_avaliacao', $codigo_avaliacao)->first();


    return $inscricaoRecurso;
  }

  public function inscricaAutomaticaDeUCsPosGraduacao()
  {

    $anocorrente = DB::table('tb_ano_lectivo')->where('Codigo', $this->anoAtualPrincipal->index())->first();

    $classe_ano_anterior = DB::table('tb_grade_curricular_aluno')
      ->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', 'tb_grade_curricular_aluno.codigo_ano_lectivo')
      ->join('tb_grade_curricular', 'tb_grade_curricular.Codigo', 'tb_grade_curricular_aluno.codigo_grade_curricular')
      ->select(DB::raw('max(tb_grade_curricular.Codigo_Classe) as classe'))
      ->where('codigo_matricula', $this->alunoRepository->dadosAlunoLogado()->matricula)
      ->where('tb_ano_lectivo.ordem', $anocorrente->ordem - 1)
      ->where('Codigo_Status_Grade_Curricular', 2)
      ->orderBy('tb_grade_curricular.Codigo_Classe', 'desc')
      ->first();

    $incricao_cadeira_ano_corrente = DB::table('tb_grade_curricular_aluno')
      ->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', 'tb_grade_curricular_aluno.codigo_ano_lectivo')
      ->join('tb_grade_curricular', 'tb_grade_curricular.Codigo', 'tb_grade_curricular_aluno.codigo_grade_curricular')
      ->select('tb_grade_curricular_aluno.codigo', 'tb_grade_curricular_aluno.codigo_grade_curricular', 'tb_grade_curricular_aluno.turma', 'tb_grade_curricular_aluno.codigo_confirmacao')
      ->where('codigo_matricula', $this->alunoRepository->dadosAlunoLogado()->matricula)
      ->where('codigo_ano_lectivo', $this->anoAtualPrincipal->index())
      ->where('tb_grade_curricular.Codigo_Classe', $classe_ano_anterior->classe + 1)
      ->where('Codigo_Status_Grade_Curricular', 2)
      ->get();

    $turma = DB::table('tb_turmas')->where('Codigo_Curso', auth()->user()->preinscricao->Curso_Candidatura)->where('Codigo_Classe', $classe_ano_anterior->classe + 1)->where('Codigo_AnoLectivo', $this->anoAtualPrincipal->index())->first();
    $grades = DB::table('tb_grade_curricular')->where('Codigo_Curso', auth()->user()->preinscricao->Curso_Candidatura)->where('Codigo_Classe', $classe_ano_anterior->classe + 1)->get();
    $confirmacao = DB::table('tb_confirmacoes')->where('Codigo_Matricula', $this->alunoRepository->dadosAlunoLogado()->matricula)->where('Codigo_Ano_lectivo', $this->anoAtualPrincipal->index())->where('Classe', $classe_ano_anterior->classe + 1)->first();

    if (auth()->user()->preinscricao->codigo_tipo_candidatura != 1) {
      if (!$confirmacao) {
        try {
          $confirmacao_id = DB::table('tb_confirmacoes')->insertGetId([
            'Codigo_Matricula' => $this->alunoRepository->dadosAlunoLogado()->matricula, 'Data_Confirmacao' => date('Y-m-d'), 'Codigo_Turma' => $turma->Codigo, 'Codigo_Ano_lectivo' => $this->anoAtualPrincipal->index(), 'Estado' => 1, 'Classe' => $classe_ano_anterior->classe + 1, 'canal' => 3
          ]);
        } catch (\Exception $e) {
          DB::rollback();
        }
      } elseif ($confirmacao) {
        //dd($confirmacao);
        $confirmacao_id = $confirmacao->Codigo;
      }

      $array = json_decode($grades, true);
      $array1 = json_decode($incricao_cadeira_ano_corrente, true);

      if (blank($incricao_cadeira_ano_corrente)) {
        try {
          foreach ($array as $key => $value) {

            DB::table('tb_grade_curricular_aluno')->insert(
              [
                'codigo_grade_curricular' => $value['Codigo'], 'codigo_confirmacao' => $confirmacao_id, 'turma' => $turma->Codigo,
                'codigo_matricula' => $this->alunoRepository->dadosAlunoLogado()->matricula, 'estado' => 1,
                'canal' => 3, 'codigo_ano_lectivo' => $this->anoAtualPrincipal->index(), 'Nota' => 0, 'observacao' => 'Inscrição automática no Portal',
                'Codigo_Status_Grade_Curricular' => 2, 'Codigo_Status_Grade_Curricular' => 2, 'Codigo_Status_Grade_Curricular' => 2
              ]
            );
          }
        } catch (\Exception $e) {
          DB::rollback();
        }
      } else {
        try {
          foreach ($array1 as $key => $value1) {
            if ($value1['codigo_confirmacao'] != NULL || $value1['turma'] != NULL) {
              DB::table('tb_grade_curricular_aluno')->where('codigo',  $value1['codigo'])->update(['codigo_confirmacao' => $confirmacao_id, 'turma' => $turma->Codigo]);
            }
          }
        } catch (\Exception $e) {
          dd($e->getMessage());
          DB::rollback();
        }
      }
    }
    $msg = "Inscrição automatica realizada com sucesso";
    DB::commit();
    return $msg;
  }




  public function cadeirasParaverColisao($semestre)
  { // pegar cadeiras para verificar colisao
    
    $anoCorrente = $this->anoAtualPrincipal->index();
    $aluno = $this->alunoRepository->dadosAlunoLogado();
    $cadeirasPVerColisao = DB::table('tb_grade_curricular_aluno')
      ->join('mgh_tb_aula','mgh_tb_aula.fk_horario',DB::raw("JSON_EXTRACT(tb_grade_curricular_aluno.ref_horario, '$.pk')"))
      ->join('mgh_tb_horario','mgh_tb_horario.pk_horario',DB::raw("JSON_EXTRACT(tb_grade_curricular_aluno.ref_horario, '$.pk')"))
      ->join('mgh_tb_dia_da_semana','mgh_tb_dia_da_semana.pk_dia_da_semana','mgh_tb_aula.fk_dia_da_semana')
      ->whereIn('Codigo_Status_Grade_Curricular', [2, 4])
      ->where('codigo_matricula', $aluno->matricula)
      ->where('codigo_ano_lectivo', $anoCorrente)
      ->where(DB::raw("JSON_EXTRACT(mgh_tb_horario.ref_periodicidade, '$.pk')"), $semestre)
      ->where('tb_grade_curricular_aluno.codigo_confirmacao', '!=', null)
      ->where('ref_horario', '!=', null)
      ->select(DB::raw("JSON_EXTRACT(tb_grade_curricular_aluno.ref_horario, '$.desc') as desc_horario"),'mgh_tb_aula.fk_horario', 'mgh_tb_aula.hora_inicio', 
          'mgh_tb_aula.hora_termino', 'mgh_tb_aula.fk_dia_da_semana', 'mgh_tb_dia_da_semana.designacao as dia_semana')
      ->get();

    return $cadeirasPVerColisao;
  }

  public function colisao($semana, $horaInicio)
  { // pegar cadeiras para verificar colisao
    
    $anoCorrente = $this->anoAtualPrincipal->index();
    $aluno = $this->alunoRepository->dadosAlunoLogado();

        $cadeirasPVerColisao = DB::table('tb_grade_curricular_aluno')
        ->join('mgh_tb_aula','mgh_tb_aula.fk_horario',DB::raw("JSON_EXTRACT(tb_grade_curricular_aluno.ref_horario, '$.pk')"))
        ->join('mgh_tb_dia_da_semana','mgh_tb_dia_da_semana.pk_dia_da_semana','mgh_tb_aula.fk_dia_da_semana')
        ->whereIn('Codigo_Status_Grade_Curricular', [2, 4])
        ->where('codigo_matricula', $aluno->matricula)
        ->where('codigo_ano_lectivo', $anoCorrente)
        ->where('mgh_tb_dia_da_semana.pk_dia_da_semana', $semana)
        ->whereIn('mgh_tb_aula.hora_inicio', [$horaInicio])
        ->where('tb_grade_curricular_aluno.codigo_confirmacao', '!=', null)
        ->where('ref_horario', '!=', null)
        ->select(DB::raw("JSON_EXTRACT(tb_grade_curricular_aluno.ref_horario, '$.desc') as desc_horario"),'mgh_tb_aula.fk_horario', 'mgh_tb_aula.hora_inicio', 
            'mgh_tb_aula.hora_termino', 'mgh_tb_aula.fk_dia_da_semana', 'mgh_tb_dia_da_semana.designacao as dia_semana')
        ->distinct()->get();

      
    return $cadeirasPVerColisao;
  }


  public function ignorarCadeirasAtraso()
  {  //por curso

    $aluno = $this->alunoRepository->dadosAlunoLogado();
    // verificar se existe parametro para ignorar cadeiras atraso para todos os cursos
    $parametro = DB::table('mgim_tb_parametro_inscricao')
      //->join('parametro_cursos_inscricoes_sem_UC_atraso', 'parametro_cursos_inscricoes_sem_UC_atraso.parametro_inscricoes_id', '=', 'mgim_tb_parametro_inscricao.pk_parametro')
      ->where('mgim_tb_parametro_inscricao.active_state', 2) // estado 2 - definido para todos os cursos
      ->where('mgim_tb_parametro_inscricao.sigla', 'iuca')->first();

    if (!$parametro) {

      $parametro = DB::table('mgim_tb_parametro_inscricao')
      ->join('parametro_cursos_inscricoes_sem_UC_atraso', 'parametro_cursos_inscricoes_sem_UC_atraso.parametro_inscricoes_id', '=', 'mgim_tb_parametro_inscricao.pk_parametro')
      ->where('mgim_tb_parametro_inscricao.active_state', 1) // estado 2 - definido para todos os cursos
        ->where('parametro_cursos_inscricoes_sem_UC_atraso.curso_id', $aluno->curso_matricula)
        ->where('mgim_tb_parametro_inscricao.sigla', 'iuca')->first();
    }


    return $parametro;
  }
  public function inscricaoSemHorario()
  {

    $parametro = DB::table('mgim_tb_parametro_inscricao')->where('active_state', 1)->where('sigla', 'iucsh')->first();

    return $parametro;
  }
  public function permitirInscricaoComDivida()
  {

    $parametro = DB::table('mgim_tb_parametro_inscricao')->where('active_state', 1)->where('sigla', 'icd')->first();

    return $parametro;
  } //pagamentoExpiradoPorTipoFatura

    //************   ELIMINACAO DE CADEIRAS ************** */


  public function eliminarCadeiras($codigo_fatura)
  { // funcao generica para eliminar cadeiras inscritas por fatura.

    
    $dado=false;
    $fatura = DB::table('factura')->where('Codigo', $codigo_fatura)->first();
    $anoCorrente = $this->anoAtualPrincipal->index();
    $anoAnterior = $this->anoAtualPrincipal->anoAnterior();
    $ano_lectivo = $anoCorrente;
    $estado_grade_aluno=4;

    if ($fatura->codigo_descricao == 3 || $fatura->codigo_descricao == 1) { //inscricoes de cadeiras anuais e semestrais

      if($fatura->codigo_descricao == 1){
        $estado_grade_aluno=2;
      }
  
      $dado = $this->eliminarConfirmacoesCadeiras($fatura->CodigoMatricula,$estado_grade_aluno,$fatura->codigo_descricao);


    }elseif($fatura->codigo_descricao == 6 || $fatura->codigo_descricao == 7 || $fatura->codigo_descricao == 8){ // inscricoes de avaliacaoes. 6- recurso/7-melhoria/8-exame especial

      if($fatura->codigo_descricao==7 || $fatura->codigo_descricao==8){ // se foram facturas de melhoria ou exame especial o ano escolhido é o ano anterior( regra de negocio. Ver com Onesimo)

        $ano_lectivo = $anoAnterior;


      }

      $dado = $this->eliminarInscricoesAvaliacoes($ano_lectivo,$fatura->codigo_descricao,$fatura->Codigo);

    }

    return $dado;
  }


  public function eliminarConfirmacoesCadeiras($codigo_matricula,$estado_grade_aluno,$codigo_descricao_factura) // eliminar inscricoes de cadeiras anuais e semestrais
  {

    $anoCorrente = $this->anoAtualPrincipal->index();



    $dados = DB::table('tb_grade_curricular')
      ->join(
        'tb_grade_curricular_aluno',
        'tb_grade_curricular_aluno.codigo_grade_curricular',
        '=',
        'tb_grade_curricular.Codigo'
      )
      ->join('tb_tipo_servicos', 'tb_tipo_servicos.codigo_grade_currilular', '=', 'tb_grade_curricular.Codigo')
      ->join('factura_items', 'factura_items.CodigoProduto', '=', 'tb_tipo_servicos.Codigo')
      ->join('factura', 'factura_items.CodigoFactura', '=', 'factura.Codigo')
      ->join('pagamento_por_referencias', 'pagamento_por_referencias.factura_codigo', '=', 'factura.Codigo')
      ->whereIn('pagamento_por_referencias.Status', ['EXPIRED', 'CANCELED'])
      //->where('factura.Codigo', $codigo_fatura)
      ->where('factura.codigo_descricao', $codigo_descricao_factura) // para garantir o codigo_descricao 3 ou 1
      ->where('factura.CodigoMatricula', $codigo_matricula)
      ->where('factura.ano_lectivo', $anoCorrente)
      ->where('tb_grade_curricular_aluno.codigo_matricula', $codigo_matricula)
      ->where('tb_grade_curricular_aluno.codigo_ano_lectivo', $anoCorrente)
      ->where('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', $estado_grade_aluno)
      ->select('tb_grade_curricular_aluno.*')->get();




    if (sizeOf($dados) > 0) {

      foreach ($dados as $key => $value) {



        try {
          GradeCurricularAluno::find($value->codigo)->delete();
        } catch (\Exception $ex) {

          DB::rollback();
          return false;
        }
        try {
          Confirmacao::find($value->codigo_confirmacao)->delete();
        } catch (\Exception $ex) {

          DB::rollback();
          return false;
        }
      }

      return true;
    } else {

      return false;
    }
  }

  public function eliminarInscricoesAvaliacoes($ano_lectivo, $codigo_descricao_factura, $codigo_factura)
  { // para facturas de inscricoes de avaliacoes





    $dados = DB::table('inscricao_avaliacoes')
      ->join('factura', 'inscricao_avaliacoes.codigo_factura', '=', 'factura.Codigo')
      ->join('pagamento_por_referencias', 'pagamento_por_referencias.factura_codigo', '=', 'factura.Codigo')
      ->whereIn('pagamento_por_referencias.Status', ['EXPIRED', 'CANCELED'])
      //->where('factura.Codigo', $codigo_fatura)
      ->where('factura.codigo_descricao', $codigo_descricao_factura)
      ->where('factura.Codigo', $codigo_factura)
      ->where('inscricao_avaliacoes.codigo_ano_lectivo', $ano_lectivo)
      ->where('inscricao_avaliacoes.estado', 'pendente')
      //->where('factura.CodigoMatricula', $codigo_matricula) // so pra garantir
      ->where('factura.ano_lectivo', $ano_lectivo)
      ->select('inscricao_avaliacoes.*')->get();




    //DB::beginTransaction();




    if (sizeOf($dados) > 0) {

      foreach ($dados as $key => $value) {



        try {
          DB::table('inscricao_avaliacoes')->where('codigo',$value->codigo)->update(['estado' => 'anulado']);
        } catch (\Exception $ex) {
          //throw $ex;
          //DB::rollback();
          return false;
          // return response()->json("Erro ao eliminar cadeiras", 500);
        }

      }

      return true;
    } else {

      return false;
    }
    //DB::commit();


  }


  //Vincular directamente a prova ao candidato com o pagamento da taixa de inscrição validado
  public function vincularProvaExameAcessoAoCandidato($factura_codigo)
  {
    $vinculacao=false;

    $user = auth()->user();
    $factura= Factura::find($factura_codigo);
    $preinscricao = Candidato::find($factura->codigo_preinscricao);

    if ($factura->codigo_descricao==9) {
      //->inRandomOrder()->first();

      

      $prova = Prova::where('ano_lectivo_id', $preinscricao->anoLectivo)
        ->whereJsonContains('cursos', [$preinscricao->Curso_Candidatura])
        ->inRandomOrder()->first();
 

      //Pocurando por um horário livre
      $horarioProva = HorarioProva::where('ano_lectivo_id', $preinscricao->anoLectivo)
        ->where('curso_id', $preinscricao->Curso_Candidatura)
        //->where('data_realizacao', '>', date('Y-m-d'))
        ->where('periodo_id', $preinscricao->Codigo_Turno)
        ->withCount('candidatoProvas')
        ->with('sala')
        ->orderBy('data_realizacao')
        ->orderBy('hora_inicio')
        ->get()->filter(function ($horario) {
          return $horario->candidato_provas_count < $horario->sala->capacidadeExameAcessoProva;
        })->first();


      if ($horarioProva && $prova) {
        //dd($horarioProva);
        $vinculacao=CandidatoProva::updateOrCreate([ 'candidato_id' => $preinscricao->Codigo],[
          'candidato_id' => $preinscricao->Codigo,
          'prova_id' => $prova->id ?? null,
          'horario_prova_id' => $horarioProva->id ?? null
        ]);
      } else {
        $data_do_curso = HorarioProva::where('curso_id', $preinscricao->Codigo_Turno)->first()->data_realizacao;
        //colocá-lo em qualquer sala do turno do candidato, não importa o curso quando as salas já estiverem lotadas
        $horarioProva = HorarioProva::where('ano_lectivo_id', $preinscricao->anoLectivo)
          ->where('data_realizacao', $data_do_curso)
          ->where('data_realizacao', '>', date('Y-m-d'))
          ->where('periodo_id', $preinscricao->Codigo_Turno)
          ->withCount('candidatoProvas')
          ->with('sala')
          ->orderBy('hora_inicio')
          ->get()->filter(function ($horario) {
              return $horario->candidato_provas_count < $horario->sala->capacidadeExameAcessoProva;
          })->first();

        if ($horarioProva && $prova) {
          
          $vinculacao=CandidatoProva::updateOrCreate([ 'candidato_id' => $preinscricao->Codigo],[
            'candidato_id' => $preinscricao->Codigo,
            'prova_id' => $prova->id ?? null,
            'horario_prova_id' => $horarioProva->id ?? null
          ]); 
        }
      }
    }



    return $vinculacao;

  }

  //************   VALIDACAO DE CADEIRAS ************** */


  public function validarCadeirasParaConfirmacao($codigo_matricula) //validar  inscricoes de cadeiras anuais e semestrais- update do estado da Codigo_Status_Grade_Curricular para 2
  {

    $anoCorrente = $this->anoAtualPrincipal->index();



    $dados = DB::table('tb_grade_curricular')
      ->join(
        'tb_grade_curricular_aluno',
        'tb_grade_curricular_aluno.codigo_grade_curricular',
        '=',
        'tb_grade_curricular.Codigo'
      )
      ->join('tb_tipo_servicos', 'tb_tipo_servicos.codigo_grade_currilular', '=', 'tb_grade_curricular.Codigo')
      ->join('factura_items', 'factura_items.CodigoProduto', '=', 'tb_tipo_servicos.Codigo')
      ->join('factura', 'factura_items.CodigoFactura', '=', 'factura.Codigo')
      ->join('pagamento_por_referencias', 'pagamento_por_referencias.factura_codigo', '=', 'factura.Codigo')
      ->where('pagamento_por_referencias.Status', 'PAID')
      //->where('factura.Codigo', $codigo_fatura)
      ->where('factura.codigo_descricao', 3)
      ->where('factura.CodigoMatricula', $codigo_matricula)
      ->where('factura.ano_lectivo', $anoCorrente)
      ->where('tb_grade_curricular_aluno.codigo_matricula', $codigo_matricula)
      ->where('tb_grade_curricular_aluno.codigo_ano_lectivo', $anoCorrente)
      ->where('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', 4)
      ->select('tb_grade_curricular_aluno.*')->get();



    if (sizeOf($dados) > 0) {

      foreach ($dados as $key => $value) {



        try {
          GradeCurricularAluno::find($value->codigo)->update([
            'Codigo_Status_Grade_Curricular' => 2
          ]);
        } catch (\Exception $ex) {

          DB::rollback();
          return false;
        }
       /*  try {
          Confirmacao::find($value->codigo_confirmacao)->update([]);
        } catch (\Exception $ex) {

          DB::rollback();
          return false;
        } */
      }

      return true;
    } else {

      return false;
    }

  }
  public function validarInscricoesAvaliacoes($codigo_factura) // para facturas de inscricoes de avaliacoes
  {
    $dados = DB::table('inscricao_avaliacoes')
    ->join('factura', 'inscricao_avaliacoes.codigo_factura', '=', 'factura.Codigo')
    ->join('pagamento_por_referencias', 'pagamento_por_referencias.factura_codigo', '=', 'factura.Codigo')
    ->where('pagamento_por_referencias.Status', 'PAID')
    //->where('factura.Codigo', $codigo_fatura)
    //->where('factura.codigo_descricao', $codigo_descricao_factura)
    ->where('factura.Codigo', $codigo_factura)
    //->where('inscricao_avaliacoes.codigo_ano_lectivo', $ano_lectivo)
    ->where('inscricao_avaliacoes.estado', 'pendente')
    //->where('factura.CodigoMatricula', $codigo_matricula) // so pra garantir
    //->where('factura.ano_lectivo', $ano_lectivo)
    ->select('inscricao_avaliacoes.*')->get();




  //DB::beginTransaction();




  if (sizeOf($dados) > 0) {

    foreach ($dados as $key => $value) {



      try {
        DB::table('inscricao_avaliacoes')->where('codigo',$value->codigo)->update(['estado' => 'validado']);
      } catch (\Exception $ex) {
        //throw $ex;
        //DB::rollback();
        return false;
        // return response()->json("Erro ao eliminar cadeiras", 500);
      }

    }

    return true;
  } else {

    return false;
  }
  //DB::commit();

  }

  public function validarCadeiras($codigo_fatura) // funcao generica para validar cadeiras
  {

    $dado=false;
    $fatura = DB::table('factura')->where('Codigo', $codigo_fatura)->first();
   /*  $anoCorrente = $this->anoAtualPrincipal->index();
    $anoAnterior = $this->anoAtualPrincipal->anoAnterior();
    $ano_lectivo = $anoCorrente; */
    if ($fatura->codigo_descricao == 3) { //inscricoes de cadeiras anuais e semestrais

      $dado = $this->validarCadeirasParaConfirmacao($fatura->CodigoMatricula);


    }elseif($fatura->codigo_descricao == 6 || $fatura->codigo_descricao == 7 || $fatura->codigo_descricao == 8){ // inscricoes de avaliacaoes. 6- recurso/7-melhoria/8-exame especial

      /*if($fatura->codigo_descricao==7 || $fatura->codigo_descricao==8){ // se foram facturas de melhoria ou exame especial o ano escolhido é o ano anterior( regra de negocio. Ver com Onesimo)

        $ano_lectivo = $anoAnterior;


      }*/

      $dado = $this->validarInscricoesAvaliacoes($fatura->Codigo);

    }

    return $dado;

  }
}
