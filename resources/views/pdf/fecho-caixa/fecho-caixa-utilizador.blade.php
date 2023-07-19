
<!DOCTYPE html>
<html lang="pt">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/><!DOCTYPE html>
        <title>PROPINAS (PREÇARIOS)</title>
    
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
                font-size: 12pt;
                text-align: justify;
            }
            strong{
                font-size: 12pt;
            }

            table{
                width: 100%;
                text-align: left;
                border-spacing: 2;	
                margin-bottom: 10px;
                /* border: 1px solid rgb(0, 0, 0); */
                font-size: 12pt;
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
                font-size: 13px;
                margin: 0;
                padding: 0;
            }
            strong{
                font-size: 13px;
            }
        </style>
    </head>
<body>

<div style="text-align: center;width: 100%;padding: 20px 0;">
    <table style="border-bottom: 1px solid #000;padding-bottom: 10px">
        <tr>
            <td rowspan="5" style="width: 100px">
                <img src="{{ public_path('images/logotipo.png') }}" style="width: 200px;height: 120px;" />
            </td>
            <td style="text-align: right;font-size: 16px">Universidade Metodista</td>
        </tr>
        <tr>
            <td style="text-align: right;font-size: 16px">Rua Nossa Senhora da Muxima Nº 10,</td>
        </tr>
        <tr>
            <td style="text-align: right;font-size: 16px">Bairro Kinaxixi, Luanda</td>
        </tr>
        <tr>
            <td style="text-align: right;font-size: 16px">+244 947716133/+244 942364667</td>
        </tr>
        <tr>
            <td style="text-align: right;font-size: 16px">geral@uma.co.ao</td>
        </tr>
    </table>
</div>

<main>

    <table style="background-color: rgb(234, 234, 234);margin-top: -20px">
        <tr>
            <td colspan="2" style="text-align: center;padding: 0px">FECHO DE CAIXA POR UTILIZADOR</td>
        </tr>
        <tr>
            <td style="text-align: left;padding: 0px">Total de Registro:</td>
            <td style="text-align: left;padding-left: 5px;border-left: 2px solid #ffffff"><strong>{{ $items->count() }}</strong></td>
        </tr>

        <tr>
            <td style="text-align: left;padding: 0px">Utilizador</td>
            <td style="text-align: left;padding-left: 5px;border-left: 2px solid #ffffff"><strong>{{ Auth::user()->nome }}</strong></td>
        </tr>
    </table>

    <table class="table table-stripeds" style="">
        <thead>
            <tr style="background-color: #3F51B5;color: #ffffff">
                <th style="text-align: center;padding: 4px 0" width="150px">Operador</th>
                <th style="text-align: center;padding: 4px 0">Data Validação</th>
                <th style="text-align: center;padding: 4px 0">Valor</th>
                <th style="text-align: center;padding: 4px 0">Recibo</th>
                <th style="text-align: center;padding: 4px 0">Forma de Pagamento</th>
                <th style="text-align: center;padding: 4px 0">Estado</th>
                <th style="text-align: center;padding: 4px 0">Ano Lectivo</th>
            </tr>
        </thead>
        @php
            $contador = 0;
        @endphp
        <tbody>
            @foreach ($items as $item)
                @php
                    $contador++;
                @endphp
                <tr>
                    <td style="padding: 5px;text-align: center">{{  $item->nomeUtilizador }}</td>
                    <td style="text-align: center">{{ date("Y-m-d", strtotime($item->dataValidacaoPagamento)) }}</td>
                    <td style="text-align: center">{{ number_format($item->valorPagamento, 2, ',', '.') }} KZ</td>
                    <td style="text-align: center">{{ $item->reciboPagamento }}</td>
                    <td style="text-align: center">{{ $item->formaPagamento }}</td>
                    @if ( $item->estadoPagamento == 1 ) 
                        <td style="text-align: center">Valido</td>
                    @else 
                        @if($item->estadoPagamento == 2)
                        <td style="text-align: center">Rejeitado</td>
                        @endif
                    @endif
                    <td style="text-align: center">{{ $item->AnoLectivoPagamento }} </td>
                </tr>     
            @endforeach
        </tbody>
    </table> 
</main>

</body>
</html>



