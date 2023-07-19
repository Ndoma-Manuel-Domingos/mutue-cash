<?php

namespace App\Services;

use App\Factura;
use App\Http\Controllers\ClassesAuxiliares\anoAtual;
use App\KohaFactura;
use App\Repositories\AlunoRepository;
use GuzzleHttp\Client;
use GuzzleHttp;
use Carbon\Carbon;

class KohaService
{

  //Cria uma factura de multa no para o estudante pagar
  public static  function gerarMulta($matricula_id, $montante, $koha_multa_id) //$koha_multa_id é igual ao codigo de fine no Koha
  {


    //dd(SystemService::getCodigoAnoLectivoActual());
    //Recuoerar o estudante pela matricula
    $repository = new AlunoRepository();
    $aluno = $repository->dadosAlunoPorMatricula($matricula_id);

    $data['DataFactura'] = Carbon::now();
    $data['TotalPreco'] = $montante;
    $data['CodigoMatricula'] = $matricula_id;
    $data['polo_id'] = $aluno->polo_id;
    //$fatura['Referencia'] = $referencia;
    $data['ValorAPagar'] = $montante;
    //$data['Desconto'] = $desco;
    //$data['TotalMulta'] = $multa;

    $data['ano_lectivo'] = (new anoAtual())->index(); //SystemService::getCodigoAnoLectivoActual();
    $data['codigo_descricao'] = 2;
    $factura = Factura::create($data);
    //Recuperar o servico por sigla e ano lectivo
    $servico = new PagamentoService();
    //Criar itens da Factura  tipo_servico=4037
    $itens = [
      'CodigoProduto' => $servico->taxaServicoPorSigla('MnBK'),
      'CodigoFactura' => $factura->Codigo,
      'Quantidade' => 1,
      'Total' => $montante,
      //'Mes' => $mes,
      // 'mes_temp_id' => $value1['mes_temp_id'],
      //'Multa' => $value1['Multa'],
      'preco' => $montante,
      //'descontoProduto' => $desconto_mes
    ];

    $factura->factura_itens()->create($itens);
    //adicionar na tabela KohaFactura
    KohaFactura::create([
      'factura_codigo' => $factura->Codigo,
      'koha_multa_id' => $koha_multa_id,
      'montante' => $montante
    ]);





    return $factura;
  }
  //Usa a API do KOHA para efectuar o pagamento da multa npo KOHA
  public function pagarMultaNoKoha($matricula_id, $montante, $koha_multa_id)
  {

    $client = new Client();
    $endpoint = "http://www.koha.com";

    $response = $client->post($endpoint, [
      GuzzleHttp\RequestOptions::JSON => [
        'matricula_id' => $matricula_id,
        'montante' => $montante,
        'codigo_koha' => $koha_multa_id
      ]
    ]);
  }

  //Verificar status da multa
  public static function checkMultaStatus($matricula_id, $montante, $koha_multa_id)
  {
    $multa = KohaFactura::where('koha_multa_id', $koha_multa_id)->first();

    if ($multa) {

      $factura = $multa->factura;
      return [
        'valorPago' => $factura->ValorEntregue,
        'matricula_id' => $factura->CodigoMatricula,
        'koha_multa_id' => $factura->kohaFactura->koha_multa_id,
        'status' => ($factura->estado == 1 && $factura->ValorEntregue == $montante)
      ]; //;
    } else {
      return null;
    }
  }
}
