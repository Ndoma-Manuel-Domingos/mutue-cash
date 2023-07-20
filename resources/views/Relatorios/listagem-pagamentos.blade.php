
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
        
            <tr style="background-color: #a1a4b9;color: #ffffff;">
                <th colspan="2" style="padding: 5px;">Ano Lectivo:</th>
                <th colspan="6" style="padding: 5px;">{{ $ano_lectivo ? $ano_lectivo->Designacao : 'Todos' }}</th>
            </tr>
            
            <tr style="background-color: #a1a4b9;color: #ffffff;">
                <th colspan="2" style="padding: 5px;">Operador:</th>
                <th colspan="6" style="padding: 5px;">{{ $operador ? $operador->nome : 'Todos' }}</th>
            </tr>
            
            <tr style="background-color: #a1a4b9;color: #ffffff;">
                <th colspan="2" style="padding: 5px;">Data Inicio:</th>
                <th colspan="6" style="padding: 5px;">{{ $requests ? $requests['data_inicio'] : 'Todos' }}</th>
            </tr>
            
            <tr style="background-color: #a1a4b9;color: #ffffff;">
                <th colspan="2" style="padding: 5px;">Data Final:</th>
                <th colspan="6" style="padding: 5px;">{{ $requests ? $requests['data_final'] : 'Todos' }}</th>
            </tr>
            
            
            <tr style="background-color: #3F51B5;color: #ffffff;padding: 7px">
                <th colspan="8">Total: <strong>{{ count($items) }}</strong></th>
            </tr>
            
            <tr style="background-color: #3F51B5;color: #ffffff">
                <th style="text-align: center;padding: 4px 2px" >Nº</th>
                <th style="text-align: center;padding: 4px 2px" >Matricula</th>
                <th style="text-align: center;padding: 4px 2px" >Serviço Pago</th>
                <th style="text-align: center;padding: 4px 2px" >Nome</th>
                <th style="text-align: center;padding: 4px 2px" >Curso</th>
                <th style="text-align: center;padding: 4px 2px" >Data</th>
                <th style="text-align: center;padding: 4px 2px" >Total Pago</th>
            </tr>
            
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td style="text-align: center;">{{ $item->Codigo ?? '' }}</td>
                    <td style="text-align: center;">{{ $item->matricula ?? '' }}</td>
                    <td style="text-align: center;">{{ $item->servico ?? '' }}</td>
                    <td style="text-align: center;">{{ $item->Nome_Completo ?? '' }}</td>
                    <td style="text-align: center;">{{ $item->curso ?? '' }}</td>
                    <td style="text-align: center;">{{ date("Y-m-d", strtotime($item->DataRegisto ?? ''))  }}</td>
                    <td style="text-align: center;">{{ number_format($item->Totalgeral ?? 0, 2, ',', '.') }} kz</td>
                </tr>     
            @endforeach
        </tbody>
    </table> 
</main>

</body>
</html>
