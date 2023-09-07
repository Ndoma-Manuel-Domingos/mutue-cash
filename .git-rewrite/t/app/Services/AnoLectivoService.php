<?php

namespace App\Services;
use App\Http\Controllers\ClassesAuxiliares\anoAtual;

use App\AnoLectivo;
use DB;

class AnoLectivoService
{

  public $anoAtualPrincipal;

  public function __construct()
  {

    $this->anoAtualPrincipal = new anoAtual();
  }
  public function AnosLectivo($codigo)
  {
   $anos_lectivos = DB::table('tb_ano_lectivo')->where('Codigo', $codigo)->first();

     
   return $anos_lectivos;
  }

  public function semestreActivo()
  {
    
   $codigo_semestre=null;
    $semestre1=DB::table('tb_ano_lectivo')
    ->where('Codigo', $this->anoAtualPrincipal->index())
    ->whereBetween(DB::raw('CURDATE()'), [DB::raw('date(dataInicioPrimeiroSemestre)'), DB::raw('date(dataFimPrimeiroSemestre)')] )
    ->first();
    $semestre2=DB::table('tb_ano_lectivo')
    ->where('Codigo', $this->anoAtualPrincipal->index())
    ->whereBetween(DB::raw('CURDATE()'), [DB::raw('date(dataInicioSegundoSemestre)'), DB::raw('date(dataFimSegundoSemestre)')] )->first();
    if($semestre1){
      
      $codigo_semestre=1; 

    }
   
    elseif($semestre2){
  
      $codigo_semestre=2; 

    }
   
   return $codigo_semestre;
  }

  public function anosLectivoEstudante()
  {
    //Codigo de Matricula do Usuario Logado
    $codigo_matricula = DB::table('tb_admissao')
      ->join('tb_matriculas', 'tb_matriculas.Codigo_Aluno', '=', 'tb_admissao.codigo')
      ->select('tb_matriculas.codigo')
      ->where('tb_admissao.pre_incricao', auth()->user()->preinscricao->Codigo)
      ->first();


    // Verificar na tabela de anos anterior o seu ultimo ano incrito
    $ultimo_ano_letivo_designacao = DB::table('tb_inscricoes_ano_anterior')
      ->join('tb_ano_lectivo', 'tb_inscricoes_ano_anterior.codigo_ano_lectivo', '=', 'tb_ano_lectivo.Codigo')
      ->select('tb_ano_lectivo.Designacao')
      ->where('tb_inscricoes_ano_anterior.codigo_matricula', $codigo_matricula->codigo)
      ->orderBy('Designacao', 'DESC')
      ->first();
      
    // no caso de retornar nulo implica que se trata de um estudante que seus dados não foram migrados do siuma
    if ($ultimo_ano_letivo_designacao != null) {
      // $anosLectivos = DB::table('tb_ano_lectivo')->select('*')->where('Designacao', '>=', $ultimo_ano_letivo_designacao->Designacao)->orderBy('Designacao', 'DESC')->get();
      //Adicionei condição do Ciclos pós-graduação e nova condição de anos lectivos para o painel de pagamento
      if(auth()->user()->preinscricao->codigo_tipo_candidatura==1){
        $anosLectivos = DB::table('tb_ano_lectivo')->select('*')->where('Designacao', '>=', $ultimo_ano_letivo_designacao->Designacao)->where('Designacao', '!=', $this->anoAtualPrincipal->cicloMestrado()->Designacao)->where('Designacao', '!=', $this->anoAtualPrincipal->cicloDoutoramento()->Designacao)->orderBy('Designacao', 'DESC')->get();
      }elseif(auth()->user()->preinscricao->codigo_tipo_candidatura==2){
        $anosLectivos = DB::table('tb_ano_lectivo')->select('*')->where('Designacao', '>=', $ultimo_ano_letivo_designacao->Designacao)->where('Designacao', '!=', $this->anoAtualPrincipal->cicloDoutoramento()->Designacao)->orderBy('Designacao', 'DESC')->get();
      }else{
        $anosLectivos = DB::table('tb_ano_lectivo')->select('*')->where('Designacao', '>=', $ultimo_ano_letivo_designacao->Designacao)->where('Designacao', '!=', $this->anoAtualPrincipal->cicloMestrado()->Designacao)->orderBy('Designacao', 'DESC')->get();
      }
    } else {

      // se trata de esudantes Não migrados
      $ano_letivo_designacao = DB::table('tb_confirmacoes')
        ->join('tb_ano_lectivo', 'tb_confirmacoes.Codigo_Ano_lectivo', '=', 'tb_ano_lectivo.Codigo')
        ->select('tb_ano_lectivo.Designacao')
        ->where('tb_confirmacoes.Codigo_Matricula', $codigo_matricula->codigo)
        ->orderBy('Designacao', 'ASC')
        ->first();
      
      //Adicionei condição do Ciclos pós-graduação
      if (!$ano_letivo_designacao) {
        $factura_ano_lectivo = DB::table('factura')->select('factura.ano_lectivo')->where('factura.CodigoMatricula', $codigo_matricula->codigo)->get();
        
        $anosLectivos = DB::table('tb_ano_lectivo')
        ->select('*')->where('Codigo', $this->anoAtualPrincipal->index())
        ->orWhereIn('Codigo', $factura_ano_lectivo->pluck('ano_lectivo'))
        ->orderBy('Designacao', 'DESC')->get();
      } else {
        if(auth()->user()->preinscricao->codigo_tipo_candidatura==1){
          $anosLectivos = DB::table('tb_ano_lectivo')->select('*')->where('Designacao', '>=', $ano_letivo_designacao->Designacao)->where('Designacao', '!=', $this->anoAtualPrincipal->cicloMestrado()->Designacao)->where('Designacao', '!=', $this->anoAtualPrincipal->cicloDoutoramento()->Designacao)->orderBy('Designacao', 'DESC')->get();
        }elseif(auth()->user()->preinscricao->codigo_tipo_candidatura==2){
          $anosLectivos = DB::table('tb_ano_lectivo')->select('*')->where('Designacao', '>=', $ano_letivo_designacao->Designacao)->where('Designacao', '!=', $this->anoAtualPrincipal->cicloMestrado()->Designacao)->where('Designacao', '!=', $this->anoAtualPrincipal->cicloDoutoramento()->Designacao)->orderBy('Designacao', 'DESC')->get();
          // $anosLectivos = DB::table('tb_ano_lectivo')->select('*')->where('Designacao', '>=', $ano_letivo_designacao->Designacao)->where('Designacao', '!=', $this->anoAtualPrincipal->cicloDoutoramento()->Designacao)->orderBy('Designacao', 'DESC')->get();
        }else{
          $anosLectivos = DB::table('tb_ano_lectivo')->select('*')->where('Designacao', '>=', $ano_letivo_designacao->Designacao)->where('Designacao', '!=', $this->anoAtualPrincipal->cicloMestrado()->Designacao)->where('Designacao', '!=', $this->anoAtualPrincipal->cicloDoutoramento()->Designacao)->orderBy('Designacao', 'DESC')->get();
          // $anosLectivos = DB::table('tb_ano_lectivo')->select('*')->where('Designacao', '>=', $ano_letivo_designacao->Designacao)->where('Designacao', '!=', $this->anoAtualPrincipal->cicloMestrado()->Designacao)->orderBy('Designacao', 'DESC')->get();
        }
      }
    }

    return $anosLectivos;
  }

  public function getUltimoAnoLectivoInscrito($codigo_matricula){

    // se trata de esudantes Não migrados
    $ano_letivo_designacao = DB::table('tb_confirmacoes')
      ->leftJoin('tb_grade_curricular_aluno', 'tb_grade_curricular_aluno.codigo_confirmacao', 'tb_confirmacoes.Codigo')
      ->join('tb_ano_lectivo', 'tb_confirmacoes.Codigo_Ano_lectivo', '=', 'tb_ano_lectivo.Codigo')
      ->select('tb_ano_lectivo.Designacao')
      ->where('tb_confirmacoes.Codigo_Matricula', $codigo_matricula)
      // ->where('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', '!=',4)
      // ->where('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', '!=', 5)
      ->orderBy('tb_confirmacoes.Codigo_Ano_lectivo', 'DESC')
      ->first();
    if($ano_letivo_designacao==null){
      $ano_letivo_designacao = DB::table('tb_inscricoes_ano_anterior')
      ->join('tb_ano_lectivo', 'tb_inscricoes_ano_anterior.codigo_ano_lectivo', '=', 'tb_ano_lectivo.Codigo')
      ->select('tb_ano_lectivo.Designacao')
      ->where('tb_inscricoes_ano_anterior.codigo_matricula', $codigo_matricula)
      ->orderBy('tb_inscricoes_ano_anterior.codigo_ano_lectivo', 'DESC')
      ->first();
    }

    
    $anosLectivo = DB::table('tb_ano_lectivo')->select('*')->where('Designacao', $ano_letivo_designacao->Designacao)->orderBy('Designacao', 'DESC')->first();
      
    return $anosLectivo;
  
  }

}