<?php

namespace App\Services;

use App\Factura;

use App\PagamentoPorReferencia;
use App\Repositories\AlunoRepository;

class GerarRefereciaDePagamento
{


  static public function run($factura_codigo, $montante)
  {

    //Recuperar a factura gerada e criar a referência
    $factura = Factura::find($factura_codigo);
    //Buscar aluno por Matricula
    $alunoRepo = new AlunoRepository();
    //Para Matriculados e Não Matriculados
    if ($factura->CodigoMatricula) {
      $aluno = $alunoRepo->dadosAlunoPorMatricula($factura->CodigoMatricula);
    } else {
      $aluno = $alunoRepo->getAlunoPorPreinscricao($factura->codigo_preinscricao);
    }



    //Calculo de parcela a pagar
    $parcela_count = PagamentoPorReferencia::where('factura_codigo', $factura->Codigo)->count() + 1;

    $source_id = $factura->Codigo; //$factura->Codigo.'P'.$parcela_count; //parcela actual a ser enviada no BE como source_id(Factura parcelar)


    //gerar referencia via SOAP do Pagamento Fácil do BE
    $data['source_id'] = $source_id;
    $data['amount'] = $montante;
    $data['telefone'] = '997334106'; //$aluno->telefone??'';
    $data['email'] = 'ndongalamd@gmail.com'; //$aluno->email??'';
    $data['custumer_name'] = $aluno->Nome_Completo; //Nome do Aluno
    $data['endereco'] = $aluno->endereco ?? 'Luanda, Angola';
    $data['expira_dentro_de'] = env('BE_REFERENCIA_EXPIRATION_DAYS', 1); //um dia


    $response = PagamentoPorReferenciaService::create($data); //$factura->ValorAPagar

    $response['factura_codigo'] = $factura->Codigo;
    //Guardar as informações da referencia criada na BD local 
    $referencia = PagamentoPorReferencia::updateOrCreate(['source_id' => $source_id, 'factura_codigo' => $factura->Codigo], $response);

    return $referencia;
  }
}
