<?php

namespace App\Services\Api\Turma;

use App\Semestre;
use App\Services\Api\ApiService; 
class TurmaApiService extends ApiService
{
  

  public function nrVagasTurma($turma_id,$grade_id)
  {
  
    $url = $this->baseUrl() . '//inscricoes/verificar_espaco?turma=' . $turma_id . '&grade=' . $grade_id;
  
    return $url;
  }

  public function pegaHorarioTurma($ano_lectivo,$semestre,$codigo_grade,$curso)//horario da grade - nova abordagem
  {
    //Domingos António eixeira da Cunha 
    //$url = $this->baseUrl() . '//inscricao/horario?turma=' . $codigo_turma . '&grade=' . $codigo_grade; // abordagem antiga '10.10.6.32:8080/mutue'
   
    
    $url = $this->baseUrl() . '//mgh/api/findHorarios?anoLectivo='.$ano_lectivo .'&semestre='.$semestre.'&grade='.$codigo_grade.'&curso='.$curso;
    
    return $url;
  }

  public function pegaHorarioSelecao($codigo_matricula)
  {

    $url = $this->baseUrl() . '//horarios/meuHorario?matricula=' . $codigo_matricula;
    return $url;
  }

  public function verColisaoHorario($pk_horario)
  {
   // http://10.10.6.32:8080/mutue/mgh/api/validarColisoesHorarios?pkHorario=9
   
    $url = $this->baseUrl() . '//mgh/api/validarColisoesHorarios?pkHorario=' . $pk_horario; 
    
    return $url;
  }

  public function verColisaoHorarioBackup($codigo_grade,$codigo_turma) //
  {
      
    $url = $this->baseUrl() . '//inscricoes/vericarColisao?turma=' . $codigo_turma . '&grade=' . $codigo_grade;
  
    
    return $url;
  }

}


