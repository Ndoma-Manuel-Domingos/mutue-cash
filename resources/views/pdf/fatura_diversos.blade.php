<?php use SimpleSoftwareIO\QrCode\Facades\QrCode; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Factura</title>

    <link rel="stylesheet" href="css/style_matricula.css" media="all" />

    <style type="text/css">
        * {
            font-family: Arial, Helvetica, sans-serif;
            padding: 0;
            margin: 0;
        }

        .footer {
            color: #777777;
            width: 100%;
            height: 30px;
            position: relative;
            bottom: 0;
            border-top: 1px solid #AAAAAA;
            padding: 8px 0;
            text-align: center;
        }
    </style>
</head>

<body>
    <main>
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
            </div>
        </header>


        <div style="border-bottom: 0px solid black;">

            <h5 style="background-color: lightgray;">
                @if ($aluno->ValorEntregue > 0 || ($aluno->estado > 0 && $aluno->estado <= 2))
                    <center>FACTURA RECIBO</center>
                @else
                    <center>FACTURA</center>
                @endif
            </h5>
            <!--li ><h3><center style="font-size: 14px!important;margin-top: 0px">FATURA Nº &nbsp; {{ $aluno->numero_fatura }}/{{ date('Y') }}</center> </h3></li-->
            <div style="text-align: right;font-size:10px;"><i>Ano Lectivo: {{ $aluno->anoLectivo }}</i></div>

            <table style="font-size:10px;">
                <thead>
                    <tr>
                        @if ($aluno->valor_depositado > 0)
                            <th style="text-align: left;padding: 1px; background-color: lightgray;"><b>Recibo:
                                </b>{{ $aluno->numero_fatura }}</th>
                        @else
                            <th style="text-align: left;padding: 1px; background-color: lightgray;"><b>Factura:
                                </b>{{ $aluno->numero_fatura }}</th>
                        @endif
                        <th style="text-align: right;padding: 1px;">&nbsp;&nbsp;&nbsp;&nbsp;</th>
                        <th
                            style="text-align: left;padding: 1px; border-top: solid 1px lightgray;border-left: solid 1px lightgray; border-right: solid 1px lightgray;">
                            Nº Matrícula: {{ $aluno->codigo_matricula }}</th>
                    </tr>

                    <tr>
                        <th style="text-align: left;padding: 1px;background-color: lightgray;">Data de Movimento:
                            {{ $aluno->DataFactura }}</th>
                        <th style="text-align: right;padding: 1px;">&nbsp;&nbsp;&nbsp;&nbsp;</th>
                        <th
                            style="text-align: left;padding: 1px; border-left: solid 1px lightgray; border-right: solid 1px lightgray;">
                            Nome: {{ $aluno->Nome_Completo }}</th>
                    </tr>

                    <tr>
                        @if ($pagamento)
                            @if ($pagamento->Status == 'PAID')
                                <th style="text-align: left;padding: 1px; background-color: lightgray;">Valor
                                    Depositado:
                                    {{ number_format($aluno->valor_depositado, 2, ',', '.') }}</th>
                                <th style="text-align: right;padding: 1px;">&nbsp;&nbsp;&nbsp;&nbsp;</th>
                            @else
                                <th style="text-align: left;padding: 1px; background-color: lightgray;">Valor a Pagar:
                                    {{ number_format($pagamento->AMOUNT, 2, ',', '.') }}</th>
                                <th style="text-align: right;padding: 1px;">&nbsp;&nbsp;&nbsp;&nbsp;</th>
                            @endif
                        @else
                            <th style="text-align: left;padding: 1px; background-color: lightgray;"></th>
                            <th style="text-align: right;padding: 1px;">&nbsp;&nbsp;&nbsp;&nbsp;</th>
                        @endif
                        <th
                            style="text-align: left;padding: 1px; border-left: solid 1px lightgray; border-right: solid 1px lightgray;">
                            Curso: {{ $aluno->curso }}
                        </th>
                    </tr>

                    <tr>
                        <th style="text-align: left;padding: 1px;background-color: lightgray;"> Moeda: KZ

                        </th>
                        <th style="text-align: right;padding: 1px;">&nbsp;&nbsp;&nbsp;&nbsp;</th>
                        <th
                            style="text-align: left;padding: 1px;border-bottom: solid 1px lightgray;border-left: solid 1px lightgray; border-right: solid 1px lightgray;">

                            <b>Turno:</b> {{ $aluno->turno }}
                            <br><b>Polo:</b> {{ $aluno->polo }} <b>Polo de pag.:</b>
                            @if ($aluno->AlunoCacuaco == 'SIM')
                                <span> Cacuaco</span>
                            @else
                                <span>Kinaxixi</span>
                            @endif
                        </th>
                    </tr>

                    @if ($pagamento)
                        <tr>
                            <th style="text-align: left;padding: 1px; background-color: lightgray;"><b>Entidade:
                                </b> <b>{{ $pagamento->ENTITY_ID }}</b>
                            </th>
                        </tr>
                        <tr>
                            <th style="text-align: left;padding: 1px; background-color: lightgray;"><b>Referência:
                                </b><b>{{ $pagamento->REFERENCE }}</b>
                            </th>
                        </tr>
                    @endif

                </thead>

            </table>

        </div>

        <table style="font-size: 10px!important;">
            <thead>
                <tr style="">
                    <th style="background-color: #2e306e; color:white; border:solid 1px white; padding: 0px;">Nº</th>
                    <!--th class="wd-40p">Valor</th-->
                    <th
                        style="text-align: left!important;background-color: #2e306e; color:white;font-size:10px; border:solid 1px white; padding: 0px;">
                        Descrição&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    </th>
                    <th style="background-color: #2e306e; color:white; border:solid 1px white; padding: 0px;">Prestação
                    </th>
                    <th style="background-color: #2e306e; color:white; border:solid 1px white; padding: 0px;">Preço
                        Unit.</th>
                    <th style="background-color: #2e306e; color:white; border:solid 1px white; padding: 0px;">Qtd.</th>
                    <th style="background-color: #2e306e; color:white; border:solid 1px white; padding: 0px;">Multa</th>
                    <th style="background-color: #2e306e; color:white; border:solid 1px white; padding: 0px;">Desc.</th>
                    <th style="background-color: #2e306e; color:white;border:solid 1px white; padding: 0px;">Total</th>
                </tr>
            </thead>

            <tbody style="text-align: center!important">
                @foreach ($faturas as $item => $fatura)
                    <tr>
                        <td style=""> {{ ++$item }}</td>
                        <td style="text-align: left!important;font-size: 11px!important;">
                            @if ($fatura->disciplina)
                                @if ($fatura->avaliacao)
                                    @if ($fatura->avaliacao == 7)
                                        Recurso de {{ $fatura->disciplina }}
                                    @elseif($fatura->avaliacao == 22)
                                        Melhoria de {{ $fatura->disciplina }}
                                    @elseif($fatura->avaliacao == 11)
                                        Exame de Ep. Especial de {{ $fatura->disciplina }}
                                    @endif
                                @else
                                    Inscrição Cad. {{ $fatura->disciplina }}
                                @endif
                            @elseif ($fatura->servico)
                                {{ $fatura->servico }}
                            @endif
                        </td>

                        @if ($fatura->prestacao > 0)
                            <td style="">{{ $fatura->prestacao }}ª de {{ $qtdPrestacoes }}</td>
                        @else
                            <td style="">
                                @if ($aluno->negociacao)
                                    {{ $fatura->mes }}-{{ $fatura->anoLectivo }}
                                @elseif ($fatura->mes)
                                    {{ $fatura->mes }}
                                @else
                                    #
                                @endif
                            </td>
                        @endif
                        <?php
                        
                        $desconto = 0;
                        
                        $desconto = $fatura->preco > 0 ? ($fatura->desconto / $fatura->preco) * 100 : 0;
                        
                        ?>
                        <th style="">{{ number_format($fatura->preco, 2, ',', '.') }}</th>
                        <th style="">{{ isset($fatura->qtd) }}</th>
                        <th style="">{{ number_format($fatura->multa, 2, ',', '.') }}</th>
                        <th style="">{{ number_format($desconto, 2, ',', '.') }} %</th>
                        <td style="">
                            <span>{{ number_format($fatura->total * $fatura->qtd, 2, ',', '.') }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table style="margin-right:0px">
            <thead>
                <tr>
                    @if ($pagamento)
                        <th style="text-align:left;padding: 0px;font-size:9px;">
                            <h3>PAGAMENTO POR REFERÊNCIA</h3>
                            ENTIDADE: <b>{{ $pagamento->ENTITY_ID }}</b><br>
                            REFERÊNCIA: <b>{{ $pagamento->REFERENCE }}</b><br>
                            MONTANTE: <b>{{ number_format($pagamento->AMOUNT, 2, ',', '.') }} KZs</b><br>
                            ESTADO:
                            <b>{{ $pagamento->Status == 'PAID' ? 'PAGO' : ($pagamento->Status == 'ACTIVE' ? 'PENDENTE' : 'EXPIRADO') }}</b>
                        </th>
                    @else
                        <th style="text-align:left;padding: 0px;font-size:9px; font-weight: bolder">COORDENADAS
                            BANCÁRIAS<br>
                            BAI: AKZ 12761513810001<br>
                            IBAN: IBAN: AO06004000002761513810122<br>
                            KEVE: AKZ 133241110001
                        </th>
                    @endif

                    <th style="text-align:left;padding: 0px;font-size:9px;"></th>
                    <th style="text-align:left;padding: 0px;font-size:9px;">Total da Factura<br>
                        Multa<br>
                        Desconto<br>
                        Total a Pagar<br>
                        Total Pago<br>
                        @if ($aluno->negociacao)
                            50% da Dívida<br>
                            Valor por prestação<br>
                            Total de prestações<br>
                        @endif
                        Troco<br>
                        Reserva Anterior<br>
                        Reserva Actual<br>
                        <span>&nbsp;</span>
                    </th>
                    <th style="text-align:right;padding: 0px;font-size:9px;">
                        {{ number_format($aluno->TotalFatura, 2, ',', '.') }}<br>
                        {{ number_format($aluno->multa, 2, ',', '.') }}<br>
                        {{ number_format($aluno->desconto, 2, ',', '.') }}<br>
                        {{ number_format($total_apagar, 2, ',', '.') }}<br>

                        @if ($pagamento)
                            @if ($pagamento->Status == 'PAID')
                                {{ number_format($aluno->valor_depositado, 2, ',', '.') }}<br>
                            @else
                                {{ number_format(0, 2, ',', '.') }}<br>
                            @endif
                        @else
                            {{ $aluno->ValorEntregue > 0 ? number_format($aluno->ValorEntregue, 2, ',', '.') : number_format(0, 2, ',', '.') }}<br>
                        @endif

                        @if ($aluno->negociacao)
                            {{ number_format($aluno->primeiroValorApagar, 2, ',', '.') }} <br>
                            {{ number_format($aluno->valorPrestacoes, 2, ',', '.') }}<br>
                            {{ number_format($aluno->primeiroValorApagar, 2, ',', '.') }}<br>
                        @endif
                        {{ number_format($aluno->troco, 2, ',', '.') }}<br>
                        {{ number_format($aluno->saldo_anterior, 2, ',', '.') }}<br>
                        {{ number_format($aluno->saldo, 2, ',', '.') }}<br>
                        <span>&nbsp;</span>
                    </th>

                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="text-align:left;padding: 0px;font-size:9px;"> São {{ $extenso }}</td>
                </tr>
            </tbody>
        </table>

        @if (isset($pagamentos))
            <div style="font-size: 10px;">
                <p> <strong>PAGAMENTOS PARCELARES POR REFERÊNCIA</strong></p>
                <table style="font-size: 10px!important;">
                    <thead>
                        <tr style="">
                            <th style="background-color: #2e306e; color:white; border:solid 1px white; padding: 0px;">Nº
                            </th>
                            <!--th class="wd-40p">Valor</th-->
                            <th
                                style="text-align: left!important;background-color: #2e306e; color:white;font-size:10px; border:solid 1px white; padding: 0px;">
                                Referências&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            </th>
                            <th style="background-color: #2e306e; color:white; border:solid 1px white; padding: 0px;">
                                Entidade</th>
                            <th style="background-color: #2e306e; color:white; border:solid 1px white; padding: 0px;">
                                Montante</th>
                            <th style="background-color: #2e306e; color:white; border:solid 1px white; padding: 0px;">
                                Parcela</th>

                            <th style="background-color: #2e306e; color:white; border:solid 1px white; padding: 0px;">
                                Data de Expiração</th>
                            <th style="background-color: #2e306e; color:white; border:solid 1px white; padding: 0px;">
                                Estado</th>
                        </tr>
                    </thead>
                    <tbody style="text-align: center!important">
                        @foreach ($pagamentos as $item => $pagamento)
                            <tr>
                                <td style=""> {{ ++$item }}</td>
                                <td style="text-align: left!important;font-size: 11px!important;">
                                    {{ $pagamento->REFERENCE }}
                                </td>
                                <th style="">{{ $pagamento->ENTITY_ID }}</th>
                                <th style="">{{ number_format($pagamento->AMOUNT, 2, ',', '.') }}</th>
                                <th style="">{{ $pagamento->SOURCE_ID }}</th>
                                <th style="">{{ $pagamento->END_DATE }}</th>
                                <th style="">{{ $pagamento->Status }}</th>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>
        @endif

        <div style="font-size: 10px;">
            <span style="width: 90%; word-break: break-all; float: left;text-justify: distribute-all-lines;">
                <div style="font-weight: bolder">Observação:</div>
                @if (isset($aluno->estado) && $aluno->estado == 0)
                    <p>O pagamento da factura encontra-se em validação</p>
                @endif
                @if ($aluno->obs)
                    {{ $aluno->obs }} <br>
                @endif

                @if ($aluno->negociacao)
                    Deverá ser pago os 50% do valor em dívida
                    <b>{{ number_format($aluno->primeiroValorApagar, 2, ',', '.') }}
                        AKZ </b> que cobrirá <b>{{ $aluno->mesesQuitar }}</b> meses de propina. </p>
                    O valor restante em dívida, que corresponde a
                    <b>{{ number_format($aluno->primeiroValorApagar, 2, ',', '.') }} AKZ</b>, deverá ser pago em
                    <b>{{ $aluno->qtd_prestacoes }}</b> prestações no máximo a partir de {{ $aluno->mes_inicial }} no
                    valor de {{ number_format($aluno->valorPrestacoes, 2, ',', '.') }} AKZ <b></b></p>
                @endif
            </span>
            <?php echo '<img style="Width:10%;  float: right;" src="data:image/png;base64,' .
                base64_encode(
                    QrCode::format('png')
                        ->size(200)
                        ->generate('https://mutue.ao/dados-validacao?numero=' . $aluno->numero_fatura . '&tipo=1'),
                ) .
                '">';
            ?>
        </div>

        <div>
            <br><br><br>
            <p style="text-align:center;">Assinatura<br><br>
                __________________________ <br><br>
                {{ Auth::user()->nome ?? '' }}

            </p>
        </div>

        <br><br><br><br>
        <div class="footer" style="width: 100%; left: -10px; font-size: 8px!important;">
            Documento processado pelo software MUTUE CASH - Gestão Universitária, desenvolvido pela Mutue - Soluções
            Tecnológicas
            Inteligentes.
        </div>
    </main>
</body>

</html>
