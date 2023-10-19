<?php
use Illuminate\Support\Str;
?>

<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Nº de Depósito: {{ $item->codigo }}</title>
    <style>
        * {
            padding: 2px 0;
            margin: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        hr {
            padding: 0;
        }

        @media print {
            #noprint {
                display: none;
            }
        }
    </style>

</head>

<body style="margin-top: 0px;margin-left: 0px;">
    <div id="app" class="cupom"
        style="width: 210px; padding: 5px 35px 5px 15px; overflow: hidden; position:relative; border: 1px solid #999; text-transform:uppercase; margin: 5px 0px 0px 5px; font: bold 15px 'Courier New';">

        <center><img src="{{ public_path('images/logotipo.png') }}" width="150px" /> <br><br></center>

        <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td class="titulo-cupom" style="line-height: 10px;font-weight:bold;margin-bottom: 0px;">
                    MUTUE CASH<br><br>
                </td>
            </tr>
            <tr>
                <td class="descricao"
                    style="text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px;font-size:10px;">
                    Rua Nossa Senhora da Muxima Nº 10.
                    <br>Bairro Kinaxixi, Luanda.
                </td>
            </tr>
            <tr>
                <td class="descricao"
                    style="font-weight:bold;text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
                    TEL: +244 947716133/+244 942364667
                </td>
            </tr>
            <tr>
                <td class="descricao"
                    style="font-weight:bold;text-align: left; line-height: 10px; margin-bottom: 0px;padding-right: 12px; font-size:10px;">
                    NIF: 5401150865
                </td>
            </tr>
        </table>
        <hr style="border-width: 1px; border-style: dashed;">


        <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td class="titulo-cupom" style="font-weight:bold;line-height: 15px;font-size:14px; margin-bottom: 0px;">
                    {{ $item->codigo_matricula_id > 0 ? '' : ($item->candidato ? 'Candidato: ' : 'Estudante') }}
                    {{ $item->matricula->admissao->preinscricao->Nome_Completo ?? ($item->candidato->Nome_Completo ?? '') }}
                </td>
            </tr>

            <tr>
                <td class="descricao"
                    style="font-size:15px; line-height:14px;text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
                    {{ $item->codigo_matricula_id > 0 ? 'Nº MAT:' : ($item->candidato ? 'Nº CAN: ' : 'Estudante') }}
                    {{ $item->codigo_matricula_id ?? ($item->candidato->Codigo ?? '') }}
                </td>
            </tr>
            <tr>
                <td class="descricao"
                    style="font-size:15px; line-height:14px;text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
                    {{ $item->codigo_matricula_id > 0 ? 'TEL:' : ($item->candidato ? 'TEL: ' : '000-000-000') }}
                    {{ $item->codigo_matricula_id ?? ($item->candidato->Contactos_Telefonicos ?? '') }}
                </td>
            </tr>
        </table>
        <hr style="border-width: 1px; border-style: dashed;">

        <div class="titulo-cupom"
            style="text-align: left;line-height: 15px; text-align: center; margin-bottom: 0px;padding-top: 10px">Nº de
            Depósito: {{ $item->codigo }}/{{ date('Y', strtotime($item->created_at)) }}</div>

        <table width="100%" border="0" cellspacing="0" cellpadding="0">

            <tr width="100%" class="descricao-produto" style="font: bold 8px 'Courier New';line-height: 10px;">
                <td colspan=4>FORMA PAGAMENTO: DEPOSITO</td>
            </tr>
            <tr width="100%" class="descricao-produto" style="font: bold 8px 'Courier New';line-height: 10px;">
                <td colspan=4>MOEDA: AOA</td>
            </tr>
            <tr class="descricao-produto" style="font: bold 8px 'Courier New'; line-height: 10px;">
                <td style="font-weight:bold;" colspan=2>DESC.</td>
                <td style="font-weight:bold;">VALOR DEP.</td>
                <td style="text-align: right;font-weight:bold;">VALOR APÓS DEP. </td>
                <td style="text-align: right;font-weight:bold;">RESERVA DISPONÍVEL</td>
            </tr>

            <tr class="descricao"
                style="text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
                <td colspan=2 style="width: 200px;font-weight:bold;">Deposito</td>
                <td style="font-weight:bold;">{{ number_format($item->valor_depositar ?? 0, 2, ',', '.') }}</td>
                <td style="text-align:right;font-weight:bold;">
                    {{ number_format($item->saldo_apos_movimento ?? 0, 2, ',', '.') }}</td>
                <td style="text-align:right;font-weight:bold;">
                    {{ number_format($item->matricula->admissao->preinscricao->saldo ?? 0, 2, ',', '.') }}</td>
            </tr>
        </table>
        <br>

        <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td class="descricao"
                    style="font-weight:bold;text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
                    Observação: <br> {{ $item->observacao ?? '' }}</td>
            </tr>
        </table>
        <br>


        <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td class="descricao"
                    style="font-weight:bold;text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
                    Operador: {{ $item->user->nome ?? '' }}</td>
            </tr>
            <tr class="descricao" style="text-align: left; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
                <td width="50%" align="left" style="font-weight:bold;font-size:8px;">Data:
                    {{ $item->created_at ?? '' }}</td>
            </tr>
        </table>
        <div class="titulo-cupom"
            style="line-height: 15px; text-align: center; margin-bottom: 0px;font-size:8px; text-transform: lowercase;">
            Obrigado e volte sempre
        </div>
    </div>

</body>

<script>
    window.print();
</script>

</html>
