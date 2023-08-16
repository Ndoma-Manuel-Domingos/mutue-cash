
<!DOCTYPE html>
<html lang="pt">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/><!DOCTYPE html>
        <title>COMPROVATIVO DE DEPÓSITO</title>

        <link rel="stylesheet" href="css/style_matricula.css" media="all" />

        <style>
            *{
                font-family: Arial, Helvetica, sans-serif;
                padding: 0;
                margin: 0;
            }
        </style>

    </head>
<body>

    <header class="clearfix">
        <div id="logo">
            <img src="{{ public_path('images/logotipo.png') }}">
        </div>
        <div id="company" style="font-size: 10px;">
            <h2 class="name">Universidade Metodista</h2>
            <div>Rua Nossa Senhora da Muxima Nº 10.<br>Bairro Kinaxixi, Luanda.</div>
            <div>+244 947716133/+244 942364667</div>
            <div><a href="mailto:geral@uma.co.ao">geral@uma.co.ao</a></div>
            <div>NIF: 5401150865</div>
        </div>
    </header>


    <div style="border-bottom: 0px solid black;">
        <h5 style="background-color: lightgray;padding: 10px;text-align: center">
            COMPROVATIVO DE DEPÓSITO
        </h5>
        <div style="text-align: right;font-size:10px;"><i>Ano Lectivo: {{ $item->ano_lectivo->Designacao }}</i></div>
        <table style="font-size:10px;width: 100%" >
            <thead>
                <tr>
                    {{--   --}}
                    {{-- <th style="text-align: right;padding: 4px;">&nbsp;&nbsp;&nbsp;&nbsp;</th> --}}
                    <th style="text-align: left;padding: 4px; border-top: solid 1px lightgray;border-left: solid 1px lightgray; border-right: solid 1px lightgray;">
                        Nº Deposito: {{ $item->codigo ?? '' }}</th>
                    <th style="text-align: left;padding: 4px; background-color: lightgray;">
                        Mº Matricula: {{ $item->codigo_matricula_id ?? '' }}
                    </th>
                </tr>

                <tr>
                    {{-- <th style="text-align: left;padding: 4px; background-color: lightgray;"></th>  --}}
                    {{-- <th style="text-align: right;padding: 4px;">&nbsp;&nbsp;&nbsp;&nbsp;</th> --}}
                    <th style="text-align: left;padding: 4px; border-top: solid 1px lightgray;border-left: solid 1px lightgray; border-right: solid 1px lightgray;">
                        Data Movimento: {{ date("Y-m-d", strtotime($item->created_at ?? ''))  }}
                    </th>

                    <th style="text-align: left;padding: 4px; background-color: lightgray;">
                        Estudante: {{ $item->matricula->admissao->preinscricao->Nome_Completo ?? '' }}
                    </th>
                </tr>

                <tr>
                    {{-- <th style="text-align: left;padding: 4px; background-color: lightgray;"></th>  --}}
                    {{-- <th style="text-align: right;padding: 4px;">&nbsp;&nbsp;&nbsp;&nbsp;</th> --}}
                    <th style="text-align: left;padding: 4px; border-top: solid 1px lightgray;border-left: solid 1px lightgray; border-right: solid 1px lightgray; font-weight: bolder">
                        Operador: {{ $item->user->nome ?? '' }}</th>
                </tr>
            </thead>
        </table>
    </div>

    <table style="font-size: 10px!important;">
        <thead>
          <tr style="">
           <th style="background-color: #2e306e; color:white; border:solid 1px white; padding: 0px;">Nº</th>
            <!--th class="wd-40p">Valor</th-->
             {{-- <th
                style="text-align: left!important;background-color: #2e306e; color:white;font-size:10px; border:solid 1px white; padding: 0px;">
                Descrição&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            </th> --}}
            <th style="background-color: #2e306e; color:white; border:solid 1px white; padding: 5px;text-align: right!important;">Valor Depositado</th>
            <th style="background-color: #2e306e; color:white; border:solid 1px white; padding: 5px;text-align: right!important;">Valor após deposito</th>
            <th style="background-color: #2e306e; color:white; border:solid 1px white; padding: 5px;text-align: right!important;">Saldo Dispoível</th>
            {{-- <th style="background-color: #2e306e; color:white; border:solid 1px white; padding: 0px;">Multa</th>
            <th style="background-color: #2e306e; color:white; border:solid 1px white; padding: 0px;">Desc.</th>
            <th style="background-color: #2e306e; color:white;border:solid 1px white; padding: 0px;">Total</th> --}}
          </tr>
        </thead>

        <tbody style="text-align: center!important">
            <th style="text-align: right!important;font-size: 11px!important;">1</th>
            <td style="text-align: right!important;font-size: 11px!important;">{{ number_format($item->valor_depositar ?? 0, 2, ',', '.') }} kz </td>
            <td style="text-align: right!important;font-size: 11px!important;">{{ number_format($item->saldo_apos_movimento ?? 0, 2, ',', '.') }} kz</td>
            <td style="text-align: right!important;font-size: 11px!important;">{{ number_format($item->matricula->admissao->preinscricao->saldo ?? 0, 2, ',', '.') }} kz </td>
        </tbody>

    </table>

    <div >
        <br><br><br>
        <p style="text-align:center;">Assinatura<br><br>
            __________________________ <br><br>
            {{$item->user->nome}}
        </p>
    </div>

    <footer style="width: 100%; left: -10px; font-size: 10px!important;">
        Documento processado pelo software MUTUE CASH - Gestão Universitária, desenvolvido pela Mutue - Soluções Tecnológicas
        Inteligentes.
    </footer>

    <br><br><br>
        <div style="border-top: 1px solid #AAAAAA;"></div>
    <br><br><br>
    {{-- OUTRA PARTE DO RECIBO, SERÁ 2 RECIBOS EM UMA SÓ FOLHA --}}
    <header class="clearfix">
        <div id="logo">
            <img src="{{ public_path('images/logotipo.png') }}">
        </div>
        <div id="company" style="font-size: 10px;">
            <h2 class="name">Universidade Metodista</h2>
            <div>Rua Nossa Senhora da Muxima Nº 10.<br>Bairro Kinaxixi, Luanda.</div>
            <div>+244 947716133/+244 942364667</div>
            <div><a href="mailto:geral@uma.co.ao">geral@uma.co.ao</a></div>
            <div>NIF: 5401150865</div>
        </div>
    </header>


    <div style="border-bottom: 0px solid black;">
        <h5 style="background-color: lightgray;padding: 10px;text-align: center">
            COMPROVATIVO DE DEPOSITO
        </h5>
        <div style="text-align: right;font-size:10px;"><i>Ano Lectivo: {{ $item->ano_lectivo->Designacao }}</i></div>
        <table style="font-size:10px;width: 100%" >
            <thead>
                <tr>
                    {{--   --}}
                    {{-- <th style="text-align: right;padding: 4px;">&nbsp;&nbsp;&nbsp;&nbsp;</th> --}}
                    <th style="text-align: left;padding: 4px; border-top: solid 1px lightgray;border-left: solid 1px lightgray; border-right: solid 1px lightgray;">
                        Nº Deposito: {{ $item->codigo ?? '' }}</th>
                    <th style="text-align: left;padding: 4px; background-color: lightgray;">
                        Mº Matricula: {{ $item->codigo_matricula_id ?? '' }}
                    </th>
                </tr>

                <tr>
                    {{-- <th style="text-align: left;padding: 4px; background-color: lightgray;"></th>  --}}
                    {{-- <th style="text-align: right;padding: 4px;">&nbsp;&nbsp;&nbsp;&nbsp;</th> --}}
                    <th style="text-align: left;padding: 4px; border-top: solid 1px lightgray;border-left: solid 1px lightgray; border-right: solid 1px lightgray;">
                        Data Movimento: {{ date("Y-m-d", strtotime($item->created_at ?? ''))  }}
                    </th>

                    <th style="text-align: left;padding: 4px; background-color: lightgray;">
                        Estudante: {{ $item->matricula->admissao->preinscricao->Nome_Completo ?? '' }}
                    </th>
                </tr>

                <tr>
                    {{-- <th style="text-align: left;padding: 4px; background-color: lightgray;"></th>  --}}
                    {{-- <th style="text-align: right;padding: 4px;">&nbsp;&nbsp;&nbsp;&nbsp;</th> --}}
                    <th style="text-align: left;padding: 4px; border-top: solid 1px lightgray;border-left: solid 1px lightgray; border-right: solid 1px lightgray; font-weight: bolder">
                        Operador: {{ $item->user->nome ?? '' }}</th>
                </tr>
            </thead>
        </table>
    </div>

    <table style="font-size: 10px!important;">
        <thead>
          <tr style="">
           <th style="background-color: #2e306e; color:white; border:solid 1px white; padding: 0px;">Nº</th>
            <!--th class="wd-40p">Valor</th-->
             {{-- <th
                style="text-align: left!important;background-color: #2e306e; color:white;font-size:10px; border:solid 1px white; padding: 0px;">
                Descrição&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            </th> --}}
            <th style="background-color: #2e306e; color:white; border:solid 1px white; padding: 5px;text-align: right!important;">Valor Depositado</th>
            <th style="background-color: #2e306e; color:white; border:solid 1px white; padding: 5px;text-align: right!important;">Valor após deposito</th>
            <th style="background-color: #2e306e; color:white; border:solid 1px white; padding: 5px;text-align: right!important;">Saldo Dispoível</th>
            {{-- <th style="background-color: #2e306e; color:white; border:solid 1px white; padding: 0px;">Multa</th>
            <th style="background-color: #2e306e; color:white; border:solid 1px white; padding: 0px;">Desc.</th>
            <th style="background-color: #2e306e; color:white;border:solid 1px white; padding: 0px;">Total</th> --}}
          </tr>
        </thead>

        <tbody style="text-align: center!important">
            <th style="text-align: right!important;font-size: 11px!important;">1</th>
            <td style="text-align: right!important;font-size: 11px!important;">{{ number_format($item->valor_depositar ?? 0, 2, ',', '.') }} kz </td>
            <td style="text-align: right!important;font-size: 11px!important;">{{ number_format($item->saldo_apos_movimento ?? 0, 2, ',', '.') }} kz</td>
            <td style="text-align: right!important;font-size: 11px!important;">{{ number_format($item->matricula->admissao->preinscricao->saldo ?? 0, 2, ',', '.') }} kz </td>
        </tbody>

    </table>

    <div >
        <br><br><br>
        <p style="text-align:center;">Assinatura<br><br>
            __________________________ <br><br>
            {{$item->user->nome}}
        </p>
    </div>

    <footer style="width: 100%; left: -10px; font-size: 10px!important;">
        Documento processado pelo software MUTUE CASH - Gestão Universitária, desenvolvido pela Mutue - Soluções Tecnológicas
        Inteligentes.
    </footer>

    {{-- @include('pdf.estudantes.header')

    <main>

        <table class="table table-stripeds" style="">

            <thead>

                <tr style="background-color: #3F51B5;color: #ffffff">
                    <th colspan="9" style="font-size: 10pt;padding: 10px">COMPROVATIVO DO DEPOSITO</th>
                </tr>

                <tr style="background-color: #3F51B5;color: #ffffff">
                    <th style="text-align: center;padding: 4px 2px" >Nº Deposito</th>
                    <th style="text-align: center;padding: 4px 2px" >Matricula</th>
                    <th style="text-align: center;padding: 4px 2px" >Estudante</th>
                    <th style="text-align: center;padding: 4px 2px" >Saldo depositado</th>
                    <th style="text-align: center;padding: 4px 2px" >Saldo apos Movimento</th>
                    <th style="text-align: center;padding: 4px 2px" >Forma Pagamento</th>
                    <th style="text-align: center;padding: 4px 2px" >Operador</th>
                    <th style="text-align: center;padding: 4px 2px" >Ano Lectivo</th>
                    <th style="text-align: center;padding: 4px 2px" >Data</th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <td style="text-align: center;">{{ $item->codigo ?? '' }}</td>
                    <td style="text-align: center;">{{ $item->codigo_matricula_id ?? '' }}</td>
                    <td style="text-align: center;">
                        {{ $item->matricula->admissao->preinscricao->Nome_Completo ?? '' }}
                    </td>
                    <td style="text-align: center;">{{ number_format($item->valor_depositar ?? 0, 2, ',', '.') }} kz</td>
                    <td style="text-align: center;">{{ number_format($item->saldo_apos_movimento ?? 0, 2, ',', '.') }} kz</td>
                    <td style="text-align: center;">{{ $item->forma_pagamento->descricao ?? '' }}</td>
                    <td style="text-align: center;">{{ $item->user->nome ?? '' }}</td>
                    <td style="text-align: center;">{{ $item->ano_lectivo->Designacao ?? '' }}</td>
                    <td style="text-align: center;">{{ date("Y-m-d", strtotime($item->created_at ?? ''))  }}</td>
                </tr>
            </tbody>
        </table>
    </main> --}}

</body>
</html>
