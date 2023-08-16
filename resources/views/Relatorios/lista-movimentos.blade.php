
<!DOCTYPE html>
<html lang="pt">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/><!DOCTYPE html>
        <title>RELATÓRIO DO FECHO E VALIDAÇÃO DO CAIXA</title>
    
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

<h5 style="text-align: center; text-transform: uppercase">RELATÓRIO DO FECHO DE CAIXA</h5><br>
<main>
    <table class="table table-stripeds" style="">
        <thead>
            <tr style="background-color: #3F51B5;color: #ffffff">
                <th style="text-align: center;padding: 4px 2px" >CAIXA</th>
                <th style="text-align: center;padding: 4px 2px" >ESTADO DO CAIXA</th>
                <th style="text-align: center;padding: 4px 2px" >OPERADOR</th>
                <th style="text-align: center;padding: 4px 2px" >VALIDAÇÃO</th>
                <th style="text-align: center;padding: 4px 2px" >DATA DE ABERTURA</th>
                <th style="text-align: center;padding: 4px 2px" >DATA DO FECHO</th>
            </tr>
            
        </thead>
        <tbody>
            <tr>
                <td style="text-align: center;text-transform: uppercase;padding: 10px 0;background-color: #ccc">{{ $movimento->caixa->nome ?? '' }}</td>
                <td style="text-align: center;text-transform: uppercase;padding: 10px 0;background-color: #ccc">{{ $movimento->status ?? '' }}</td>
                <td style="text-align: center;text-transform: uppercase;padding: 10px 0;background-color: #ccc">{{ $movimento->operador->nome ?? '' }}</td>
                <td style="text-align: center;text-transform: uppercase;padding: 10px 0;background-color: #ccc">{{ $movimento->operador_admin->nome ?? '' }}</td>
                <td style="text-align: center;text-transform: uppercase;padding: 10px 0;background-color: #ccc">{{ date("Y-m-d H:i:s", strtotime($movimento->created_at ?? '')) }}</td>
                <td style="text-align: center;text-transform: uppercase;padding: 10px 0;background-color: #ccc">{{ ($movimento->status && $movimento->status=='aberto') ? NULL : date("Y-m-d H:i:s", strtotime($movimento->updated_at ?? '')) }}</td>
            </tr>
        </tbody>
        
        <thead>
            <tr style="background-color: #3F51B5;color: #ffffff">
                <th style="text-align: center;padding: 4px 2px" >VALOR DE ABERTURA</th>
                <th style="text-align: center;padding: 4px 2px" >TOTAL DE DEPÓSITOS</th>
                <th style="text-align: center;padding: 4px 2px" >TOTAL DE PAG.RECEBIDO</th>
                <th style="text-align: center;padding: 4px 2px" >TOTAL FACTURADO</th>
                <th style="text-align: center;padding: 4px 2px" >TOTAL ARRECADADO</th>
                <th style="text-align: center;padding: 4px 2px" ></th>
            </tr>
            
        </thead>
        <tbody>
            
            <tr>
                <td style="text-align: center;text-transform: uppercase;padding: 10px 0;background-color: #ccc">A0A - {{ number_format($movimento->valor_abertura ?? 0, 2, ",", ".") }}</td>
                <td style="text-align: center;text-transform: uppercase;padding: 10px 0;background-color: #ccc">A0A - {{ number_format($movimento->valor_arrecadado_depositos ?? 0, 2, ",", ".") }}</td>
                <td style="text-align: center;text-transform: uppercase;padding: 10px 0;background-color: #ccc">A0A - {{ number_format($movimento->valor_arrecadado_pagamento ?? 0, 2, ",", ".") }}</td>
                <td style="text-align: center;text-transform: uppercase;padding: 10px 0;background-color: #ccc">A0A - {{ number_format($movimento->valor_facturado_pagamento ?? 0, 2, ",", ".") }}</td>
                <td style="text-align: center;text-transform: uppercase;padding: 10px 0;background-color: #ccc">A0A - {{ number_format($movimento->valor_arrecadado_total ?? 0, 2, ",", ".") }}</td>
                <td style="text-align: center;text-transform: uppercase;padding: 10px 0;background-color: #ccc"></td>
            </tr>
        
        </tbody>
        
        <thead>
            <tr style="background-color: #3F51B5;color: #ffffff">
                <th style="text-align: left;padding: 4px 2px" colspan="5">OBSERVAÇÃO</th>
                <th style="text-align: left;padding: 4px 2px" colspan="5"></th>
            </tr>
        </thead>
        
        <tbody>
            <tr>
                <td colspan="5" style="text-align: left;text-transform: uppercase;padding: 10px 10px;background-color: #ccc;line-height: 25px">{{ $movimento->observacao ?? "" }}</td>
                <td colspan="5" style="text-align: left;text-transform: uppercase;padding: 10px 10px;background-color: #ccc;line-height: 25px"></td>
            </tr>
        </tbody>
   
    </table> 
    
    <br><br>
    <table style="margin-top: 60px">
        <tbody>
            <tr>
                <td colspan="5" style="text-align: left;text-transform: uppercase;padding: 0px 10px;"><strong>Operador</strong></td>
            </tr>
            <tr>
                <td colspan="5" style="text-align: left;text-transform: uppercase;padding: 10px 10px;">____________________________________________________________________</td>
            </tr>
            <tr>
                <td colspan="5" style="text-align: left;text-transform: uppercase;padding: 0px 10px;">{{ $movimento->operador->nome ?? '' }}</td>
            </tr>
        </tbody>
    </table>
    
    <table style="margin-top: 30px">
        
        <tbody>
            <tr>
                <td colspan="5" style="text-align: left;text-transform: uppercase;padding: 0px 10px;"><strong>Administrador</strong></td>
            </tr>
            <tr>
                <td colspan="5" style="text-align: left;text-transform: uppercase;padding: 10px 10px;">____________________________________________________________________</td>
            </tr>
            <tr>
                <td colspan="5" style="text-align: left;text-transform: uppercase;padding: 0px 10px;">{{ $movimento->operador_admin->nome ?? '' }}</td>
            </tr>
        </tbody>
    </table>


    <footer style="width: 100%; left: -10px; font-size: 10px!important;">
        Documento processado pelo software MUTUE CASH - Gestão Universitária, desenvolvido pela Mutue - Soluções Tecnológicas
        Inteligentes.
    </footer>
    
</main>

</body>
</html>
