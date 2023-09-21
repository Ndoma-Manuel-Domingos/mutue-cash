<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <!DOCTYPE html>
    <title>RELATÓRIO DOS DEPOSITOS</title>

    <style type="text/css">
        * {
            margin: 0;
            padding: 0;
            -webkit-box-sizing: border-box;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
            text-align: left;
        }

        body {
            padding: 20px;
            font-family: Arial, Helvetica, sans-serif;
        }

        h1 {
            font-size: 15pt;
            margin-bottom: 10px;
        }

        h2 {
            font-size: 12pt;
        }

        p {
            /* margin-bottom: 20px; */
            line-height: 25px;
            font-size: 10pt;
            text-align: justify;
        }

        strong {
            font-size: 10pt;
        }

        table {
            width: 100%;
            text-align: left;
            border-spacing: 2;
            margin-bottom: 10px;
            /* border: 1px solid rgb(0, 0, 0); */
            font-size: 10pt;
        }

        thead {
            background-color: #fdfdfd;
            font-size: 10px;
        }

        td {
            border-bottom: 1px solid rgb(255, 255, 255);
        }

        th,
        td {
            padding: 6px;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }

        strong {
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

    {{-- <h5 style="text-align: center; text-transform: uppercase">Lista de depósitos</h5><br> --}}
    <main>
        <table class="table table-stripeds" style="">
            <thead>

                <tr style="background-color: #3F51B5;color: #ffffff;padding: 2px 7px">
                    <th colspan="8" style="padding: 5px;">EXTRATOS DE DEPÓSITOS</th>
                </tr>

                <tr style="background-color: #a1a4b9;color: #ffffff;">
                    <th colspan="3" style="padding: 5px;">Estudantes:</th>
                    <th colspan="5" style="padding: 5px;">{{ $matricula ? $matricula->admissao->preinscricao->Nome_Completo : 'TODOS'  }}</th>
                </tr>
                @if($requests && $requests['data_final'])

                <tr style="background-color: #a1a4b9;color: #ffffff;">
                    <th colspan="3" style="padding: 5px;">Data Inicio:</th>
                    <th colspan="5" style="padding: 5px;">{{ $requests ? $requests['data_inicio'] : 'Todos' }}</th>
                </tr>

                <tr style="background-color: #a1a4b9;color: #ffffff;">
                    <th colspan="3" style="padding: 5px;">Data Final:</th>
                    <th colspan="5" style="padding: 5px;">{{ $requests ? $requests['data_final'] : 'Todos' }}</th>
                </tr>


                <tr style="background-color: #3F51B5;color: #ffffff;padding: 7px">
                    <th colspan="8">Total: <strong>{{ count($items) }}</strong></th>
                </tr>
                @endif

                <tr style="background-color: #3F51B5;color: #ffffff">
                    <th style="text-align: center;padding: 4px 2px">Nº Deposito</th>
                    <th style="text-align: center;padding: 4px 2px">Nº Estudante</th>
                    <th style="text-align: center;padding: 4px 2px">Estudante</th>
                    <th style="text-align: center;padding: 4px 2px">Valor depositado</th>
                    <th style="text-align: center;padding: 4px 2px">Reserva após Depósito</th>
                    <th style="text-align: center;padding: 4px 2px">Operador</th>
                    <th style="text-align: center;padding: 4px 2px">Ano Lectivo</th>
                    <th style="text-align: center;padding: 4px 2px">Data</th>
                </tr>

            </thead>
            <tbody>
                @foreach ($items as $item)
                <tr>

                    <td style="text-align: center;">{{ $item->codigo ?? '' }}</td>
                    <td style="text-align: center;">{{$item->codigo_matricula_id ?? $item->candidato->Codigo ?? '' }}</td>
                    <td style="text-align: left;">
                        {{ $item->matricula->admissao->preinscricao->Nome_Completo ?? $item->candidato->Nome_Completo ?? '' }}
                    </td>
                    <td style="text-align: center;">{{ number_format($item->valor_depositar ?? 0, 2, ',', '.') }} kz</td>
                    <td style="text-align: center;">{{ number_format($item->saldo_apos_movimento ?? 0, 2, ',', '.') }} kz</td>
                    <td style="text-align: left;">{{ $item->user->nome ?? '' }}</td>
                    <td style="text-align: center;">{{ $item->ano_lectivo->Designacao ?? '' }}</td>
                    <td style="text-align: left;">{{ date("Y-m-d", strtotime($item->created_at ?? ''))  }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

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
            Inteligentes.
        </footer>
    </main>

</body>
</html>
