<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Categoria;
use Illuminate\Support\Facades\DB;
use App\LogAcesso;
use Illuminate\Support\Facades\Auth;

class Extenso
{
  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */




  public function index($v)
  {



    $v = filter_var($v, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    $sin = array("centavo", "kwanzas", "mil", "milhão", "bilhão", "trilhão", "quatrilhão");
    $plu = array("centavos", "kwanzas", "mil", "milhões", "bilhões", "trilhões", "quatrilhões");

    $c = array("", "cem", "duzentos", "trezentos", "quatrocentos", "quinhentos", "seiscentos", "setecentos", "oitocentos", "novecentos");
    $d = array("", "dez", "vinte", "trinta", "quarenta", "cinquenta", "sessenta", "setenta", "oitenta", "noventa");
    $d10 = array("dez", "onze", "doze", "treze", "quatorze", "quinze", "dezesseis", "dezesete", "dezoito", "dezanove");
    $u = array("", "um", "dois", "três", "quatro", "cinco", "seis", "sete", "oito", "nove");

    $z = 0;

    $v = number_format($v, 2, ".", ".");
    $int = explode(".", $v);

    for ($i = 0; $i < count($int); $i++) {
      for ($ii = mb_strlen($int[$i]); $ii < 3; $ii++) {
        $int[$i] = "0" . $int[$i];
      }
    }

    $rt = null;
    $fim = count($int) - ($int[count($int) - 1] > 0 ? 1 : 2);
    for ($i = 0; $i < count($int); $i++) {
      $v = $int[$i];
      $rc = (($v > 100) && ($v < 200)) ? "cento" : $c[$v[0]];
      $rd = ($v[1] < 2) ? "" : $d[$v[1]];
      $ru = ($v > 0) ? (($v[1] == 1) ? $d10[$v[2]] : $u[$v[2]]) : "";

      $r = $rc . (($rc && ($rd || $ru)) ? " e " : "") . $rd . (($rd && $ru) ? " e " : "") . $ru;
      $t = count($int) - 1 - $i;
      $r .= $r ? " " . ($v > 1 ? $plu[$t] : $sin[$t]) : "";
      if ($v == "000")
        $z++;
      elseif ($z > 0)
        $z--;

      if (($t == 1) && ($z > 0) && ($int[0] > 0))
        $r .= (($z > 1) ? " de " : "") . $plu[$t];

      if ($r)
        $rt = $rt . ((($i > 0) && ($i <= $fim) && ($int[0] > 0) && ($z < 1)) ? (($i < $fim) ? ", " : " e ") : " ") . $r;
    }

    $rt = mb_substr($rt, 1);

    $extenso = $rt ? trim($rt) : "zero";
    return  $extenso;
  }

  public function finalista($user_id)
  {
    $id = $user_id;
    $collection = collect([]);

    $cadeirasRestantes = 0;
    $aluno = DB::table('tb_matriculas')
      ->join('tb_admissao', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
      ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
      ->where('tb_preinscricao.user_id', $id)
      ->select('tb_matriculas.Codigo as matricula', 'tb_matriculas.Codigo_Curso as curso_matricula', 'tb_preinscricao.Curso_Candidatura as curso_preinscricao')
      ->first();



    $planoCurricular = DB::table('tb_grade_curricular')->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')
      ->where('tb_grade_curricular.Codigo_Curso', $aluno->curso_matricula)
      ->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina', 'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano', 'tb_duracao.codigo as codigo_duracao')
      ->distinct('disciplina')
      ->whereIn('tb_grade_curricular.status', [1, 2])
      ->get()
      ->count();

    $collection = collect([]);


    //dd($planoCurricular);
    $planoCurricular1 = DB::table('tb_grade_curricular')->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')
      ->where('tb_grade_curricular.Codigo_Curso', $aluno->curso_preinscricao)
      ->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina', 'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano', 'tb_duracao.codigo as codigo_duracao')
      ->distinct('disciplina')->whereIn('tb_grade_curricular.status', [1, 2])->get()->count();

    $cadeirasEliminadas1 = DB::table('tb_grade_curricular')
    ->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')
    ->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')
    ->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')
    ->join('tb_grade_curricular_aluno', 'tb_grade_curricular_aluno.codigo_grade_curricular', '=', 'tb_grade_curricular.Codigo')
    ->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')
    ->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')
    ->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina',
    'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano', 'tb_grade_curricular.valor_inscricao as valor_cadeira', 'tb_duracao.codigo as codigo_duracao')
    ->where('tb_grade_curricular.Codigo_Curso', $aluno->curso_preinscricao)
    ->where('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', 3)
    ->where('tb_grade_curricular_aluno.codigo_matricula', $aluno->matricula)
    ->whereIn('tb_grade_curricular.status', [1, 2])->distinct('disciplina')->get()->count();


    $cadeirasEliminadas = DB::table('tb_grade_curricular')
    ->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')
    ->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')
    ->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')
    ->join('tb_grade_curricular_aluno', 'tb_grade_curricular_aluno.codigo_grade_curricular', '=', 'tb_grade_curricular.Codigo')
    ->join('tb_duracao', 'tb_duracao.codigo', '=', 'tb_disciplinas.duracao')
    ->join('tb_semestres', 'tb_grade_curricular.Codigo_Semestre', '=', 'tb_semestres.Codigo')
    ->select('tb_disciplinas.Designacao as disciplina', 'tb_semestres.Designacao as semestre', 'tb_classes.Designacao as classe', 'tb_duracao.designacao as duracao_disciplina',
    'tb_grade_curricular.Codigo as codigo_grade', 'tb_classes.Codigo as codigo_ano', 'tb_grade_curricular.valor_inscricao as valor_cadeira', 'tb_duracao.codigo as codigo_duracao')
    ->where('tb_grade_curricular.Codigo_Curso', $aluno->curso_matricula)->where('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', 3)
    ->whereIn('tb_grade_curricular.status', [1, 2])->where('tb_grade_curricular_aluno.codigo_matricula', $aluno->matricula)->distinct('disciplina')->get()->count();

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

    //dd("Noy");

    if ($aluno) {
      if (($aluno->curso_preinscricao == 1 || $aluno->curso_preinscricao == 5 || $aluno->curso_preinscricao == 9 || $aluno->curso_matricula == 28 || $aluno->curso_matricula == 29 || $aluno->curso_matricula == 30 || $aluno->curso_matricula == 31 || $aluno->curso_matricula == 32 || $aluno->curso_matricula == 33 || $aluno->curso_matricula == 34 || $aluno->curso_matricula == 35)) { //SE O ALUNO ESTA INSCRITO EM UM CURSO DE ESPECIALIDADE
        if (($aluno->curso_preinscricao == 1 || $aluno->curso_preinscricao == 5 || $aluno->curso_preinscricao == 9) && ($aluno->curso_preinscricao == $aluno->curso_matricula)) {


          $cadeirasRestantes = $planoCurricular - $cadeirasEliminadas;
          $cadeirasRestantes = $cadeirasRestantes + $cadeirasEliminadaAnoCorrente;
          //dd($cadeirasEliminadas);

        } elseif ($aluno->curso_matricula == 28 || $aluno->curso_matricula == 29 || $aluno->curso_matricula == 30 || $aluno->curso_matricula == 31 || $aluno->curso_matricula == 32 || $aluno->curso_matricula == 33 || $aluno->curso_matricula == 34 || $aluno->curso_matricula == 35) {

          if ($aluno->curso_preinscricao != $aluno->curso_matricula) {
            $cadeirasRestantes = ($planoCurricular1 + $planoCurricular) - ($cadeirasEliminadas + $cadeirasEliminadas1);
            $cadeirasRestantes = $cadeirasRestantes + $cadeirasEliminadaAnoCorrente + $cadeirasEliminadaAnoCorrente1;
          }
        } else {
          //ESTUDANTE EMIGRADO
          $cadeirasRestantes = $planoCurricular - $cadeirasEliminadas;
          $cadeirasRestantes = $cadeirasRestantes + $cadeirasEliminadaAnoCorrente;
        }
      } else { // SE O ALUNO NAO ESTA INSCRITO EM UM CURSO DE ESPECIALIDADE

        $cadeirasRestantes = $planoCurricular - $cadeirasEliminadas;
        $cadeirasRestantes = $cadeirasRestantes + $cadeirasEliminadaAnoCorrente;
      }
    }

    return $cadeirasRestantes;
  }


  public function moeda_extenso($moeda, $letra)
  {
    $valor = $moeda;
    $maiusculas = $letra;
    if (!$maiusculas) {
      $singular = ["cêntimo", "kwanza", "mil", "milhão", "bilhão", "trilhão", "quatrilhão"];
      $plural = ["cêntimos", "kwanzas", "mil", "milhões", "bilhões", "trilhões", "quatrilhões"];
      $u = ["", "um", "dois", "três", "quatro", "cinco", "seis",  "sete", "oito", "nove"];
    } else {
      $singular = ["CÊNTIMO", "KWANZA", "MIL", "MILHÃO", "BILHÃO", "TRILHÃO", "QUADRILHÃO"];
      $plural = ["CÊNTIMOS", "KWANZAS", "MIL", "MILHÕES", "BILHÕES", "TRILHÕES", "QUADRILHÕES"];
      $u = ["", "um", "dois", "três", "quatro", "cinco", "seis",  "sete", "oito", "nove"];
    }

    $c = ["", "cem", "duzentos", "trezentos", "quatrocentos", "quinhentos", "seiscentos", "setecentos", "oitocentos", "novecentos"];
    $d = ["", "dez", "vinte", "trinta", "quarenta", "cinquenta", "sessenta", "setenta", "oitenta", "noventa"];
    $d10 = ["dez", "onze", "doze", "treze", "quatorze", "quinze", "dezesseis", "dezesete", "dezoito", "dezenove"];

    $z = 0;
    $rt = "";

    $valor = number_format($valor, 2, ".", ".");
    $inteiro = explode(".", $valor);
    for ($i = 0; $i < count($inteiro); $i++)
      for ($ii = strlen($inteiro[$i]); $ii < 3; $ii++)
        $inteiro[$i] = "0" . $inteiro[$i];

    $fim = count($inteiro) - ($inteiro[count($inteiro) - 1] > 0 ? 1 : 2);
    for ($i = 0; $i < count($inteiro); $i++) {
      $valor = $inteiro[$i];
      $rc = (($valor > 100) && ($valor < 200)) ? "cento" : $c[$valor[0]];
      $rd = ($valor[1] < 2) ? "" : $d[$valor[1]];
      $ru = ($valor > 0) ? (($valor[1] == 1) ? $d10[$valor[2]] : $u[$valor[2]]) : "";

      $r = $rc . (($rc && ($rd || $ru)) ? " e " : "") . $rd . (($rd &&
        $ru) ? " e " : "") . $ru;
      $t = count($inteiro) - 1 - $i;
      $r .= $r ? " " . ($valor > 1 ? $plural[$t] : $singular[$t]) : "";
      if ($valor == "000") $z++;
      elseif ($z > 0) $z--;
      if (($t == 1) && ($z > 0) && ($inteiro[0] > 0)) $r .= (($z > 1) ? " de " : "") . $plural[$t];
      if ($r) $rt = $rt . ((($i > 0) && ($i <= $fim) && ($inteiro[0] > 0) && ($z < 1)) ? (($i < $fim) ? ", " : " e ") : " ") . $r;
    }

    if (!$maiusculas) {
      $return = $rt ? $rt : "zero";
    } else {
      if ($rt) $rt = mb_ereg_replace(" E ", " e ", ucwords($rt));
      $return = ($rt) ? ($rt) : "Zero";
    }

    if (!$maiusculas) {
      if (mb_ereg_replace(" E ", " e ", ucwords($return)) == " Um Mil Kwanzas") {
        return " Mil Kwanzas";
      } else {
        return  mb_ereg_replace(" E ", " e ", ucwords($return));
      }
    } else {
      if (strtoupper($return) == " UM MIL KWANZAS") {
        return " Mil Kwanzas";
      } else {
        return  strtoupper($return);
      }
    }
  }
  /**
   * Show the form for creating a new resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function create()
  {
    //
  }

  /**
   * Store a newly created resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function store(Request $request)
  {
    //
  }

  /**
   * Display the specified resource.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function show($id)
  {
    //
  }

  /**
   * Show the form for editing the specified resource.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function edit($id)
  {
    //
  }

  /**
   * Update the specified resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function update(Request $request, $id)
  {
    //
  }

  /**
   * Remove the specified resource from storage.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function destroy($id)
  {
    //
  }
}
