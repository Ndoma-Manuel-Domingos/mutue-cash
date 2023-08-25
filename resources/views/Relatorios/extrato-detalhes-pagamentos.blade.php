
<!DOCTYPE html>
<html lang="pt">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/><!DOCTYPE html>
        <title>DETALHES DE EXTRACTO DE PAGAMENTO</title>

        <style type="text/css">
            *{
                margin: 0;
                padding: 0;
                -webkit-box-sizing: border-box;
                box-sizing: border-box;
                font-family: Arial, Helvetica, sans-serif;
                text-align: left;
            }
            body{
                padding: 20px;
                font-family: Arial, Helvetica, sans-serif;
            }

            h1{
                font-size: 15pt;
                margin-bottom: 10px;
            }
            h2{
                font-size: 12pt;
            }
            p{
                /* margin-bottom: 20px; */
                line-height: 25px;
                font-size: 10pt;
                text-align: justify;
            }
            strong{
                font-size: 10pt;
            }

            table{
                width: 100%;
                text-align: left;
                border-spacing: 2;
                margin-bottom: 10px;
                /* border: 1px solid rgb(0, 0, 0); */
                font-size: 10pt;
            }
            thead{
                background-color: #fdfdfd;
                font-size: 10px;
            }
            td{
                border-bottom: 1px solid rgb(255, 255, 255);
            }
            th, td{
                padding: 6px;
                font-size: 10px;
                margin: 0;
                padding: 0;
            }
            strong{
                font-size: 10px;
            }

            .modal-content {
                position: relative;
                display: -ms-flexbox;
                display: flex;
                -ms-flex-direction: column;
                flex-direction: column;
                width: 100%;
                pointer-events: auto;
                background-color: #fff;
                background-clip: padding-box;
                border: 1px solid rgba(0, 0, 0, 0.2);
                border-radius: 0.3rem;
                box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.5);
                outline: 0;
            }

            .modal-header {
                display: -ms-flexbox;
                display: flex;
                -ms-flex-align: start;
                align-items: flex-start;
                -ms-flex-pack: justify;
                justify-content: space-between;
                padding: 1rem;
                border-bottom: 1px solid #e9ecef;
                border-top-left-radius: calc(0.3rem - 1px);
                border-top-right-radius: calc(0.3rem - 1px);
            }

            .modal-title {
                margin-bottom: 0;
                line-height: 1.5;
            }

            .modal-body {
                position: relative;
                -ms-flex: 1 1 auto;
                flex: 1 1 auto;
                padding: 1rem;
            }

            footer {
                color: #777777;
                width: 100%;
                height: 30px;
                position: absolute;
                bottom: 0;
                border-top: 1px solid #AAAAAA;
                padding: 8px 0;
                text-align: center;
            }
        </style>
    </head>
<body>

@include('pdf.estudantes.header')

<main>
    <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">DETALHES DE EXTRACTO DE PAGAMENTO</h5>
        </div>

        <div class="modal-body">
          <div class="table-responsive">
            <table class="table-sm text-nowrap">
              <tbody>
                <tr> <th>Nº da factura: {{ $pagamento->codigo_factura }}</th> </tr>
                <tr> <th>1 pagamento(s) efectuado(s)</th> </tr>
                <tr> <th>Data da factura: {{ $pagamento->DataFactura }}</th> </tr>
                <tr> <th>Valor total a pagar: {{number_format($pagamento->Totalgeral ?? 0, 2, ',', '.')}} </th> </tr>
                <tr> <th>Valor pago pelo serviço: {{number_format($pagamento->Totalgeral ?? 0, 2, ',', '.')}} </th> </tr>
                <tr> <th>Valor em dívida: {{number_format(($pagamento->ValorAPagar - $pagamento->valor_depositado) ?? 0, 2, ',', '.')}} </th> </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="modal-header bg-info py-1" style="background-color: #2e306e; color:white; border:solid 1px white; padding: 0px;">
          <h6 class="modal-title">Pagamentos</h6>
        </div>

        <div class="modal-body">
          <div class="table-responsive">
            <table class="table-sm table-bordered table-hover text-nowrap" style="width: 100%;">
              <thead>
                <tr>
                  <th>Items</th>
                  <th>Nº Pagamento</th>
                  <th>Data de envio do pag.</th>
                  <th>Valor depositado</th>
                  <th>Estado</th>
                  <th>Data da validação</th>
                  {{-- <th>Feito com saldo</th> --}}
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>{{ 1 }}</td>
                  <td>{{ $pagamento->Codigo }}</td>
                  <td>{{ $pagamento->DataRegisto }}</td>
                  <td class="text-center">{{ number_format($pagamento->valor_depositado ?? 0, 2, ',', '.') }}</td>
                  <td class="text-center" ><span class="text-success">Validado</span></td>
                  <td class="text-center">{{ $pagamento->updated_at }}</td>
                  {{-- <td class="text-center"> Não</td> --}}
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="modal-header bg-info py-1" style="background-color: #2e306e; color:white; border:solid 1px white; padding: 0px;">
          <h6 class="modal-title">Items do Pagamento</h6>
        </div>

        <div class="modal-body">
          <div class="table-responsive">
            <table class="table-sm table-bordered table-hover text-nowrap" style="width: 100%;">
              <thead>
                <tr>
                  <th>Item</th>
                  <th>Serviço/UC</th>
                  <th>Prestação</th>
                  <th>Valor</th>
                  <th>Multa</th>
                  <th>Desconto</th>
                  <th class="text-center">Total</th>
                </tr>
              </thead>
              <tbody>
                @foreach($items as $index => $item)
                    <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->servico->Descricao }}</td>
                    <td>{{ $item->mes_temps ? $item->mes_temps->designacao : ( $item->mes ? $item->mes->mes : '#') }}</td>
                    <td>{{ number_format($item->Valor_Pago ?? 0, 2, ',', '.') }}</td>
                    <td>{{ number_format($item->Multa ?? 0, 2, ',', '.') }}</td>
                    <td>{{ number_format($item->Deconnto ?? 0, 2, ',', '.') }}</td>
                    <td class="text-center">{{ number_format($item->Valor_Total ?? 0, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    <div >
        <br>
        <br><br><br>
        <p style="text-align:center;">Assinatura<br><br>
            __________________________ <br><br>
            {{ $operador ? $operador->nome : 'Todos' }}

        </p>
    </div>

    <footer style="width: 100%; left: -10px; font-size: 10px!important;">
        Documento processado pelo software MUTUE CASH - Gestão Universitária, desenvolvido pela Mutue - Soluções Tecnológicas
        Inteligentes
    </footer>
</main>

</body>
</html>
