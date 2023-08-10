
<!DOCTYPE html>
<html lang="pt">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/><!DOCTYPE html>
        <title>RELATÓRIO DOS DEPOSITOS</title>
    
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
        </style>
    </head>
<body>

@include('pdf.estudantes.header')

<main>
    <table class="table table-stripeds" style="">

        <thead>
            <tr style="background-color: #3F51B5;color: #ffffff;padding: 2px 7px">
                <th colspan="10" style="padding: 5px;">LISTAGEM DE TODOS OS MOVIMENTOS</th>
            </tr>

            @if($requests && $requests['data_final'])
                <tr style="background-color: #a1a4b9;color: #ffffff;">
                    <th colspan="4" style="padding: 5px;text-transform: uppercase">OPERADOR:</th>
                    <th colspan="6" style="padding: 5px;">{{ $operador ? $operador->nome : 'Todos' }}</th>
                </tr>
                
                <tr style="background-color: #a1a4b9;color: #ffffff;">
                    <th colspan="4" style="padding: 5px;text-transform: uppercase">CAIXA:</th>
                    <th colspan="6" style="padding: 5px;">{{ $caixa ? $caixa->nome : 'Todos' }}</th>
                </tr>
                
                <tr style="background-color: #a1a4b9;color: #ffffff;">
                    <th colspan="4" style="padding: 5px;text-transform: uppercase">Data Inicio:</th>
                    <th colspan="6" style="padding: 5px;">{{ $requests ? $requests['data_inicio'] : 'Todos' }}</th>
                </tr>
                
                <tr style="background-color: #a1a4b9;color: #ffffff;">
                    <th colspan="4" style="padding: 5px;text-transform: uppercase">Data Final:</th>
                    <th colspan="6" style="padding: 5px;">{{ $requests ? $requests['data_final'] : 'Todos' }}</th>
                </tr>
                
                <tr style="background-color: #3F51B5;color: #ffffff;padding: 7px">
                    <th colspan="10">Total: <strong>{{ count($items) }}</strong></th>
                </tr>
            @endif
            
            <tr style="background-color: #3F51B5;color: #ffffff">
                <th style="text-align: center;padding: 4px 2px" >Nº</th>
                <th style="text-align: center;padding: 4px 2px" >Operador</th>
                <th style="text-align: center;padding: 4px 2px" >Caixa</th>
                <th style="text-align: center;padding: 4px 2px" >Estado Caixa</th>
                <th style="text-align: center;padding: 4px 2px" >Validação</th>
                <th style="text-align: center;padding: 4px 2px" >V.Abertura</th>
                <th style="text-align: center;padding: 4px 2px" >V.Pagamentos</th>
                <th style="text-align: center;padding: 4px 2px" >V.Depositos</th>
                <th style="text-align: center;padding: 4px 2px" >Total Fecho</th>
                <th style="text-align: center;padding: 4px 2px" >Data</th>
            </tr>
            
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td style="text-align: center;">{{ $item->codigo ?? ''}}</td>
                    <td style="text-align: center;">{{ $item->operador->nome ?? '' }}</td>
                    <td style="text-align: center;">{{ $item->caixa->nome ?? '' }} </td>
                    <td class="text-uppercase">{{ $item->status ?? '' }}</td>
                    <td style="text-align: center;text-transform: uppercase">{{ $item->status_admin ?? '' }}</td>
                    <td style="text-align: center;">AOA {{ number_format($item->valor_abertura ?? 0, 2, ',', '.')}}</td>
                    <td style="text-align: center;">AOA {{ number_format($item->valor_arrecadado_pagamento ?? 0, 2, ',', '.')}}</td>
                    <td style="text-align: center;">AOA {{ number_format($item->valor_arrecadado_depositos ?? 0, 2, ',', '.')}}</td>
                    <td style="text-align: center;">AOA {{ number_format($item->valor_arrecadado_total ?? 0, 2, ',', '.')}}</td>
                    <td style="text-align: center;">{{ $item->created_at }}</td>
                </tr>     
            @endforeach
        </tbody>
    </table> 
</main>

</body>
</html>
