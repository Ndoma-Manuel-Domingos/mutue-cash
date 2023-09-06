<?php

namespace App\Services;

use DB;
use App\Services\PagamentoService;
use App\Services\BolsaService;
use App\Repositories\AlunoRepository;
use App\Services\InscricoesService;
use Carbon\Carbon;

class prazoExpiracaoService
{
  public  $pagamentoService;
  public  $bolsaService;
  public  $alunoRepository;
  public  $inscricoesService;
  public function __construct()
  {
    $this->pagamentoService = new PagamentoService();
    $this->bolsaService = new BolsaService();
    $this->alunoRepository = new AlunoRepository();
    $this->inscricoesService = new InscricoesService();
  }
  public function prazoInscricoesCadeiras($ano_lectivo)
  {
    $response = null;
    $msg = '';
    $prazo = DB::table('tb_calendario_actividade_lectivas')
      ->where('codigo_ano_lectivo', $ano_lectivo)
      ->where('codigo_tipo_calendario', 4)
      ->where('codigo_tipo_candidatura', 1)
      ->whereBetween(DB::raw('CURDATE()'), [DB::raw('date(data_inicio)'), DB::raw('date(data_termino)')])->first();

     

      
    $antes_prazo = DB::table('tb_calendario_actividade_lectivas')
      ->where('codigo_ano_lectivo', $ano_lectivo)
      ->where('codigo_tipo_calendario', 4)
      ->where('codigo_tipo_candidatura', 1)
      ->where(DB::raw('CURDATE()'), '<', DB::raw('date(data_inicio)'))->first();
    $pos_prazo = DB::table('tb_calendario_actividade_lectivas')
      ->where('codigo_ano_lectivo', $ano_lectivo)
      ->where('codigo_tipo_calendario', 4)
      ->where('codigo_tipo_candidatura', 1)
      ->where(DB::raw('CURDATE()'), '>', DB::raw('date(data_termino)'))->first();
    $aluno = $this->alunoRepository->dadosAlunoLogado();

    $bolseiro = $this->bolsaService->Bolsa($aluno->matricula, $ano_lectivo);

    $pagamento = $this->pagamentoService->pagamentoInscricaoForaPrazo($ano_lectivo);
    $isencao_multa_inscricao = $this->inscricoesService->isencaoMultaPorCurso('Rfdp', $ano_lectivo);
    if (!$isencao_multa_inscricao) {
      $isencao_multa_inscricao = $this->inscricoesService->isencaoServico('Rfdp', $ano_lectivo);
    }
    if (!$prazo) {
      if ($pagamento || $bolseiro || $isencao_multa_inscricao) {

        $response = 1;
      } elseif (!$pagamento && !$bolseiro && !$isencao_multa_inscricao) {

        if ($antes_prazo) {

          $msg = 'Prezado Estudante, a época para inscrição às Unidades Curriculares ainda não está disponível!';
        }
        if ($pos_prazo) {

          $msg = 'Prezado Estudante, a época para inscrição às Unidades Curriculares terminou. Para fazer a inscrição às UCs deve fazer o pagamento de Reconfirmação Fora do Prazo em outros serviços!';
        }
        $response = null;
      }
    } elseif ($prazo) {

      $response = 1;
    }

    $data['response'] = $response;
    $data['msg'] = $msg;


    return $data;
  }

  public function prazoSelecaoHorarios($ano_lectivo)
  {
    $resposta = 0;
    $prazo = DB::table('tb_calendario_actividade_lectivas')
      ->where('codigo_ano_lectivo', $ano_lectivo)
      ->where('codigo_tipo_calendario', 8)
      ->where('codigo_tipo_candidatura', 1)
      ->whereBetween(DB::raw('CURDATE()'), [DB::raw('date(data_inicio)'), DB::raw('date(data_termino)')])->first();

    if ($prazo) {

      $resposta = 1;
    }
    return $resposta;
  }

  public function prazoPagamentoParaAtivacaoMatricula() // verifica se o periodo de pagamento para ativar a matricula esta aberto
  {
    $resposta = null;
    $prazo = DB::table('parametro_periodo_ativacao_matricula')
      ->where('estado', 1)
      ->whereBetween(DB::raw('NOW()'), [DB::raw('data_inicio'), DB::raw('data_fim')])->first();

    if ($prazo) {

      $resposta = $prazo;
    }

    return $resposta;
  }

  public function ativarMatriculaNoPrazo()
  { // ativar matricula caso o pagamento para tal esteja dentro do prazo
    $aluno = $this->alunoRepository->dadosAlunoLogado();

    if ($aluno->estado_matricula == 'inactivo') {
      $prazo = $this->prazoPagamentoParaAtivacaoMatricula(); // o periodo para pagamento de ativacao de matricula

      //dd($prazo);
      if ($prazo) {

        $pagamento = $this->pagamentoService->pagamentoPorPrestacao($prazo->prestacao);
        //dd($pagamento);

        if ($pagamento) {

          $ativar = DB::table('tb_matriculas')->where('Codigo', $aluno->matricula)->update(['estado_matricula' => 'activo']);
        }
      }
    }
  }

  public function prazoInscricoesRecurso($ano_lectivo)
  {
    $response = null;
    $msg = '';
    $prazo = DB::table('tb_calendario_actividade_lectivas')
      ->where('codigo_ano_lectivo', $ano_lectivo)
      ->where('codigo_tipo_calendario', 9)
      ->where('codigo_tipo_candidatura', 1)
      ->whereBetween(DB::raw('CURDATE()'), [DB::raw('date(data_inicio)'), DB::raw('date(data_termino)')])->first();
    $antes_prazo = DB::table('tb_calendario_actividade_lectivas')
      ->where('codigo_ano_lectivo', $ano_lectivo)
      ->where('codigo_tipo_calendario', 9)
      ->where('codigo_tipo_candidatura', 1)
      ->where(DB::raw('CURDATE()'), '<', DB::raw('date(data_inicio)'))->first();
    $pos_prazo = DB::table('tb_calendario_actividade_lectivas')
      ->where('codigo_ano_lectivo', $ano_lectivo)
      ->where('codigo_tipo_calendario', 9)
      ->where('codigo_tipo_candidatura', 1)
      ->where(DB::raw('CURDATE()'), '>', DB::raw('date(data_termino)'))->first();

    if ($antes_prazo) {
      $msg = 'Prezado Estudante, a época para inscrição de Recurso ainda não está disponível!';
      $response = null;
    } elseif ($pos_prazo) {
      $msg = 'Prezado Estudante, a época para inscrição de Recurso terminou.';
      $response = null;
    } elseif($prazo) {

      $response = 1;
    }

    $data['response'] = $response;
    $data['msg'] = $msg;

    return $data;
  }
  public function prazoInscricoesMelhoria($ano_lectivo)
  {
    $response = null;
    $msg = '';
    $prazo = DB::table('tb_calendario_actividade_lectivas')
      ->where('codigo_ano_lectivo', $ano_lectivo)
      ->where('codigo_tipo_calendario', 11)
      ->where('codigo_tipo_candidatura', 1)
      ->whereBetween(DB::raw('CURDATE()'), [DB::raw('date(data_inicio)'), DB::raw('date(data_termino)')])->first();
    $antes_prazo = DB::table('tb_calendario_actividade_lectivas')
      ->where('codigo_ano_lectivo', $ano_lectivo)
      ->where('codigo_tipo_calendario', 11)
      ->where('codigo_tipo_candidatura', 1)
      ->where(DB::raw('CURDATE()'), '<', DB::raw('date(data_inicio)'))->first();
    $pos_prazo = DB::table('tb_calendario_actividade_lectivas')
      ->where('codigo_ano_lectivo', $ano_lectivo)
      ->where('codigo_tipo_calendario', 11)
      ->where('codigo_tipo_candidatura', 1)
      ->where(DB::raw('CURDATE()'), '>', DB::raw('date(data_termino)'))->first();

    if ($antes_prazo) {
      $msg = 'Prezado Estudante, a época para inscrição de Melhoria de notas ainda não está disponível!';
      $response = null;
    } elseif ($pos_prazo) {
      $msg = 'Prezado Estudante, a época para inscrição de Melhoria de notas terminou.';
      $response = null;
    } elseif($prazo) {
      $response = 1;
    }

    $data['response'] = $response;
    $data['msg'] = $msg;

    return $data;
  }
  public function prazoInscricoesEEspecial($ano_lectivo)
  {
    $response = null;
    $msg = '';
    $prazo = DB::table('tb_calendario_actividade_lectivas')
      ->where('codigo_ano_lectivo', $ano_lectivo)
      ->where('codigo_tipo_calendario', 10)
      ->where('codigo_tipo_candidatura', 1)
      ->whereBetween(DB::raw('CURDATE()'), [DB::raw('date(data_inicio)'), DB::raw('date(data_termino)')])->first();
    $antes_prazo = DB::table('tb_calendario_actividade_lectivas')
      ->where('codigo_ano_lectivo', $ano_lectivo)
      ->where('codigo_tipo_calendario', 10)
      ->where('codigo_tipo_candidatura', 1)
      ->where(DB::raw('CURDATE()'), '<', DB::raw('date(data_inicio)'))->first();
    $pos_prazo = DB::table('tb_calendario_actividade_lectivas')
      ->where('codigo_ano_lectivo', $ano_lectivo)
      ->where('codigo_tipo_calendario', 10)
      ->where('codigo_tipo_candidatura', 1)
      ->where(DB::raw('CURDATE()'), '>', DB::raw('date(data_termino)'))->first();

    if ($antes_prazo) {
      $msg = 'Prezado Estudante, a época para inscrição de Exame de Época Especial ainda não está disponível!';
      $response = null;
    } elseif ($pos_prazo) {
      $msg = 'Prezado Estudante, a época para inscrição de Exame de Época Especial terminou.';
      $response = null;
    } elseif($prazo) {
      $response = 1;
    }

    $data['response'] = $response;
    $data['msg'] = $msg;

    return $data;
  }
  
  
  public function prazoSolicitacaoReingresso($ano_lectivo)
  {
    $response = null;
    $msg = '';
    $prazo = DB::table('tb_calendario_actividade_lectivas')
      ->where('codigo_ano_lectivo', $ano_lectivo)
      ->where('codigo_tipo_calendario', 12)
      ->where('codigo_tipo_candidatura', 1)
      ->whereBetween(DB::raw('CURDATE()'), [DB::raw('date(data_inicio)'), DB::raw('date(data_termino)')])->first();
    $antes_prazo = DB::table('tb_calendario_actividade_lectivas')
      ->where('codigo_ano_lectivo', $ano_lectivo)
      ->where('codigo_tipo_calendario', 12)
      ->where('codigo_tipo_candidatura', 1)
      ->where(DB::raw('CURDATE()'), '<', DB::raw('date(data_inicio)'))->first();
    $pos_prazo = DB::table('tb_calendario_actividade_lectivas')
      ->where('codigo_ano_lectivo', $ano_lectivo)
      ->where('codigo_tipo_calendario', 12)
      ->where('codigo_tipo_candidatura', 1)
      ->where(DB::raw('CURDATE()'), '>', DB::raw('date(data_termino)'))->first();

    if ($antes_prazo) {
      $msg = 'Prezado Estudante, a época para Solicitação de Reingresso ainda não está disponível!';
      $response = null;
    } elseif ($pos_prazo) {
      $msg = 'Prezado Estudante, a época para Solicitação de Reingresso terminou.';
      $response = null;
    } elseif($prazo) {
      $response = 1;
    }

    $data['response'] = $response;
    $data['msg'] = $msg;

    return $data;
  }

  public function prazoSolicitacaoMudancaUC($ano_lectivo)
  {
    $response = null;
    $msg = '';
    $prazo = DB::table('tb_calendario_actividade_lectivas')
      ->where('codigo_ano_lectivo', $ano_lectivo)
      ->where('codigo_tipo_calendario', 13)
      ->where('codigo_tipo_candidatura', 1)
      ->whereBetween(DB::raw('CURDATE()'), [DB::raw('date(data_inicio)'), DB::raw('date(data_termino)')])->first();
    $antes_prazo = DB::table('tb_calendario_actividade_lectivas')
      ->where('codigo_ano_lectivo', $ano_lectivo)
      ->where('codigo_tipo_calendario', 13)
      ->where('codigo_tipo_candidatura', 1)
      ->where(DB::raw('CURDATE()'), '<', DB::raw('date(data_inicio)'))->first();
    $pos_prazo = DB::table('tb_calendario_actividade_lectivas')
      ->where('codigo_ano_lectivo', $ano_lectivo)
      ->where('codigo_tipo_calendario', 13)
      ->where('codigo_tipo_candidatura', 1)
      ->where(DB::raw('CURDATE()'), '>', DB::raw('date(data_termino)'))->first();

    if ($antes_prazo) {
      $msg = 'Prezado Estudante, a época para Solicitação de Mudança de UC p/outra ainda não está disponível!';
      $response = null;
    } elseif ($pos_prazo) {
      $msg = 'Prezado Estudante, a época para Solicitação de Mudança de UC p/outra terminou.';
      $response = null;
    } elseif($prazo) {
      $response = 1;
    }

    $data['response'] = $response;
    $data['msg'] = $msg;

    return $data;
  }

  public function prazoSolicitacaoMudancaCurso($ano_lectivo)
  {
    $response = null;
    $msg = '';
    $prazo = DB::table('tb_calendario_actividade_lectivas')
      ->where('codigo_ano_lectivo', $ano_lectivo)
      ->where('codigo_tipo_calendario', 14)
      ->where('codigo_tipo_candidatura', 1)
      ->whereBetween(DB::raw('CURDATE()'), [DB::raw('date(data_inicio)'), DB::raw('date(data_termino)')])->first();
    $antes_prazo = DB::table('tb_calendario_actividade_lectivas')
      ->where('codigo_ano_lectivo', $ano_lectivo)
      ->where('codigo_tipo_calendario', 14)
      ->where('codigo_tipo_candidatura', 1)
      ->where(DB::raw('CURDATE()'), '<', DB::raw('date(data_inicio)'))->first();
    $pos_prazo = DB::table('tb_calendario_actividade_lectivas')
      ->where('codigo_ano_lectivo', $ano_lectivo)
      ->where('codigo_tipo_calendario', 14)
      ->where('codigo_tipo_candidatura', 1)
      ->where(DB::raw('CURDATE()'), '>', DB::raw('date(data_termino)'))->first();

    if ($antes_prazo) {
      $msg = 'Prezado Estudante, a época para Solicitação de Mudança de Curso Interno ainda não está disponível!';
      $response = null;
    } elseif ($pos_prazo) {
      $msg = 'Prezado Estudante, a época para Solicitação de Mudança de Curso Interno terminou.';
      $response = null;
    } elseif($prazo) {
      $response = 1;
    }

    $data['response'] = $response;
    $data['msg'] = $msg;

    return $data;
  }


  public function prazoInscricoesCadeirasExtraCurriculares($ano_lectivo)
  {
    $resposta = null;
    $msg = '';
    $prazo = DB::table('tb_calendario_actividade_lectivas')
      ->where('codigo_ano_lectivo', $ano_lectivo)
      ->where('codigo_tipo_calendario', 15)
      ->where('codigo_tipo_candidatura', 1)
      ->whereBetween(DB::raw('CURDATE()'), [DB::raw('date(data_inicio)'), DB::raw('date(data_termino)')])->first();

    if ($prazo) {

      $resposta = 1;
      
    }elseif(!$prazo){
      $msg = "Prezado Estudante, as Inscrição para Unidades Extra-Curriculares não está disponível!";
      $resposta=null; 
    }
    $data['msg'] = $msg;
    $data['resposta'] = $resposta;
    return $data;
  }
  public function prazoPagamentoAnoTodoComDesconto($ano_lectivo,$numero_prestacao) // prazo para ter o desconto de 5% pelo pagamento do ano todo 
  {
  
    $condicoes = [];
    if(auth()->user()->preinscricao->codigo_tipo_candidatura==1){
      array_push($condicoes, ['activo', 1]);
      
    }else{

      $condicoes = array_push($condicoes, ['activo_posgraduacao', 1]);
    }
  
      
        $prazo=DB::table('mes_temp')->where('ano_lectivo',$ano_lectivo)->where('prestacao', $numero_prestacao)
        ->whereBetween(DB::raw('CURDATE()'), [DB::raw('date(data_inicial)'), DB::raw('date(data_final_desconto)')])
        ->where($condicoes)->first();

    
    return $prazo;
  }


  public function prazosCalendario($ano_lectivo, $tipo_calendario, $tipo_candidatura)
  {
    $data['prazoMatriculaPosGraduacao'] = DB::table('tb_calendario_actividade_lectivas')
      ->where('codigo_ano_lectivo', $ano_lectivo)
      ->where('codigo_tipo_calendario', $tipo_calendario)
      ->where('codigo_tipo_candidatura', $tipo_candidatura)
      ->whereBetween(DB::raw('CURDATE()'), [DB::raw('date(data_inicio)'), DB::raw('date(data_termino)')])->first();

    $data['prazoMatriculaLincenciatura'] = DB::table('tb_calendario_actividade_lectivas')
      ->where('codigo_ano_lectivo', $ano_lectivo)
      ->where('codigo_tipo_calendario', $tipo_calendario)
      ->where('codigo_tipo_candidatura', $tipo_candidatura)
      ->whereBetween(DB::raw('CURDATE()'), [DB::raw('date(data_inicio)'), DB::raw('date(data_termino)')])->first();

    return $data;
  }
 
  
}
