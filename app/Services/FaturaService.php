<?php

namespace App\Services;

use DB;
use Auth;
use App\Repositories\AlunoRepository;
use App\Http\Controllers\ClassesAuxiliares\anoAtual;
use  App\Services\ServicosService;

class FaturaService
{
  public $alunoRepository;
  public $anoCorrente;
  public $servicosService;
  public $anoAtualPrincipal;
  public function __construct()

  {
    $this->alunoRepository = new AlunoRepository();
    $this->anoAtualPrincipal = new anoAtual();
    $this->servicosService = new ServicosService();
   
  }

  public  function salvarFacturaMovimentoConta($codigo_factura)
  {
    DB::beginTransaction();

    try {

      if ($codigo_factura) {
        $fatura = DB::table('factura')->where('Codigo', $codigo_factura)->first();
        if ($fatura) {
          DB::table('historico_movimento_conta_estudante')->insert([
            'referencia' => $fatura->Codigo,
            'data_movimento' => date('Y-m-d'), 'credito' => 0, 'debito' => 0, 'estado' => 0, 'matricula' => $fatura->CodigoMatricula ?? null, 'saldo_operacao' => 0, 'saldo_geral' => 0, 'codigoTipoMovimento' => 1, 'codigoMotivo' => null,
            'codigoUtilizador' => null, 'observacao' => 'factura solicitada pelo estudante', 'Factura' => $fatura->Codigo
          ]);
        }
        // //H:i:s
      }
    } catch (\Illuminate\Database\QueryException $e) {

      DB::rollback();
      return Response()->json('ocorreu um erro(mc)');
    }
    DB::commit();
  }

  public function liquidarFacturas()
  {
    $aluno = $this->alunoRepository->dadosAlunoLogado();
    $anoCorrente = $this->anoAtualPrincipal->index();
    $ano1 = DB::table('tb_ano_lectivo')
      ->where('Codigo', $anoCorrente)
      ->first(); // redundancia
    $facturas = DB::table('factura')
      ->select(
        'factura_items.estado as estado_item',
        'factura_items.CodigoFactura',
        'factura.estado as estado_fatura',
        'factura.corrente',
        'factura.ValorEntregue',
        'factura.ValorAPagar',
        'factura.Codigo as codigoFatura'
      )
      ->join('factura_items', 'factura_items.CodigoFactura', 'factura.Codigo')
      ->join('tb_pagamentos', 'tb_pagamentos.codigo_factura', 'factura.Codigo')
      ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', 'factura_items.CodigoProduto')
      ->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', 'factura.ano_lectivo')
      ->where('factura.CodigoMatricula', $aluno->matricula)
      ->where('factura.corrente', 1)->where('factura.estado', '!=', 3)
      //->whereNotIn('factura.codigo_descricao', [5, 10])
      //->where('tb_tipo_servicos.TipoServico', 'Mensal')
      ->where(function ($query) {
        $query->where('factura_items.estado', '!=', 1)
          ->orWhereIn('factura.estado', [0, 2]);
      })->where(function ($query) use ($ano1) {
        $query->whereIn('tb_ano_lectivo.ordem', [$ano1->ordem, $ano1->ordem - 1])
          ->orWhereIn('tb_ano_lectivo.Designacao',[$this->anoAtualPrincipal->cicloMestrado()->Designacao, $this->anoAtualPrincipal->cicloDoutoramento()->Designacao]);
      })
      //->whereRaw('factura_items.mes_temp_id IS NOT NULL')
      ->orderBy('factura.Codigo', 'desc')
      ->get();

    $facturas2 = DB::table('factura')
      ->select(
        'factura_items.estado as estado_item',
        'factura_items.CodigoFactura',
        'factura.estado as estado_fatura',
        'factura.corrente',
        'factura.ValorEntregue',
        'factura.ValorAPagar',
        'factura.Codigo as codigoFatura'
      )
      ->join('factura_items', 'factura_items.CodigoFactura', 'factura.Codigo')
      ->join('tb_pagamentos', 'tb_pagamentos.codigo_factura', 'factura.Codigo')
      ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', 'factura_items.CodigoProduto')
      ->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', 'factura.ano_lectivo')
      ->where('factura.CodigoMatricula', $aluno->matricula)
      ->where('factura.corrente', 1)->where('factura.estado', '!=', 3)
      ->where('factura.ValorEntregue', '>', 0)
      //->whereNotIn('factura.codigo_descricao', [5, 10])
      //->where('tb_tipo_servicos.TipoServico', 'Mensal')
      ->where(function ($query) {
        $query->where('factura_items.estado', '!=', 2)
          ->orWhereIn('factura.estado', [0, 1]);
      })->where(function ($query) use ($ano1) {
        $query->whereIn('tb_ano_lectivo.ordem', [$ano1->ordem, $ano1->ordem - 1])
          ->orWhereIn('tb_ano_lectivo.Designacao',[$this->anoAtualPrincipal->cicloMestrado()->Designacao, $this->anoAtualPrincipal->cicloDoutoramento()->Designacao]);
      })
      //->whereRaw('factura_items.mes_temp_id IS NOT NULL')
      ->orderBy('factura.Codigo', 'desc')
      ->get();

    DB::beginTransaction();
    $array = json_decode($facturas, true);
    $array2 = json_decode($facturas2, true);
    $msg = "Nenhuma acção efectuada!";
    $update = false;
    // atualizando o estado das facturas
    if (filled($facturas)) {
      foreach ($array as $key => $item) {
        //$soma_pagamentos = DB::table('tb_pagamentos')->select('valor_depositado')->where('codigo_factura', $item['codigoFatura'])->get()->sum('valor_depositado');
        //dd($soma_pagamentos);

        $factura_items = DB::table('factura_items')
        ->select(
          'factura_items.estado as estado_item',
          'factura_items.codigo',
          'factura_items.valor_pago',
          'factura_items.Total',
          'factura_items.descontoProduto',
          'factura_items.Multa',
          'factura_items.preco'
        )->where('factura_items.CodigoFactura', $item['codigoFatura'])->get();

        if (($item['ValorAPagar'] - $item['ValorEntregue']) < 0.5) {
          try {
            $diferenca=($item['ValorAPagar'] - $item['ValorEntregue']);
            DB::table('factura')->where('Codigo', $item['codigoFatura'])->update(['estado' => 1, 'ValorEntregue' => ($item['ValorEntregue']+$diferenca)]);
          } catch (\Illuminate\Database\QueryException $e) {
            DB::rollback();
            return $e->getMessage();
          }
          try {
            if (filled($factura_items)) {
              foreach ($factura_items as $key => $item_fatura) {
                $totalItem = ($item_fatura->preco+$item_fatura->Multa)-$item_fatura->descontoProduto;
                if ($item_fatura->valor_pago!=0) {
                  if (($item_fatura->Total- $item_fatura->valor_pago) < 0.5) {
                    $valor_pago=($item_fatura->Total-$item_fatura->valor_pago)+$item_fatura->valor_pago;
                    DB::table('factura_items')->where('codigo', $item_fatura->codigo)->update(['estado' => 1,'valor_pago' => $valor_pago,'Total' => $totalItem]);
                  }else{
                    DB::table('factura_items')->where('codigo', $item_fatura->codigo)->update(['estado' => 2,'Total' => $totalItem]);
                  }
                }else{
                  DB::table('factura_items')->where('codigo', $item_fatura->codigo)->update(['estado' => 0]);
                }
              }
            }
          } catch (\Illuminate\Database\QueryException $e) {

            DB::rollback();
            return $e->getMessage();
          }
          $msg = "Atualização do estado das factura feita com sucesso";
        }
      }
    }

    if (filled($facturas2)) {
      foreach ($array2 as $key => $item) {

        $factura_items = DB::table('factura_items')
        ->select(
          'factura_items.estado as estado_item',
          'factura_items.codigo',
          'factura_items.valor_pago',
          'factura_items.Total',
          'factura_items.descontoProduto',
          'factura_items.Multa',
          'factura_items.preco'
        )->where('factura_items.CodigoFactura', $item['codigoFatura'])->get();

        if (($item['ValorAPagar'] - $item['ValorEntregue']) >=0.5) {
          try {
            DB::table('factura')->where('Codigo', $item['codigoFatura'])->update(['estado' => 2]);
          } catch (\Illuminate\Database\QueryException $e) {

            DB::rollback();
            return $e->getMessage();
          }
          try {
            if (filled($factura_items)) {
              foreach ($factura_items as $key => $item_fatura) {
                $totalItem = ($item_fatura->preco+$item_fatura->Multa)-$item_fatura->descontoProduto;
                if ($item_fatura->valor_pago!=0) {
                  if (($item_fatura->Total- $item_fatura->valor_pago) < 0.5) {
                    $valor_pago=($item_fatura->Total-$item_fatura->valor_pago)+$item_fatura->valor_pago;
                    DB::table('factura_items')->where('codigo', $item_fatura->codigo)->update(['estado' => 1,'valor_pago' => $valor_pago,'Total' => $totalItem]);
                  }else{
                    DB::table('factura_items')->where('codigo', $item_fatura->codigo)->update(['estado' => 2,'Total' => $totalItem]);
                  }
                }else{
                  DB::table('factura_items')->where('codigo', $item_fatura->codigo)->update(['estado' => 0]);
                }
              }
            }
            // DB::table('factura_items')->where('CodigoFactura', $item['codigoFatura'])->update(['estado' => 2]);
          } catch (\Illuminate\Database\QueryException $e) {

            DB::rollback();
            return $e->getMessage();
          }
          $msg = "Atualização do estado das factura feita com sucesso";
        }
      }
    }

    $factura_por_liquidar = DB::select("SELECT  f.Codigo as Codigo, f.ValorAPagar as ValorApago from factura f 
    inner join tb_pagamentos p on f.Codigo = p.codigo_factura where p.estado = 1 and f.CodigoMatricula = :codigo_matricula
    GROUP BY 1 HAVING SUM(p.valor_depositado) >= f.ValorAPagar",['codigo_matricula'=>$aluno->matricula]);

    if(filled($factura_por_liquidar)){
      foreach($factura_por_liquidar  as $key => $fatura_Value){

        $factura_items_por_liquidar = DB::table('factura_items')->where('CodigoFactura', $fatura_Value->Codigo)->get();

        foreach($factura_items_por_liquidar as $key1 => $factura_items_por_liquidar_value){

          $update = DB::table('factura_items')->where('codigo', $factura_items_por_liquidar_value->codigo)->update(['estado'=>1, 'valor_pago'=>$factura_items_por_liquidar_value->Total]);

        }
          $update = DB::table('factura')->where('Codigo', $fatura_Value->Codigo)->update(['ValorEntregue'=>$fatura_Value->ValorApago, 'estado'=>1]);
      }

      if($update==true){
        $msg = "Atualização do estado das factura feita com sucesso";
      }
    }
      
    DB::commit();
    return $msg;
  }

  public function facturaComMultaIFPrazo($matricula, $codigo_ano, $sigla) // 
  {
    $inscricao_fora_prazo = $this->servicosService->servicoPorSigla($sigla, $codigo_ano);
    $factura = DB::table('factura')
      ->join('factura_items', 'factura_items.CodigoFactura', 'factura.Codigo')
      ->where('factura.CodigoMatricula', $matricula)
      ->where('factura.corrente', 1)->where('factura.estado', '!=', 3)
      ->where('factura.codigo_descricao', 3)
      ->where('factura.ano_lectivo', $codigo_ano)
      ->where('factura_items.CodigoProduto', $inscricao_fora_prazo->Codigo)
      ->first();


    return $factura;
  }

  /*public function facturasPorLiquidar() // 
  {
    $anoCorrente = $this->anoAtualPrincipal->index();
    $ano1 = DB::table('tb_ano_lectivo')
      ->where('Codigo', $anoCorrente)
      ->first(); 
    $aluno = $this->alunoRepository->dadosAlunoLogado();
    $facturas = DB::table('factura')
    ->select(
      'factura_items.estado as estado_item',
      'factura_items.CodigoFactura',
      'factura.estado as estado_fatura',
      'factura.corrente',
      'factura.ValorEntregue',
      'factura.ValorAPagar',
      'factura.Codigo as codigoFatura'
    )
    ->join('factura_items', 'factura_items.CodigoFactura', 'factura.Codigo')
    ->join('tb_pagamentos', 'tb_pagamentos.codigo_factura', 'factura.Codigo')
    ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', 'factura_items.CodigoProduto')
    ->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', 'factura.ano_lectivo')
    ->where('factura.CodigoMatricula', $aluno->matricula)
    ->where('factura.corrente', 1)->where('factura.estado', '!=', 3)
    //->whereNotIn('factura.codigo_descricao', [5, 10])
    //->where('tb_tipo_servicos.TipoServico', 'Mensal')
    ->where(function ($query) {
      $query->where('factura_items.estado', '!=', 1)
        ->orWhereIn('factura.estado', [0, 2]);
    })
    //->where('factura_items.estado',0)
    ->whereIn('tb_ano_lectivo.ordem', [$ano1->ordem, $ano1->ordem - 1])
    //->whereRaw('factura_items.mes_temp_id IS NOT NULL')
    ->orderBy('factura.Codigo', 'desc')
    ->get();


    return $facturas;
  }*/

  public function facturasPorLiquidar() // 
  {
    $anoCorrente = $this->anoAtualPrincipal->index();
    $ano1 = DB::table('tb_ano_lectivo')
      ->where('Codigo', $anoCorrente)
      ->first(); 
    $aluno = $this->alunoRepository->dadosAlunoLogado();
    $facturas = DB::table('factura')
    ->select(
      'factura_items.estado as estado_item',
      'factura_items.CodigoFactura',
      'factura.estado as estado_fatura',
      'factura.corrente',
      'factura.ValorEntregue',
      'factura.ValorAPagar',
      'factura.Codigo as codigoFatura'
    )
    ->join('factura_items', 'factura_items.CodigoFactura', 'factura.Codigo')
    ->join('tb_pagamentos', 'tb_pagamentos.codigo_factura', 'factura.Codigo')
    ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', 'factura_items.CodigoProduto')
    ->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', 'factura.ano_lectivo')
    ->where('factura.CodigoMatricula', $aluno->matricula)
    ->where(function ($query) {
      $query->where('factura_items.estado', '!=', 1)
        ->orWhereIn('factura.estado', [0, 2]);
    })
    ->where('tb_pagamentos.corrente', 1)
    ->where('tb_pagamentos.estado', 0)
    ->orderBy('factura.Codigo', 'desc')
    ->get();


    return $facturas;
  }


  public function facturasNaoPagasNaTotalidade() // 
  {
    $anoCorrente = $this->anoAtualPrincipal->index();
    $ano1 = DB::table('tb_ano_lectivo')
      ->where('Codigo', $anoCorrente)
      ->first(); 
    $aluno = $this->alunoRepository->dadosAlunoLogado();
    $facturasNaoPagasNaTotalidade = DB::table('factura')
    ->select(
      'factura_items.estado as estado_item',
      'factura_items.CodigoFactura',
      'factura.estado as estado_fatura',
      'factura.corrente',
      'factura.ValorEntregue',
      'factura.ValorAPagar',
      'factura.Codigo as codigoFatura'
    )
    ->join('factura_items', 'factura_items.CodigoFactura', 'factura.Codigo')
    ->join('tb_pagamentos', 'tb_pagamentos.codigo_factura', 'factura.Codigo')
    ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', 'factura_items.CodigoProduto')
    ->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', 'factura.ano_lectivo')
    ->where('factura.CodigoMatricula', $aluno->matricula)
    ->where('factura.corrente', 1)
    ->where('factura.estado', '!=', 3)
    ->where(function ($query) {
      $query->where('factura_items.estado', '!=', 1)
        ->orWhereIn('factura.estado', [0, 2]);
    })
    ->where('tb_pagamentos.estado', 1)
    ->where('tb_pagamentos.corrente', 1)
    ->where(function ($query) use ($ano1) {
      $query->whereIn('tb_ano_lectivo.ordem', [$ano1->ordem, $ano1->ordem - 1])
        ->orWhereIn('tb_ano_lectivo.Designacao',[$this->anoAtualPrincipal->cicloMestrado()->Designacao, $this->anoAtualPrincipal->cicloDoutoramento()->Designacao]);
    })
    ->orderBy('factura.Codigo', 'desc')
    ->get();

    return $facturasNaoPagasNaTotalidade;
  }



}
