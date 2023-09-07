<?php

namespace App\Services;

use App\GradeCurricularAluno;
use App\Turma;
use App\Candidato;
use Illuminate\Support\Facades\Http;
use App\Repositories\AlunoRepository;
use App\Services\Api\Turma\TurmaApiService;
use DB;
use App\Http\Controllers\ClassesAuxiliares\anoAtual;

class TurmaService
{
  public $alunoRepository;
  public $aluno;
  public $anoAtualPrincipal;
  public $turmaApiService;

  public function __construct()
  {


    $this->alunoRepository = new AlunoRepository();
    $this->anoAtualPrincipal = new anoAtual();
    $this->turmaApiService = new TurmaApiService();
  }

  public function removeData()
  {
  }

  public function nrVagasTurma($turma_id, $grade_id)
  {
    // try{
    $client = new \GuzzleHttp\Client();

   // $request = $client->get($this->turmaApiService->nrVagasTurma($turma_id, $grade_id));
    //$request = $client->get('10.10.6.250:8080/mutue//inscricoes/verificar_espaco?turma='.$turma_id.'&grade='.$grade_id.'');
    //dd($request);
    //$response = json_decode($request->getBody());

    return $response="";

    /*}
    catch (GuzzleHttp\Exception\ClientException $e) {
    $response = $e->getResponse();
    $responseBodyAsString = $response->getBody()->getContents();
    dd($response);
}*/
  }
  public function nrVagasTurmaBackup($turma_id, $grade_id)
  {
    $vaga = GradeCurricularAluno::where('turma', $turma_id)->where('codigo_grade_curricular', $grade_id)->get()->count();

    return $vaga;
  }
  public function temVagaTurma($turma_id, $grade_id)
  {

    $temVaga = false;
    $nrVaga = $this->nrVagasTurma($turma_id, $grade_id);
    $vaga = Turma::whereHas('sala', function ($query) use ($nrVaga) {
      $query->where('capacidade', '>', $nrVaga);
    })->where('Codigo', $turma_id)->first();

    if ($vaga) { // se tiver vaga
      $temVaga = true;
    }

    return $temVaga;
  }

  public function pegaHorarioTurma($ano_lectivo,$semestre,$codigo_grade,$curso) // horario da grade - nova abordagem
  {

    $client = new \GuzzleHttp\Client();
    //17,2,7,18

    //dd($ano_lectivo,$semestre,$codigo_grade,$curso);
    $request = $client->get($this->turmaApiService->pegaHorarioTurma($ano_lectivo,$semestre,$codigo_grade,$curso));
    
    $response = json_decode($request->getBody());

    return $response;
  }

  public function pegaHorarioSelecao($array)
  {

    $aluno = $this->alunoRepository->dadosAlunoLogado();
    $client = new \GuzzleHttp\Client();

    $url = $this->turmaApiService->pegaHorarioSelecao($aluno->matricula);
    
    $request = $client->post($url,  ['body' => $array]);

    $response = json_decode($request->getBody());

    return $response;
  }
  public function verColisaoHorario($array, $pk_horario)
  {

    
    $client = new \GuzzleHttp\Client();
    $url = $this->turmaApiService->verColisaoHorario($pk_horario);
    
    //$myBody['name'] = "Demo";
    //$array='[{ "pk": 49, "desc": "AGT.1.AS-H1", "corLetra": "black" },{ "pk": 9, "desc": "AGT.1.AS-H2", "corLetra": "black" }]';
    $request = $client->post($url,  ['form_params' => $array]);
 
   //form_params -  para mandar array
   //body -  para mandar json



    $response = json_decode($request->getBody());
 
    return $response;
  }

  public function cursoPermitido($classe_id, $curso_id, $turno_id, $matricula_id, $grade_curricular_id)
  {
    // turno 0 - todos, porque o lado do JP eles teriam que mexer no codigo se colocassemos um registro todos na tabela turnos, entao colocamos o 0 como default
    // na tabela cursos_selecao_h_turnos_diferentes

    $permitido = DB::table('alunos_selecao_h_turnos_diferentes')->where('codigo_matricula', $matricula_id)->where('estado', 1)->where('deleted_at', null)->first();


    if (!$permitido) {

      $permitido1 = DB::table('cursos_selecao_h_turnos_diferentes')->whereIn('codigo_curso', [$curso_id, 26])
        ->whereIn('codigo_ano_curricular', [$classe_id, 7])->whereIn('turno_id', [$turno_id, 0])->where('grade_curricular_id', 0)->where('estado', 1)->where('deleted_at', null)->first();

      $permitido = $permitido1;
    }

    if (!$permitido) {

      $permitido = DB::table('cursos_selecao_h_turnos_diferentes')->where('grade_curricular_id', $grade_curricular_id)->where('estado', 1)->where('deleted_at', null)->first();
    }



    return  $permitido;
  }
  public function getTurmasByCursoId($ano_id, $curso_id, $classe_id, $turno_id, $curso_preinscricao, $matricula_id, $grade_curricular_id)
  {

  /*   if ($turno_id == 1 || $turno_id == 2) {

      $turno_id = 5;
    } elseif ($turno_id == 3) {

      $turno_id = 6;
    } */
/* 
    $curso_permitido = $this->cursoPermitido($classe_id, $curso_id, $turno_id, $matricula_id, $grade_curricular_id);

    $condicoes = [];
    if (!$curso_permitido) {

      array_push($condicoes, ['Codigo_Periodo', $turno_id]);
    } */
    // ano lectivo da turma, curso, classe 
    $turmas = Turma::where('Codigo_Curso', $curso_id)
      ->where('Codigo_Classe', $classe_id)
      ->where('apenasPrimeiroAno', 0) ///para nao trazer turmas reservadas ao primeiro ano
      ->where('Codigo_AnoLectivo', $ano_id)
      ->/*where($condicoes)->*/select('Codigo', 'Designacao')->get();
    //
    if (sizeOf($turmas) == 0) {

     /*  $curso_permitido = $this->cursoPermitido($classe_id, $curso_id, $turno_id, $matricula_id, $grade_curricular_id);
      $condicoes = [];
      if (!$curso_permitido) {

        array_push($condicoes, ['Codigo_Periodo', $turno_id]);
      } */
      // ano lectivo da turma, curso, classe 
      $turmas = Turma::where('Codigo_Curso', $curso_preinscricao)
        ->where('Codigo_Classe', $classe_id)
        ->where('apenasPrimeiroAno', 0) ///para nao trazer turmas reservadas ao primeiro ano
        ->where('Codigo_AnoLectivo', $ano_id)
        ->/*where($condicoes)->*/select('Codigo', 'Designacao')->get();
      // replicacao, evitar isso*/


      //parametrizacao a entrar em vigor em breve

    }


    return $turmas;
  }


  public function pegaCursos()
  {

    $user = auth()->user();
    //


    if ($user->hasRole('Admin')) {
      $cursos = DB::table('tb_cursos')->select('Codigo as id', 'Designacao as designacao')->orderBy('designacao')->get();
    } elseif ($user->hasRole('Director de Curso')) {

      $cursos = DB::table('tb_cursos')->join('tb_curso_permitido', 'tb_curso_permitido.curso_id', '=', 'tb_cursos.Codigo')->select('tb_cursos.Codigo as id', 'tb_cursos.Designacao as designacao')->where('user_id', $user->id)->distinct('designacao')->orderBy('designacao')->get();
    } else {

      $cursos = [];
    }


    return $cursos;
  }

  public function pegaTurnos()
  {

    $collection = collect([]);
    $collection1 = collect([]);
    $turnos = DB::table('tb_periodos')->select('Codigo as id', 'Designacao as designacao')->where('status', 1)->orderBy('designacao')->get();
    /*$maiorId=DB::table('tb_periodos')->where('status',1)->orderBy('Codigo','desc')->get()->pluck('Codigo')->first();
  
    foreach($turnos as $key=> $turno){

     $collection->push(['turno_id'=>$turno->turno_id,'turno'=>$turno->turno]);  
 

    }
    
    $collection1->push(['turno_id'=>$maiorId+1,'turno'=>'Todos']);

    $dados = $collection->mergeRecursive($collection1);*/

    return $turnos;
  }
  public function pegaAnosCurriculares()
  {

    $anos = DB::table('tb_classes')->select('Codigo as id', 'Designacao as designacao')->orderBy('designacao')->get();

    return $anos;
  }


  public function pegaGradesCurriculares($curso_id = 26, $ano_curricular_id = 7)
  {
    // curso 26 e ano 7 são todos
    $condicoes = [];
    if ($curso_id && $curso_id != 26) {

      array_push($condicoes, ['tb_cursos.Codigo', $curso_id]);
    }
    if ($ano_curricular_id && $ano_curricular_id != 7) {

      array_push($condicoes, ['tb_classes.Codigo', $ano_curricular_id]);
    } elseif ($curso_id == 26 && $ano_curricular_id == 7) {

      $condicoes = [];
    }
    $grades = DB::table('tb_grade_curricular')
      ->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')
      ->join('tb_classes', 'tb_classes.Codigo', '=', 'tb_grade_curricular.Codigo_Classe')
      ->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_grade_curricular.Codigo_Curso')
      ->where($condicoes)
      // ->where('disciplina','!=',)
      ->select('tb_disciplinas.Designacao as disciplina', 'tb_grade_curricular.Codigo as codigo_grade')
      ->distinct('disciplina')->orderBy('disciplina')->get();

    return $grades;
  }

  // cursos permitidos para selecao de turmas de cursos diferentes
  public function cursosPermitidos($palavra_chave)
  {

    $condicoes = [];
    $condicoes1 = [];
    $condicoes2 = [];
    $condicoes3 = [];
    $condicoes4 = [];
    if (!empty($palavra_chave)) {
      array_push($condicoes, ['tb_disciplinas.Designacao', 'LIKE', '%' . $palavra_chave . '%']);
      array_push($condicoes1, ['tb_classes.Designacao', 'LIKE', '%' . $palavra_chave . '%']);
      array_push($condicoes2, ['tb_cursos.Designacao', 'LIKE', '%' . $palavra_chave . '%']);
      array_push($condicoes3, ['tb_periodos.Designacao', 'LIKE', '%' . $palavra_chave . '%']);
      array_push($condicoes4, ['users.name', 'LIKE', '%' . $palavra_chave . '%']);
    }
    $cursos = DB::table('cursos_selecao_h_turnos_diferentes')
      ->leftJoin('tb_grade_curricular', 'tb_grade_curricular.Codigo', '=', 'cursos_selecao_h_turnos_diferentes.grade_curricular_id')
      ->leftJoin('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_grade_curricular.Codigo_Disciplina')
      ->join('tb_classes', 'tb_classes.Codigo', '=', 'cursos_selecao_h_turnos_diferentes.codigo_ano_curricular')
      ->join('tb_cursos', 'tb_cursos.Codigo', '=', 'cursos_selecao_h_turnos_diferentes.codigo_curso')
      ->leftJoin('tb_periodos', 'tb_periodos.Codigo', '=', 'cursos_selecao_h_turnos_diferentes.turno_id')
      ->join('users', 'users.id', '=', 'cursos_selecao_h_turnos_diferentes.user_id')
      ->where('deleted_at', null)
      ->where($condicoes)->orWhere($condicoes1)->orWhere($condicoes2)->orWhere($condicoes3)->orWhere($condicoes4)
      // ->where('disciplina','!=',)
      ->select(
        'tb_disciplinas.Designacao as disciplina',
        'tb_classes.Designacao as ano_curricular',
        'tb_cursos.Designacao as curso',
        'cursos_selecao_h_turnos_diferentes.codigo as codigo',
        'tb_periodos.Designacao as turno',
        'users.name as utilizador',
        'tb_periodos.Codigo as codigo_turno',
        'tb_grade_curricular.Codigo as codigo_grade',
        'cursos_selecao_h_turnos_diferentes.estado as estado',
        'cursos_selecao_h_turnos_diferentes.created_at as data'
      )
      ->orderBy('cursos_selecao_h_turnos_diferentes.codigo', 'desc')->paginate(10);


    return $cursos;
  }

  public function alunosPermitidos($palavra_chave)
  {

    $condicoes = [];
    $condicoes1 = [];

    if (!empty($palavra_chave)) {
      array_push($condicoes, ['tb_preinscricao.Nome_Completo', 'LIKE', '%' . $palavra_chave . '%']);
      array_push($condicoes1, ['users.name', 'LIKE', '%' . $palavra_chave . '%']);
    }
    $alunos = DB::table('alunos_selecao_h_turnos_diferentes')
      ->join('tb_matriculas', 'tb_matriculas.Codigo', '=', 'alunos_selecao_h_turnos_diferentes.codigo_matricula')
      ->join('tb_admissao', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
      ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
      ->join('users', 'users.id', '=', 'alunos_selecao_h_turnos_diferentes.user_id')
      ->where('deleted_at', null)
      ->where($condicoes)->orWhere($condicoes1)
      // ->where('disciplina','!=',)
      ->select(
        'tb_preinscricao.Nome_Completo as nome',
        'users.name as utilizador',
        'alunos_selecao_h_turnos_diferentes.estado as estado',
        'alunos_selecao_h_turnos_diferentes.codigo as codigo',
        'alunos_selecao_h_turnos_diferentes.created_at as data'
      )
      ->orderBy('alunos_selecao_h_turnos_diferentes.codigo', 'desc')->paginate(10);


    return $alunos;
  }



  public function ativarPermissao($codigo, $opcoes)
  {
    try {

      if (!$opcoes) {
        $curso = DB::table('cursos_selecao_h_turnos_diferentes')->where('codigo', $codigo)->first();

        if ($curso) {

          DB::table('cursos_selecao_h_turnos_diferentes')->where('codigo', $codigo)->update(['updated_at' => date('Y-m-d H:i:s'), 'estado' => 1]);
          return 'Permissão activada com sucesso!';
        } else {

          return 201;
        }
      } else if ($opcoes) {

        $aluno = DB::table('alunos_selecao_h_turnos_diferentes')->where('codigo', $codigo)->first();

        if ($aluno) {

          DB::table('alunos_selecao_h_turnos_diferentes')->where('codigo', $codigo)->update(['updated_at' => date('Y-m-d H:i:s'), 'estado' => 1]);
          return 'Permissão do estudante activada com sucesso!';
        } else {

          return 201;
        }
      }
    } catch (\Illuminate\Database\QueryException $e) {


      //return Response()->json($e->getMessage());

      return 201;
    }
  }
  public function desativarPermissao($codigo, $opcoes)
  {

    try {
      if (!$opcoes) {

        $curso = DB::table('cursos_selecao_h_turnos_diferentes')->where('codigo', $codigo)->first();

        if ($curso) {

          DB::table('cursos_selecao_h_turnos_diferentes')->where('codigo', $codigo)->update(['updated_at' => date('Y-m-d H:i:s'), 'estado' => 0]);
          return 'Permissão desactivada com sucesso!';
        } else {

          return 201;
        }
      } else if ($opcoes) {

        $aluno = DB::table('alunos_selecao_h_turnos_diferentes')->where('codigo', $codigo)->first();

        if ($aluno) {

          DB::table('alunos_selecao_h_turnos_diferentes')->where('codigo', $codigo)->update(['updated_at' => date('Y-m-d H:i:s'), 'estado' => 0]);
          return 'Permissão do estudante desactivada com sucesso!';
        } else {

          return 201;
        }
      }
    } catch (\Illuminate\Database\QueryException $e) {


      //return Response()->json($e->getMessage());

      return 201;
    }
  }
  public function removerPermissao($codigo, $opcoes)
  {

    try {
      if (!$opcoes) {
        $curso = DB::table('cursos_selecao_h_turnos_diferentes')->where('codigo', $codigo)->first();

        if ($curso) {
          //DB::table('cursos_selecao_h_turnos_diferentes')->where('codigo', $codigo)->delete();
          DB::table('cursos_selecao_h_turnos_diferentes')->where('codigo', $codigo)->update([
            'estado' => 0, 'deleted_at' => date('Y-m-d H:i:s'), 'user_id' => auth()->user()->id
          ]);
          return 'Permissão removida com sucesso!';
        } else {

          return 201;
        }
      } elseif ($opcoes) {


        $aluno = DB::table('alunos_selecao_h_turnos_diferentes')->where('codigo', $codigo)->first();

        if ($aluno) {
          //DB::table('cursos_selecao_h_turnos_diferentes')->where('codigo', $codigo)->delete();
          DB::table('alunos_selecao_h_turnos_diferentes')->where('codigo', $codigo)->update([
            'estado' => 0, 'deleted_at' => date('Y-m-d H:i:s'), 'user_id' => auth()->user()->id
          ]);
          return 'Permissão do estudante removida com sucesso!';
        } else {

          return 201;
        }
      }
    } catch (\Illuminate\Database\QueryException $e) {


      //return Response()->json($e->getMessage());

      return 201;
    }
  }




  public function nrAlunos($codigo_grade, $codigo_turma)
  {

    $alunos = DB::table('tb_grade_curricular_aluno')->/*join('tb_confirmacoes', 'tb_grade_curricular_aluno.codigo_confirmacao', '=', 'tb_confirmacoes.Codigo')->*/where(DB::raw('json_extract(tb_grade_curricular_aluno.ref_horario, "$.pk")'), $codigo_turma)->where('codigo_grade_curricular', $codigo_grade)
      ->where('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', 2)
      ->where('tb_grade_curricular_aluno.codigo_confirmacao', '!=', null)
      ->where('tb_grade_curricular_aluno.codigo_ano_lectivo', $this->anoAtualPrincipal->index())->orderBy('codigo_confirmacao', 'asc')->distinct('codigo_matricula')->get();
    /*   if($codigo_grade==718 && $codigo_turma==371){
  //dd($data['alunos']);
} */


    return $alunos;
  }
  public function nrProvasDisciplina($codigo_turma, $codigo_disciplina)
  {
    
    $calendarios = DB::table('tb_calendario_prova')->where(DB::raw('json_extract(tb_calendario_prova.ref_horario, "$.pk")'), $codigo_turma)->where('codigo_disciplina', $codigo_disciplina)->get();

    return $calendarios;
  }
  public function calendarioProva($codigo_calendario_prova)
  {

    $calendario = DB::table('tb_calendario_prova')
    ->join('tb_grade_curricular_aluno', DB::raw('json_extract(tb_grade_curricular_aluno.ref_horario, "$.pk")'), DB::raw('json_extract(tb_calendario_prova.ref_horario, "$.pk")'))
    ->join('tb_tipo_prova', 'tb_tipo_prova.Codigo', '=', 'tb_calendario_prova.codigo_tipo_prova')
    ->join('tb_modalidade_aula', 'tb_modalidade_aula.Codigo', '=', 'tb_calendario_prova.codigo_modalidade')
    ->join('tb_salas', 'tb_salas.Codigo', '=', 'tb_calendario_prova.codigo_sala')
    ->join('tb_periodos', 'tb_periodos.Codigo', '=', 'tb_calendario_prova.codigo_periodo')
    ->join('tb_disciplinas', 'tb_disciplinas.Codigo', 'tb_calendario_prova.codigo_disciplina')
    ->join('tb_confirmacoes','tb_confirmacoes.Codigo','tb_grade_curricular_aluno.codigo_confirmacao')
    ->select( 'tb_calendario_prova.codigo as codigo_calendario_prova',
      'tb_grade_curricular_aluno.codigo_grade_curricular as codigo_grade',
      'tb_disciplinas.Codigo as disciplina_codigo',
      DB::raw('json_extract(tb_calendario_prova.ref_prazo, "$.tipoAvalicao") as epoca'),
      DB::raw('json_extract(tb_calendario_prova.ref_prazo, "$.semestre") as semestre'),
      'tb_calendario_prova.data_prova as data_inicio',
      DB::raw('json_extract(tb_calendario_prova.ref_prazo, "$.anoLectivo") as ano_lectivo'),
      'tb_tipo_prova.Designacao AS tipo_prova',       
      'tb_modalidade_aula.Designacao AS modalidade',
      DB::raw('json_extract(tb_calendario_prova.ref_horario, "$.pk") as turma_codigo'),
      DB::raw('json_extract(tb_calendario_prova.ref_horario, "$.desc") as turma'),
      'tb_salas.Designacao AS sala',
      'tb_periodos.Designacao AS periodo',
      'tb_disciplinas.Designacao AS cadeira',
      'tb_calendario_prova.data_prova as data_prova', 
      'tb_calendario_prova.hora_prova as hora_inicio',
      'tb_calendario_prova.hora_termino as hora_termino',
      'tb_calendario_prova.vigilante as vigilante'
    )
    ->where('tb_calendario_prova.Codigo', $codigo_calendario_prova)
    ->first();

    return $calendario;
  }
  public function segundaProva($codigo_disciplina, $codigo_turma)
  {

    $calendario = DB::table('tb_calendario_prova')
    ->join('tb_grade_curricular_aluno', DB::raw('json_extract(tb_grade_curricular_aluno.ref_horario, "$.pk")'), DB::raw('json_extract(tb_calendario_prova.ref_horario, "$.pk")'))
    ->join('tb_tipo_prova', 'tb_tipo_prova.Codigo', '=', 'tb_calendario_prova.codigo_tipo_prova')
    ->join('tb_modalidade_aula', 'tb_modalidade_aula.Codigo', '=', 'tb_calendario_prova.codigo_modalidade')
    ->join('tb_salas', 'tb_salas.Codigo', '=', 'tb_calendario_prova.codigo_sala')
    ->join('tb_periodos', 'tb_periodos.Codigo', '=', 'tb_calendario_prova.codigo_periodo')
    ->join('tb_disciplinas', 'tb_disciplinas.Codigo', 'tb_calendario_prova.codigo_disciplina')
    ->join('tb_confirmacoes','tb_confirmacoes.Codigo','tb_grade_curricular_aluno.codigo_confirmacao')
    ->select( 'tb_calendario_prova.codigo as codigo_calendario_prova',
      'tb_grade_curricular_aluno.codigo_grade_curricular as codigo_grade',
      'tb_disciplinas.Codigo as disciplina_codigo',
      DB::raw('json_extract(tb_calendario_prova.ref_prazo, "$.tipoAvalicao") as epoca'),
      DB::raw('json_extract(tb_calendario_prova.ref_prazo, "$.semestre") as semestre'),
      'tb_calendario_prova.data_prova as data_inicio',
      DB::raw('json_extract(tb_calendario_prova.ref_prazo, "$.anoLectivo") as ano_lectivo'),
      'tb_tipo_prova.Designacao AS tipo_prova',       
      'tb_modalidade_aula.Designacao AS modalidade',
      DB::raw('json_extract(tb_calendario_prova.ref_horario, "$.pk") as turma_codigo'),
      DB::raw('json_extract(tb_calendario_prova.ref_horario, "$.desc") as turma'),
      'tb_salas.Designacao AS sala',
      'tb_periodos.Designacao AS periodo',
      'tb_disciplinas.Designacao AS cadeira',
      'tb_calendario_prova.data_prova as data_prova', 
      'tb_calendario_prova.hora_prova as hora_inicio',
      'tb_calendario_prova.hora_termino as hora_termino',
      'tb_calendario_prova.vigilante as vigilante'
    )
      ->where('tb_disciplinas.Codigo', $codigo_disciplina)
      ->where(DB::raw('json_extract(tb_calendario_prova.ref_horario, "$.pk")'), $codigo_turma)
      ->orderBy('tb_calendario_prova.codigo', 'desc')
      ->first();
    return $calendario;
  }

  public function listarCalendarioProva($id)
  {
    $anoCorrente = $this->anoAtualPrincipal->index();
    $data['ano_lectivo'] = DB::table('tb_ano_lectivo')
      ->where('estado', 'Activo')
      ->first();
    $aluno = $this->alunoRepository->dadosAlunoLogado();
    //$nrDisciplinas=$this->turmaService->nrProvaDisciplina();
    //dd($nrDisciplinas);

    $turno_id = $aluno->turno_id;
    if ($turno_id == 1 || $turno_id == 2) {

      $turno_id = 5;
    } elseif ($turno_id == 3) {

      $turno_id = 6;
    }

    $calendarios1 = DB::table('tb_calendario_prova')
      ->join('tb_grade_curricular_aluno', DB::raw('json_extract(tb_grade_curricular_aluno.ref_horario, "$.pk")'), DB::raw('json_extract(tb_calendario_prova.ref_horario, "$.pk")'))
      ->join('tb_tipo_prova', 'tb_tipo_prova.Codigo', '=', 'tb_calendario_prova.codigo_tipo_prova')
      ->join('tb_modalidade_aula', 'tb_modalidade_aula.Codigo', '=', 'tb_calendario_prova.codigo_modalidade')
      ->join('tb_salas', 'tb_salas.Codigo', '=', 'tb_calendario_prova.codigo_sala')
      ->join('tb_periodos', 'tb_periodos.Codigo', '=', 'tb_calendario_prova.codigo_periodo')
      ->join('tb_disciplinas', 'tb_disciplinas.Codigo', 'tb_calendario_prova.codigo_disciplina')
      ->join('tb_confirmacoes','tb_confirmacoes.Codigo','tb_grade_curricular_aluno.codigo_confirmacao')
      ->select( 'tb_calendario_prova.codigo as codigo_calendario_prova',
        'tb_grade_curricular_aluno.codigo_grade_curricular as codigo_grade',
        'tb_disciplinas.Codigo as disciplina_codigo',
        DB::raw('json_extract(tb_calendario_prova.ref_prazo, "$.tipoAvalicao") as epoca'),
        DB::raw('json_extract(tb_calendario_prova.ref_prazo, "$.semestre") as semestre'),
        'tb_calendario_prova.data_prova as data_inicio',
        DB::raw('json_extract(tb_calendario_prova.ref_prazo, "$.anoLectivo") as ano_lectivo'),
        'tb_tipo_prova.Designacao AS tipo_prova',       
        'tb_modalidade_aula.Designacao AS modalidade',
        DB::raw('json_extract(tb_calendario_prova.ref_horario, "$.pk") as turma_codigo'),
        DB::raw('json_extract(tb_calendario_prova.ref_horario, "$.desc") as turma'),
        'tb_salas.Designacao AS sala',
        'tb_periodos.Designacao AS periodo',
        'tb_disciplinas.Designacao AS cadeira',
        'tb_calendario_prova.data_prova as data_prova', 
        'tb_calendario_prova.hora_prova as hora_inicio',
        'tb_calendario_prova.hora_termino as hora_termino',
        'tb_calendario_prova.vigilante as vigilante'
      )
      ->where('tb_grade_curricular_aluno.codigo_matricula', $aluno->matricula)
      ->where('tb_calendario_prova.codigo_periodo', $turno_id)
      ->where('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', 2)
      ->where('tb_grade_curricular_aluno.codigo_ano_lectivo', $anoCorrente)
      ->orderBy('tb_calendario_prova.data_prova', 'asc')
      ->distinct()
      ->get();

    // dd($data['dados']);

    // $calendarios1 = DB::table('tb_calendario')
      // ->join('tb_ano_lectivo', 'tb_calendario.codigo_ano_lectivo', '=', 'tb_ano_lectivo.Codigo')
      // ->join('tb_epoca_avalicoes', 'tb_calendario.codigo_epoca', '=', 'tb_epoca_avalicoes.codigo')
      // ->join('tb_semestres', 'tb_calendario.codigo_semestre', '=', 'tb_semestres.Codigo')
      // ->join('tb_utilizadores', 'tb_calendario.codigo_utilizador', '=', 'tb_utilizadores.Codigo')
      // ->join('tb_calendario_prova', 'tb_calendario.codigo', '=', 'tb_calendario_prova.codigo_calendario')
      // ->join('tb_tipo_prova', 'tb_tipo_prova.Codigo', '=', 'tb_calendario_prova.codigo_tipo_prova')
      // ->join('tb_modalidade_aula', 'tb_modalidade_aula.Codigo', '=', 'tb_calendario_prova.codigo_modalidade')
      // ->join('tb_turmas', 'tb_turmas.Codigo', '=', 'tb_calendario_prova.codigo_turma')
      // ->join('tb_salas', 'tb_salas.Codigo', '=', 'tb_calendario_prova.codigo_sala')
      // ->join('tb_periodos', 'tb_periodos.Codigo', '=', 'tb_calendario_prova.codigo_periodo')
      // ->join('tb_disciplinas', 'tb_disciplinas.Codigo', '=', 'tb_calendario_prova.codigo_disciplina')
      //->join('tb_grade_curricular_aluno', 'tb_grade_curricular_aluno.turma', '=', 'tb_turmas.Codigo')
      // ->join('tb_grade_curricular', 'tb_grade_curricular.codigo_disciplina', '=', 'tb_calendario_prova.codigo_disciplina')
      // ->join('tb_grade_curricular_aluno', 'tb_grade_curricular_aluno.codigo_grade_curricular', '=', 'tb_grade_curricular.codigo')
      // ->join('tb_matriculas', 'tb_matriculas.Codigo', '=', 'tb_grade_curricular_aluno.codigo_matricula')
      // ->join('tb_admissao', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
      // ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
      // ->join('tb_confirmacoes', 'tb_turmas.Codigo', '=', 'tb_confirmacoes.Codigo_Turma')
      // ->select(
        // 'tb_matriculas.Codigo as numero_matricula',
      //   'tb_calendario_prova.codigo as codigo_calendario_prova',
      //   'tb_grade_curricular_aluno.codigo_grade_curricular as codigo_grade',
      //   'tb_disciplinas.Codigo as disciplina_codigo',
      //   'tb_turmas.Codigo as turma_codigo',
      //   'tb_calendario.codigo as codigo',
      //   'tb_epoca_avalicoes.descricao as epoca',
      //   'tb_semestres.Designacao as semestre',
      //   'tb_calendario.data_inicio as data_inicio',
      //   'tb_calendario.data_termino as data_termino',
      //   'tb_ano_lectivo.Designacao as ano lectivo',
      //   'tb_tipo_prova.Designacao AS tipo_prova',
      //   'tb_modalidade_aula.Designacao AS modalidade',
      //   'tb_turmas.Designacao AS turma',
      //   'tb_salas.Designacao AS sala',
      //   'tb_periodos.Designacao AS periodo',
      //   'tb_disciplinas.Designacao AS cadeira',
      //   'tb_calendario_prova.data_prova as data_prova',
      //   'tb_calendario_prova.hora_prova as hora_inicio',
      //   'tb_calendario_prova.hora_termino as hora_termino',
      //   'tb_calendario_prova.vigilante as vigilante'
      // )
      // ->where('tb_preinscricao.user_id', $id)
      // ->where('tb_confirmacoes.Cadeirante', '=', 'nao')
      // ->whereIn('tb_turmas.Codigo_Curso',[$aluno->curso_preinscricao,$aluno->curso_matricula]) //curso_matricula
      // ->where('tb_calendario_prova.ref_horario', $turno_id)
      // ->where('tb_ano_lectivo.Codigo',  $anoCorrente)
      // ->where('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', 2)
      // ->where('tb_grade_curricular_aluno.codigo_ano_lectivo', $anoCorrente)
      // ->whereRaw("CURDATE() BETWEEN tb_calendario.data_inicio  AND tb_calendario.data_termino")
      // ->orderBy('tb_calendario_prova.data_prova', 'asc')
      // ->distinct()
      // ->get();

    $collection = collect([]);
    $collection1 = collect([]);
    $arrayCalendario = json_decode($calendarios1, true);
    foreach ($arrayCalendario as $key => $value) {
      //$value['index'] = $key;
      $calendarios = $this->nrProvasDisciplina($value['turma_codigo'], $value['disciplina_codigo']);
      $alunos = $this->nrAlunos($value['codigo_grade'], $value['turma_codigo']);
     
      if ($calendarios->count() > 1) {

        if ($alunos->count() <= 75) {
          
          $calendario1 = DB::table('tb_calendario_prova')->where(DB::raw('json_extract(tb_calendario_prova.ref_horario, "$.pk")'), $value['turma_codigo'])->where('codigo_disciplina', $value['disciplina_codigo'])->select(DB::raw('min(codigo) as codigo'))->first();
      
          if ($calendario1 && $calendario1->codigo == $value['codigo_calendario_prova']) {
            $calendarioProva = $this->calendarioProva($calendario1->codigo);

            $collection->push($value);
          }
        } elseif ($alunos->count() > 75) {

          $ate75 = $alunos->sortBy('codigo_confirmacao')->take(75);


          $existe_ate75 = $ate75->where('codigo_matricula', $aluno->matricula);

          $apos75 = $alunos->whereNotIn('codigo', $ate75->pluck('codigo')->toArray());

          $existe_apos75 = $apos75->where('codigo_matricula', $aluno->matricula)->sortBy('codigo_confirmacao');
          $intervalo = $apos75->sortBy('codigo_confirmacao')->take(4); //de 76 a 79
          $existeNoIntervalo = $intervalo->where('codigo_matricula', $aluno->matricula)->sortBy('codigo_confirmacao');
          //dd($existeNoIntervalo);
          // $intervalo1= $apos75->whereNotIn('codigo',$intervalo->pluck('codigo')->toArray());//de 76 a 79

          if ($existe_ate75->count() > 0) { // se o estudante está entre os primeiros 75 alunos

            $calendario1 = DB::table('tb_calendario_prova')->where(DB::raw('json_extract(tb_calendario_prova.ref_horario, "$.pk")'), $value['turma_codigo'])->where('codigo_disciplina', $value['disciplina_codigo'])->select(DB::raw('min(codigo) as codigo'))->first();

            if ($calendario1 && $calendario1->codigo == $value['codigo_calendario_prova']) {

              $calendarioProva = $this->calendarioProva($calendario1->codigo);
              // dd($calendarioProva);
              $collection->push($value);
            }
          } elseif ($existe_apos75->count() > 0) {
            $calendario2 = DB::table('tb_calendario_prova')->where(DB::raw('json_extract(tb_calendario_prova.ref_horario, "$.pk")'), $value['turma_codigo'])->where('codigo_disciplina', $value['disciplina_codigo'])->select(DB::raw('max(codigo) as codigo'))->first();


            if ($calendario2 && $calendario2->codigo == $value['codigo_calendario_prova']) {

              $calendarioProva = $this->calendarioProva($calendario2->codigo);
              $collection->push($value);
            }
          }/*elseif($existeNoIntervalo->count()>0){

            $calendario2=DB::table('tb_calendario_prova')->where('codigo_turma',$value['turma_codigo'])->where('codigo_disciplina',$value['disciplina_codigo'])->select(DB::raw('max(codigo) as codigo'))->first();
      
            if($calendario2 && $calendario2->codigo==$value['codigo_calendario_prova'] ){
              
              $calendarioProva=$this->calendarioProva($calendario2->codigo);
              //dd($calendarioProva,$aluno->matricula);
              $value=$calendarioProva;
              //$collection->push($value);

              
              
            }



          }*/
        }
      } else {

        $collection->push($value);
      }





      //dd($collection);





    }



    return $collection;
  }

  
  public function distribuirHorarios($codigo_disciplina, $codigo_grade, $codigo_turma, $codigo_matricula)
  {
    //dd($codigo_grade,$codigo_turma);
    $aluno = $this->alunoRepository->dadosAlunoLogado();
    $anoCorrente = $this->anoAtualPrincipal->index();
    $turno_id = $aluno->turno_id;
    if ($turno_id == 1 || $turno_id == 2) {

      $turno_id = 5;
    } elseif ($turno_id == 3) {

      $turno_id = 6;
    }


    $calendarios = $this->nrProvasDisciplina($codigo_turma, $codigo_disciplina);


    $alunos = $this->nrAlunos($codigo_grade, $codigo_turma);


    if ($calendarios->count() > 1) {

      if ($alunos['nrAlunos'] <= 75) {

        $data['nrAlunos'] = $alunos['nrAlunos'];

        return $data; //existe mais de horario de prova  

      } elseif ($alunos['nrAlunos'] > 75) {
        //dd('t '.$codigo_turma,'g'.$codigo_grade,$alunos['alunos'] );
        $ate75 = $alunos['alunos']->sortBy('codigo_confirmacao')->take(75);


        $existe_ate75 = $ate75->where('codigo_matricula', $codigo_matricula);

        $apos75 = $alunos['alunos']->whereNotIn('codigo', $ate75->pluck('codigo')->toArray());

        $existe_apos75 = $apos75->where('codigo_matricula', $codigo_matricula)->sortBy('codigo_confirmacao');

        // dd($existe_apos75);
        if ($existe_ate75->count() > 0) { // se o estudante está entre os primeiros 75 alunos

          $data['param'] = 1;
        } elseif ($existe_apos75->count() > 0) { // se o estudante está após os primeiros 75 alunos	


          $data['param'] = 2;
        }
        $data['nrAlunos'] = $alunos['nrAlunos'];
        return $data;
        // $apos75=$alunos['nrAlunos']-$ate75->count();



        //$apos75=$alunos['alunos']->orderBy('codigo_confirmacao','desc')->take(75)->get();

      }
    }

    return null; //nao existe mais de horario de prova

  }

  public function turnoEstudanteBackup()
  {
    $anoCorrente=$this->anoAtualPrincipal->index(); 
    $aluno=$this->alunoRepository->dadosAlunoLogado();
    $turno = DB::table('tb_grade_curricular_aluno')->join('tb_turmas', 'tb_turmas.Codigo', '=', 'tb_grade_curricular_aluno.turma')
      ->join('tb_periodos', 'tb_periodos.codigo', '=', 'tb_turmas.Codigo_Periodo')
      
      ->select(DB::raw('count(*) as qtd, tb_periodos.Designacao as turno,tb_grade_curricular_aluno.turma'))
      ->where('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', 2)
      ->where('tb_grade_curricular_aluno.codigo_ano_lectivo', $anoCorrente)
      ->where('tb_grade_curricular_aluno.codigo_matricula', $aluno->matricula)->groupBy('tb_grade_curricular_aluno.turma')
      ->orderBy('qtd', 'desc')->first();

    return $turno;
  }
  public function turnoEstudante()
  {
    $anoCorrente=$this->anoAtualPrincipal->index(); 
    $aluno=$this->alunoRepository->dadosAlunoLogado();
    $turno = DB::table('tb_preinscricao')
    ->join('tb_periodos', 'tb_periodos.Codigo', 'tb_preinscricao.Codigo_Turno')
    ->select('tb_periodos.Designacao as turno')
    ->where('tb_preinscricao.Codigo',$aluno->codigo_inscricao)
    ->first();
    // $turno = DB::table('tb_grade_curricular_aluno')->join('mgh_tb_horario', 'mgh_tb_horario.pk_horario', '=', 'tb_grade_curricular_aluno.ref_horario->pk')
    //   ->select(DB::raw("count(*) as qtd, json_extract(ANY_VALUE(mgh_tb_horario.ref_periodicidade),'$.pkPeriodo') as pk_turno ,json_extract(ANY_VALUE(mgh_tb_horario.ref_periodicidade), '$.descPeriodo') as turno"))
    //   ->where('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', 2)
    //   ->where('tb_grade_curricular_aluno.codigo_ano_lectivo', $anoCorrente)   
    //   ->where('tb_grade_curricular_aluno.codigo_matricula', $aluno->matricula)->groupBy('pk_turno')
    //   ->orderBy('qtd', 'desc')->first();

     
    return $turno;
  }

    public function turmaComvagaEHorario($anoCorrente,$codigo_semestre,$codigo_grade,$curso_matricula) // ver turma com vaga e horario
    {
   
     // $turmas = $this->getTurmasByCursoId($anoCorrente, $curso_matricula, $codigo_ano_curricular, $turno_id,$curso_preinscricao,$codigo_matricula,$codigo_grade);
            //$arrayTurma=json_decode($cadeiras,true);
            $turmas = $this->pegaHorarioTurma($anoCorrente,$codigo_semestre,$codigo_grade,$curso_matricula); // nova abordagem

            $collecao= collect($turmas); // tratar o array

            
            //dd($collectionTurm);
            /* dd($turmas[0]->horario);
            dd($turmas[0]->aulas); */
            $arrayTurmas = json_decode($collecao,true);
        
            $collecao1 = collect([]);
            $collecaoAulas = collect([]);
            foreach ($arrayTurmas as $key1 => $value1) {

               /*  $designacao = $value1['desc'];
                $vaga = 'SIM';
                $codigo = $value1['pk']; */
              
                $collecao1->push(['pk' => $value1['horario']['pk'], 'desc' => $value1['horario']['desc'], 'corLetra'=>$value1['horario']['corLetra'],'aulas'=>$value1['aulas']]);
                
                //$collecaoAulas->push(['aulas' => $value1['aulas']]);
            
              }
              //dd($collecao1);
              //$data['aulas'] = $collecaoAulas->unique();
              $data['horario'] = $collecao1->unique();
          
           /*  $collectionTurma = collect([]);
                   
            foreach ($turmas as $key1 => $value1) {
                $designacao = $value1['Designacao'];
                $vaga='SIM';
                $codigo=$value1['Codigo'];
              
                if (sizeOf($turmas) > 0) {
                  
                    $temVaga = $this->nrVagasTurma($value1['Codigo'], $codigo_grade);
                

                    //$temVaga=false;
                   //dd($temVaga);
                    if ($temVaga == true) {
                        $vaga = 'SIM';
                    } elseif ($temVaga == false) {
                        $vaga = 'NAO';
                        $designacao = $value1['Designacao'] . '(Turma sem vaga)';
                        $codigo=null;
                    }
                }
                
                $temHorario = $this->pegaHorarioTurma($value1['Codigo'], $codigo_grade);
            
                //&& $temVaga
                if($temHorario){
                $collectionTurma->push(['Codigo' =>$codigo , 'Designacao' => $designacao, 'vaga' => $vaga]);                
            }*/
          
        return $data;
    }  



  
}
