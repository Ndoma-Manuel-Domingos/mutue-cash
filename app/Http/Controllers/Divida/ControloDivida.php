<?php

namespace App\Http\Controllers\Divida;

use Illuminate\Http\Request;
use App\Categoria;
use Illuminate\Support\Facades\DB;
use App\LogAcesso;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ClassesAuxiliares\anoAtual;
use App\Models\Matricula;
use App\Services\DividaService;

class ControloDivida
{
  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public $meses;
  public $matri;
  public $anoAtualPrincipal;
  public $dividaService;
  
  public function __construct()
  {

    $this->anoAtualPrincipal = new anoAtual();
    $this->dividaService = new DividaService();
    
  }


  public function index()
  {
  }

  public function DividasAntigas()
  {

    $anoCorrente = $this->anoAtualPrincipal->index();


    $user = auth()->user();
    $matricula = DB::table('tb_matriculas')
      ->join('tb_admissao', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')

      ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')

      ->select('tb_matriculas.*', 'tb_preinscricao.Codigo as codigo_inscricao', 'tb_preinscricao.AlunoCacuaco as aluno_cacuaco')
      ->where('tb_preinscricao.user_id', $user->id)->first();


    $curso = DB::table('tb_cursos')->join('tb_preinscricao', 'tb_cursos.Codigo', '=', 'tb_preinscricao.Curso_Candidatura')->select('tb_cursos.Designacao as curso', 'tb_cursos.Codigo as codigo_curso')->where('tb_preinscricao.Codigo', $matricula->codigo_inscricao)->first();


    $maiorAno = DB::table('tb_inscricoes_ano_anterior')->join('tb_matriculas', 'tb_matriculas.Codigo', '=', 'tb_inscricoes_ano_anterior.codigo_matricula')->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', '=', 'tb_inscricoes_ano_anterior.codigo_ano_lectivo')
      ->select(DB::raw('max(tb_ano_lectivo.Designacao) as ano_designacao, ANY_VALUE(tb_ano_lectivo.Codigo) as maior'))->where('tb_inscricoes_ano_anterior.codigo_matricula', $matricula->Codigo)->where('tb_inscricoes_ano_anterior.status', 1)
      ->first();



    $inscricaoAnosAnteriores = DB::table('tb_inscricoes_ano_anterior')->join('tb_matriculas', 'tb_matriculas.Codigo', '=', 'tb_inscricoes_ano_anterior.codigo_matricula')->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', '=', 'tb_inscricoes_ano_anterior.codigo_ano_lectivo')
      ->select('tb_ano_lectivo.Designacao as ano_designacao', 'tb_inscricoes_ano_anterior.codigo_ano_lectivo as ano_lectivo')->where('tb_inscricoes_ano_anterior.codigo_matricula', $matricula->Codigo)->where('tb_inscricoes_ano_anterior.codigo_ano_lectivo', $maiorAno->maior)
      ->get();


    if ($maiorAno->maior) {
      $anoLectivoBolsa = DB::table('tb_ano_lectivo')
        ->where('Codigo', $maiorAno->maior)
        ->first();
      

      $bolseiro = DB::table('tb_bolseiro_siiuma')->where('tb_bolseiro_siiuma.codigo_matricula', $matricula->Codigo)->where('tb_bolseiro_siiuma.ano', $anoLectivoBolsa->Designacao)->select('*')->first();
    }

    $diplomado = DB::table('tb_matriculas')->where('estado_matricula', 'diplomado')->where('Codigo', $matricula->Codigo)->select('*')->first();


    $pagouOutubro = DB::table('tb_preinscricao')->join('tb_pagamentos', 'tb_pagamentos.Codigo_PreInscricao', '=', 'tb_preinscricao.Codigo')->join('tb_admissao', 'tb_admissao.pre_incricao', '=', 'tb_preinscricao.Codigo')->join('tb_matriculas', 'tb_matriculas.Codigo_Aluno', 'tb_admissao.codigo')->join('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', 'tb_pagamentos.Codigo')->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', 'tb_pagamentosi.Codigo_Servico')->where('tb_tipo_servicos.TipoServico', 'Mensal')->where('tb_pagamentosi.mes_temp_id', 5)->where('tb_pagamentosi.Ano', 2020)->where('tb_matriculas.Codigo', $matricula->Codigo)->select('*')->first();


    $arrayAnos = json_decode($inscricaoAnosAnteriores, true);


    $collection = collect([]);

    foreach ($arrayAnos as $key => $ano) {



       $mesesPagos = DB::table('tb_pagamentos')->join('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', '=', 'tb_pagamentos.Codigo')->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', '=', 'tb_pagamentosi.Codigo_Servico')
        ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_pagamentos.Codigo_PreInscricao')->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', 'tb_pagamentos.AnoLectivo')->select('tb_pagamentosi.Mes as mes', 'tb_pagamentosi.Valor_Pago as valor', 'tb_ano_lectivo.Designacao as ano', 'tb_pagamentos.estado as estado_pagamento', 'tb_pagamentosi.mes_id as codigo_mes')->where('tb_preinscricao.Codigo', $matricula->codigo_inscricao)->where('tb_pagamentosi.Ano', $ano['ano_designacao'])
        ->where('tb_tipo_servicos.TipoServico', 'Mensal')->where('tb_pagamentos.estado', 1)->distinct('tb_pagamentosi.Mes')->get();



      $mesesIds = $mesesPagos->pluck('codigo_mes');
      $array = json_decode($mesesIds, true);

      $ano_lectivo = DB::table('tb_ano_lectivo')
        ->where('Codigo', $ano['ano_lectivo'])->select('Designacao', 'Codigo')
        ->first();




      // && sizeof($mesesPagos)>0
      $propina = DB::table('tb_tipo_servicos')->select('Descricao', 'Preco', 'TipoServico', 'Codigo')->where('Descricao', 'like', 'propina ' . $curso->curso . '%')->where('cacuaco', $matricula->aluno_cacuaco)->where('codigo_ano_lectivo', $ano['ano_lectivo'])->first();

      if ($ano_lectivo->Codigo != $anoCorrente && sizeof($inscricaoAnosAnteriores) > 0 && $propina) {




        if (!$bolseiro || ($bolseiro && $bolseiro->desconto != 100)) {

          if (!$diplomado && !$pagouOutubro) {

            $mesesNaoPagos = DB::table('propina_por_curso')->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', '=', 'propina_por_curso.codigo_servico')->join('meses', 'meses.codigo', 'propina_por_curso.mes_id')->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', 'tb_tipo_servicos.codigo_ano_lectivo')->select(DB::raw('tb_tipo_servicos.Descricao as servico,meses.mes as mes_propina,tb_ano_lectivo.Designacao as ano,tb_ano_lectivo.Codigo as codigo_anoLectivo,((tb_tipo_servicos.Preco*0.1)+tb_tipo_servicos.Preco) as total, propina_por_curso.codigo_servico,tb_tipo_servicos.Preco as valor,tb_tipo_servicos.Preco*0.1 as multa'))->where('tb_tipo_servicos.Codigo', $propina->Codigo)->where('tb_tipo_servicos.cacuaco', $matricula->aluno_cacuaco)->where('tb_tipo_servicos.codigo_ano_lectivo', $ano['ano_lectivo'])->whereNotIn('propina_por_curso.mes_id', $array)->distinct('meses.mes')->get();

            //dd($mesesNaoPagos);

            $arrayNP = json_decode($mesesNaoPagos, true);


            foreach ($arrayNP as $key => $mes) {

              $collection->push(['valor' => $mes['valor'], 'multa' => 0, 'total' => $mes['valor'], 'servico' => $mes['servico'], 'mes_propina' => $mes['mes_propina'], 'ano_lectivo' => $mes['ano']]);
            }
          } //FIM IF DO DIPLOMADO  

          //
        }
      }
    }
    $dividaAntiga=sizeof($collection);

    //dd($collection);
    return $dividaAntiga;
  }

  public function desativarAluno()
  {
    //$tamanho=sizeof($this->meses);

    /*if(!$negociou){
dd('nao negociou');

}elseif($negociou){
dd('negociou');
}*/
    $collection = ($this->meses);
    $ano_lectivo_id = DB::table('tb_ano_lectivo')
      ->where('Codigo', 1)
      ->first()->Codigo;

    $pagouJulho = $collection->where('codigo_mes', 5);
    $tamanho = sizeof($pagouJulho);
    $diaAtual = date('d');
    $mesAtual = date('m');
    if (
      date('Y-m-d') > '2020-08-15'
      && $ano_lectivo_id == 1 && $tamanho == 0
    ) {


      DB::table('tb_matriculas')->where('Codigo', $this->matri->Codigo)->update(['estado_matricula' => 'inactivo']);
    }
  }

  public function pagouNegociacao($codigo_matricula)
  {

    $user = Matricula::with(['admissao.preinscricao'])->findOrFail($codigo_matricula);
    
    $anoCorrente = $this->anoAtualPrincipal->index();

    $aluno = DB::table('tb_matriculas')
      ->join('tb_admissao', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
      ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
      ->where('tb_preinscricao.user_id', $user->admissao->preinscricao->user_id)
      ->select('tb_matriculas.Codigo as matricula', 'tb_matriculas.Codigo_Curso as curso_matricula', 'tb_preinscricao.Curso_Candidatura as curso_preinscricao')
      ->first();
          
    $bolseiro = DB::table('tb_bolseiros')->where('codigo_matricula', $aluno->matricula)->where('codigo_anoLectivo', 17)->where('desconto', 100)->first();


    $pagou = DB::table('negociacao_dividas')
    ->join('factura', 'factura.Codigo', '=', 'negociacao_dividas.codigo_fatura')
    ->join('tb_pagamentos', 'tb_pagamentos.codigo_factura', '=', 'factura.Codigo')
    ->join('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', '=', 'tb_pagamentos.Codigo')
    ->where('negociacao_dividas.codigo_matricula', $aluno->matricula)
    ->where('tb_pagamentos.estado',1)->where('tb_pagamentos.AnoLectivo',$anoCorrente)
    ->select('negociacao_dividas.valor_divida', 'negociacao_dividas.primeiroValorApagar')->first();

    $dividas = $this->dividaService->DividasTodosAnos($aluno->matricula, 1);

      if (!$pagou) {
        $dados = 0;
      } elseif ($pagou) {
        $valorMetade = $pagou->valor_divida / 2;
        $dados = 1;
        foreach($dividas as $value){
          if($value['mes_temp_id'] != "" || $value['mes_propina'] != ""){
            $dados = 0;
          }
        }    
      }
      
    return $dados;
  }
  
  //Quando não tem ainda dados do user
  public function pagouNegociacaoAlunoSemUser($user_id)
  {

    $anoCorrente=$this->anoAtualPrincipal->index();
    $id = $user_id;
    $dados = 0;
    $aluno = DB::table('tb_matriculas')
      ->join('tb_admissao', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
      ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')->where('tb_preinscricao.user_id', $id)->select('tb_matriculas.Codigo as matricula', 'tb_matriculas.Codigo_Curso as curso_matricula', 'tb_preinscricao.Curso_Candidatura as curso_preinscricao')->first();


    $pagou = DB::table('negociacao_dividas')->join('factura', 'factura.Codigo', '=', 'negociacao_dividas.codigo_fatura')->join('tb_pagamentos', 'tb_pagamentos.codigo_factura', '=', 'factura.Codigo')->join('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', '=', 'tb_pagamentos.Codigo')->where('negociacao_dividas.codigo_matricula', $aluno->matricula)->where('tb_pagamentos.estado',1)->where('tb_pagamentos.AnoLectivo',$anoCorrente)->select('negociacao_dividas.valor_divida', 'negociacao_dividas.primeiroValorApagar')->first();
    //->
    //dd($pagou);
    if (!$pagou) {
      $dados = 0;
    } elseif ($pagou) {
      $valorMetade = $pagou->valor_divida / 2;


      //if($valorMetade==$pagou->primeiroValorApagar){


      $dados = 1;

      //}



    }

    return $dados;
  }


  public function ultimoMesRejeitado($ano, $id)
  {
    //$ano=$request->get('ano');
    $anoLectivo = DB::table('tb_ano_lectivo')
      ->where('Codigo', $ano)
      ->first()->Designacao;
    $candidato = DB::table('tb_preinscricao')->select('Codigo', 'polo_id', 'AlunoCacuaco', 'Curso_Candidatura')->where('user_id', $id)->first();

    $anoCorrente = '2020';

    $matricula = DB::table('tb_preinscricao')->join('tb_admissao', 'tb_admissao.pre_incricao', '=', 'tb_preinscricao.Codigo')->join('tb_matriculas', 'tb_matriculas.Codigo_Aluno', '=', 'tb_admissao.codigo')->where('tb_preinscricao.Codigo', $candidato->Codigo)->select('tb_matriculas.*')->first();
    $curso = '';
    if ($candidato && $matricula && ($candidato->Curso_Candidatura != $matricula->Codigo_Curso)) {

      $curso = DB::table('tb_cursos')->join('tb_preinscricao', 'tb_cursos.Codigo', '=', 'tb_preinscricao.Curso_Candidatura')->select('tb_cursos.Designacao as curso')->where('tb_preinscricao.Codigo', $candidato->Codigo)->first();
    } else {


      $curso = DB::table('tb_cursos')->join('tb_matriculas', 'tb_cursos.Codigo', '=', 'tb_matriculas.Codigo_Curso')->select('tb_cursos.Designacao as curso')->where('tb_matriculas.Codigo', $matricula->Codigo)->first();
    }


    //$curso=DB::table('tb_cursos')->join('tb_preinscricao','tb_cursos.Codigo','=','tb_preinscricao.Curso_Candidatura')->select('tb_cursos.Designacao as curso')->where('tb_preinscricao.Codigo',$candidato->Codigo)->first();

    $data['propina'] = DB::table('tb_tipo_servicos')->select('Descricao', 'Preco', 'TipoServico', 'Codigo')->where('Descricao', 'like', '%' . $curso->curso . '%')->where('cacuaco', $candidato->AlunoCacuaco)->first();



    if ($anoLectivo < $anoCorrente) {

      $CodultimoMes = DB::table('tb_pagamentos')->join('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', '=', 'tb_pagamentos.Codigo')
        ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', '=', 'tb_pagamentosi.Codigo_Servico')
        ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_pagamentos.Codigo_PreInscricao')
        ->where('tb_preinscricao.Codigo', $candidato->Codigo)
        ->where('tb_tipo_servicos.TipoServico', 'Mensal')
        ->where('tb_pagamentosi.Ano', $anoLectivo)
        ->where('tb_tipo_servicos.Codigo', $data['propina']->Codigo)
        ->select(DB::raw('max(tb_pagamentosi.mes_id) as ultimo'))
        ->first();

      //pega o utilmo codigo de pagamento do ultimo mes
      $CodultimoPagamento = DB::table('tb_pagamentos')->join('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', '=', 'tb_pagamentos.Codigo')
        ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', '=', 'tb_pagamentosi.Codigo_Servico')
        ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_pagamentos.Codigo_PreInscricao')
        ->where('tb_preinscricao.Codigo', $candidato->Codigo)
        ->where('tb_tipo_servicos.TipoServico', 'Mensal')
        ->where('tb_pagamentosi.Ano', $anoLectivo)
        ->where('tb_tipo_servicos.Codigo', $data['propina']->Codigo)->where('tb_pagamentosi.mes_id', $CodultimoMes->ultimo)
        ->select('tb_pagamentosi.Codigo as ultimoCodigo')
        ->first();
      if ($CodultimoPagamento) {
        //pega o ultimo mes de pagamento
        $data['ultimoMes'] = DB::table('tb_pagamentosi')->select('tb_pagamentosi.Mes as mes')->where('tb_pagamentosi.Codigo', $CodultimoPagamento->ultimoCodigo)->first();
      } elseif (!$CodultimoPagamento) {
        $data['ultimoMes'] = '';
      }
    } elseif ($anoLectivo == $anoCorrente) {



      $CodultimoMes = DB::table('tb_pagamentos')->join('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', '=', 'tb_pagamentos.Codigo')
        ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', '=', 'tb_pagamentosi.Codigo_Servico')
        ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_pagamentos.Codigo_PreInscricao')
        ->where('tb_preinscricao.Codigo', $candidato->Codigo)
        ->where('tb_tipo_servicos.TipoServico', 'Mensal')
        ->where('tb_pagamentosi.Ano', $anoLectivo)
        ->where('tb_tipo_servicos.Codigo', $data['propina']->Codigo)->where('tb_pagamentos.corrente', 1)
        ->select(DB::raw('max(tb_pagamentosi.mes_temp_id) as ultimo'))
        ->first();





      //pega o ultimo codigo de pagamento do ultimo mes
      $CodultimoPagamento = DB::table('tb_pagamentos')->join('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', '=', 'tb_pagamentos.Codigo')
        ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', '=', 'tb_pagamentosi.Codigo_Servico')
        ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_pagamentos.Codigo_PreInscricao')
        ->where('tb_preinscricao.Codigo', $candidato->Codigo)
        ->where('tb_tipo_servicos.TipoServico', 'Mensal')
        ->where('tb_pagamentosi.Ano', $anoLectivo)
        ->where('tb_tipo_servicos.Codigo', $data['propina']->Codigo)->where('tb_pagamentosi.mes_temp_id', $CodultimoMes->ultimo)->where('tb_pagamentos.corrente', 1)
        ->select('tb_pagamentosi.Codigo as ultimoCodigo')
        ->first();

      if ($CodultimoPagamento) {
        //pega o ultimo mes de pagamento
        $data['ultimoMes'] = DB::table('tb_pagamentosi')->join('mes_temp', 'mes_temp.id', '=', 'tb_pagamentosi.mes_temp_id')->select('mes_temp.designacao as mes', 'mes_temp.id')->where('tb_pagamentosi.Codigo', $CodultimoPagamento->ultimoCodigo)->first();
      } elseif (!$CodultimoPagamento) {
        $data['ultimoMes'] = '';
      }
    }


    return $data['ultimoMes'];
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
