<?php

namespace App\Services;

use DB;

class DescontoService
{
  public function descontoNov21Jul22()
  {
    $taxa = null;
    $taxa1 = DB::table('descontos_especiais')->where('id', 1)->where('estado', 1)->whereBetween(DB::raw('CURDATE()'), [DB::raw('date(data_inicio)'), DB::raw('date(data_fim)')])->first();

    $taxa1Fim = DB::table('descontos_especiais')->where('id', 1)->where('estado', 1)->where(DB::raw('CURDATE()'), '>',  DB::raw('date(data_fim)'))->first();
    
    $taxa4Fim = DB::table('descontos_especiais')->where('id',4)->where('estado', 1)->first();

    $taxa2 = DB::table('descontos_especiais')->where('id', 2)->where('estado', 1)->whereBetween(DB::raw('CURDATE()'), [DB::raw('date(data_inicio)'), DB::raw('date(data_fim)')])->first();
    
    $taxa4 = DB::table('descontos_especiais')->where('id', 4)->where('estado', 1)->first();

    $taxa = $taxa1;

    if ($taxa1Fim) {

      $taxa = $taxa2;
    }

    if ($taxa4Fim) {

      $taxa = $taxa4;
    }

    return $taxa;
  }

  public function descontoNov21Jul22PorDataBanco($dataBanco)
  {
    $taxa = null;
    $taxa1 = DB::table('descontos_especiais')->where('id', 1)->where('estado', 1)->where(DB::raw('date(data_inicio)'),'<=',$dataBanco)->where(DB::raw('date(data_fim)'),'>=',$dataBanco)->first();

    $taxa1Fim = DB::table('descontos_especiais')->where('id', 1)->where('estado', 1)->where(DB::raw('date(data_fim)'), '<', $dataBanco)->first();
   
    $taxa2 = DB::table('descontos_especiais')->where('id', 2)->where('estado', 1)->where(DB::raw('date(data_inicio)'),'<=',$dataBanco)->where(DB::raw('date(data_fim)'),'>=',$dataBanco)->first();
   
    $taxa = $taxa1;

    if ($taxa1Fim) {
      $taxa = $taxa2;
    }

    return $taxa;
  }

  public function descontoAnuidade(){
    $taxa = null;

    $taxa1 = DB::table('descontos_especiais')->where('id', 6)->where('estado', 1)->first();
    $taxa4 = DB::table('descontos_especiais')->where('id', 6)->where('estado', 1)->first();
    $taxa1Fim = DB::table('descontos_especiais')->where('id', 6)->where('estado', 1)->where(DB::raw('CURDATE()'), '>',  DB::raw('date(data_fim)'))->first();

    $taxa = $taxa1;

    if ($taxa1Fim) {
      $taxa->taxa = 0;
    }
    return ($taxa);
  }

  public function descontoAgropecuaria(){
    $taxa = null;

    $taxa1 = DB::table('descontos_especiais')->where('id', 5)->where('estado', 1)->first();
    $taxa1Fim = DB::table('descontos_especiais')->where('id', 5)->where('estado', 1)->where(DB::raw('CURDATE()'), '>',  DB::raw('date(data_fim)'))->first();
    
    $taxa = $taxa1;

    if ($taxa1Fim) {
      $taxa->taxa = 0;
    }
    return ($taxa);
  }
  
  public function descontosAlunosEspeciaisIncentivos($matricula_id){

    $taxa = null;

    $taxa_desconto = DB::table('tb_descontos_alunoo')->whereNotNull('tipo_taxa_desconto_especial')
    ->where('codigo_matricula', $matricula_id)->first();

    if ($taxa_desconto) {
        $taxa = 1;
    }
    return ($taxa);
  }

}
