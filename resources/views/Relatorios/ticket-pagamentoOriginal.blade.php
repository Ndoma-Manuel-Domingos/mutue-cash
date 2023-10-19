<?php
use Illuminate\Support\Str;
?>

<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>FACTURA RECIBO</title>
    <style>
        
        *{
            padding: 2px 0;
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
        }
        hr{
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
                <td class="titulo-cupom" style="font-weight:bold;line-height: 15px;font-size:10px; margin-bottom: 0px;">
                    {{ $aluno->Nome_Completo }}
                </td>
            </tr>

            <tr>
                <td class="descricao"
                    style="font-size:10px; line-height:14px;text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
                    Nº Mat: {{ $aluno->codigo_matricula }}
                </td>
            </tr>
            <tr>
                <td class="descricao"
                    style="font-size:10px; line-height:14px;text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
                    Curso: {{ $aluno->curso }}
                </td>
            </tr>
            <tr>
                <td class="descricao"
                    style="font-size:10px; line-height:14px;text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
                    TURNO: {{ $aluno->turno }}
                </td>
            </tr>
        </table>
        <hr style="border-width: 1px; border-style: dashed;">
                
        @if ($aluno->valor_depositado > 0)
            <div class="titulo-cupom" style="text-align: left;line-height: 15px; text-align: center; margin-bottom: 0px;">Recibo: {{ $aluno->numero_fatura }}</div>
        @else
            <div class="titulo-cupom" style="text-align: left;line-height: 15px; text-align: center; margin-bottom: 0px;">Factura: {{ $aluno->numero_fatura }}</div>
        @endif

        <table width="100%" border="0" cellspacing="0" cellpadding="0">
           
            <tr width="100%" class="descricao-produto" style="font: bold 8px 'Courier New';line-height: 10px;">
              <td colspan=4>FORMA PAGAMENTO: NÚMERARIO</td>
            </tr>
            <tr class="descricao-produto" style="font: bold 8px 'Courier New'; line-height: 10px;">
              <td style="font-weight:bold;" colspan=2>DESC.</td>
              <td style="font-weight:bold;">PRES.</td>
              <td style="text-align: right;font-weight:bold;">P.| </td>
              <td style="text-align: right;font-weight:bold;">QTD.|</td>
              <td style="text-align: right;font-weight:bold;">MUT.|</td>
              <td style="text-align: right;font-weight:bold;">DESC.|</td>
              <td style="text-align: right;font-weight:bold;">TOTAL.|</td>
            </tr>
            
            @foreach ($faturas as $item => $fatura)
                <tr class="descricao" style="text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:8px;">
                  <td colspan=2 style="width: 200px;font-weight:bold;">
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
                    <td style="font-weight:bold;">{{ $fatura->prestacao }}ª de {{ $qtdPrestacoes }}</td>
                  @else
                    <td  style="font-weight:bold;">
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
                        $desconto = $fatura->preco>0 ? (($fatura->desconto / $fatura->preco) * 100):0;
                    ?>
                    
                    <th style="text-align:right;font-weight:bold;">{{ number_format($fatura->preco, 2, ',', '.') }}</th>
                    <th style="text-align:right;font-weight:bold;">{{ isset($fatura->qtd) }}</th>
                    <th style="text-align:right;font-weight:bold;">{{ number_format($fatura->multa, 2, ',', '.') }}</th>
                    <th style="text-align:right;font-weight:bold;">{{ number_format($desconto, 2, ',', '.') }} %</th>
                    <td style="text-align:right;font-weight:bold;">
                        <span>{{ number_format($fatura->total * $fatura->qtd, 2, ',', '.') }}</span>
                    </td>
                  
                </tr>
            @endforeach
        </table>
        <br>
        
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td class="descricao" style="font-weight:bold;text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:10px;">Observação: <br> descricao</td>
            </tr>
        </table>
        <br>
        
        
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr class="descricao" style="text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
              <td style="font-weight:bold;">Total da Factura:</td>
              <td align="right" style="font-weight:bold;"> {{ number_format($aluno->TotalFatura, 2, ',', '.') }}</td>
            </tr>
            <tr class="descricao" style="text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
              <td style="font-weight:bold;">Multa:</td>
              <td align="right" style="font-weight:bold;">{{ number_format($aluno->multa, 2, ',', '.') }}
              </td>
            </tr>
            <tr class="descricao" style="text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
              <td style="font-weight:bold;">Desconto:</td>
              <td align="right" style="font-weight:bold;">{{ number_format($aluno->desconto, 2, ',', '.') }}</td>
            </tr>
            
            <tr class="descricao" style="text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
              <td style="font-weight:bold;">Total a Pagar:</td>
              <td align="right" style="font-weight: bold;">{{ number_format($total_apagar, 2, ',', '.') }}</td>
            </tr>
      
            <tr class="descricao" style="text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
              <td style="font-weight:bold;">Total Pago:</td>
              <td style="font-weight:bold;" align="right" style="font-weight: bold;">{{ $aluno->ValorEntregue > 0 ? number_format($aluno->ValorEntregue, 2, ',', '.') : number_format(0, 2, ',', '.') }}</td>
            </tr>
            
            @if ($aluno->negociacao)
            <tr class="descricao" style="text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
              <td style="font-weight:bold;">50% da Dívida<br>
                Valor por prestação<br>
                Total de prestações<br></td>
              <td style="font-weight:bold;" align="right" style="font-weight: bold;">
                {{ number_format($aluno->primeiroValorApagar, 2, ',', '.') }} <br>
                {{ number_format($aluno->valorPrestacoes, 2, ',', '.') }}<br>
                {{ number_format($aluno->primeiroValorApagar, 2, ',', '.') }}<br>
              </td>
            </tr>
            @endif
      
      
            <tr class="descricao" style="text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
              <td style="font-weight:bold;">Troco:</td>
              <td style="font-weight:bold;" align="right" style="font-weight: bold;">
                {{ number_format($aluno->troco, 2, ',', '.') }}</td>
            </tr>
           
      
            <tr class="descricao" style="text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
              <td style="font-weight:bold;">Reserva Anterior:</td>
              <td align="right" style="font-weight: bold;">{{ number_format($aluno->saldo_anterior, 2, ',', '.') }}</td>
            </tr>
      
           
            <tr class="descricao" style="text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
              <td style="font-weight:bold;">Reserva Actual:</td>
              <td align="right" style="font-weight: bold;">
                {{ number_format($aluno->saldo, 2, ',', '.') }}
            </td>
            </tr>
      
          </table>
          <br>
      
  
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td class="descricao"
                    style="font-weight:bold;text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
                    Operador: {{ $pagamento_utilizador->nome ?? '' }}</td>
            </tr>
            <tr class="descricao" style="text-align: left; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
                <td width="50%" align="left" style="font-weight:bold;font-size:8px;">Data: {{ $aluno->DataFactura }}</td>
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
