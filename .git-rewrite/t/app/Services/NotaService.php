<?php

namespace App\Services;  
use DB;
class NotaService
{
  public function insertOrChangeData()
  {
  }

  public function fechoDeNotasPorAno($numero_matricula)
  {
    try {
      $client = new \GuzzleHttp\Client();
      set_time_limit(0);
      //mutue.co.ao/mutue/estudante/actualizar_notas?nuneroDeMatricula=25118
      $request = $client->get('mutue.co.ao/mutue//estudante/actualizar_notas?numeroDeMatricula=' . $numero_matricula);
      //$request = $client->get('10.10.6.250:8080/mutue//inscricoes/verificar_espaco?turma='.$turma_id.'&grade='.$grade_id.'');

      $response = json_decode($request->getBody());
      
      return $response;
      
    } catch (\Exception $e) {
      DB::rollback();
      throw $e;
    }
  }
}
