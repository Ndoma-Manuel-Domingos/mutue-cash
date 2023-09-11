<?php
use Illuminate\Support\Str;
?>

<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>TICKET</title>
    <style>
        
        *{
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 2px 0;
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
        style="width: 250px; padding: 5px 35px 5px 15px; overflow: hidden; position:relative; border: 1px solid #999; text-transform:uppercase; margin: 5px 0px 0px 5px; font: bold 15px 'Courier New';">

        <!-- <center><img src="{{ asset('img/logo.png') }}" width="140px" /> </center> -->



        <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td class="titulo-cupom" style="line-height: 10px;font-weight:bold;margin-bottom: 0px;">
                    MUTUE CASH<br><br>
                </td>
            </tr>
            <tr>
                <td class="descricao"
                    style="text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px;font-size:10px;">
                    KINAXIXI - RUA DA MUXIMA
                </td>
            </tr>
            <tr>
                <td class="descricao"
                    style="text-align: left;font-weight:bold; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
                    https://cash.mutue.ao
                </td>
            </tr>
            <tr>
                <td class="descricao"
                    style="font-weight:bold;text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
                    TEL:
                    900-000-000/034-346-346
                </td>
            </tr>
            <tr>
                <td class="descricao"
                    style="font-weight:bold;text-align: left; line-height: 10px; margin-bottom: 0px;padding-right: 12px; font-size:10px;">
                    NIF: 5500034634
                </td>
            </tr>
        </table>
        <hr style="border-width: 1px; border-style: dashed;">


        <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td class="titulo-cupom" style="font-weight:bold;line-height: 15px;font-size:14px; margin-bottom: 0px;">
                    NOME ESTUDANTE
                </td>
            </tr>

            <tr>
                <td class="descricao"
                    style="font-size:15px; line-height:14px;text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
                    ENDERECO ESTUDANTE
                </td>
            </tr>
            <tr>
                <td class="descricao"
                    style="font-size:15px; line-height:14px;text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
                    TEL: 657-445-458
                </td>
            </tr>
        </table>
        <hr style="border-width: 1px; border-style: dashed;">
                
        
        <div class="titulo-cupom" style="text-align: left;line-height: 15px; text-align: center; margin-bottom: 0px;">FR AGT2023/5463</div>

        <table width="100%" border="0" cellspacing="0" cellpadding="0">
           
            <tr width="100%" class="descricao-produto" style="font: bold 8px 'Courier New';line-height: 10px;">
              <td colspan=4>FORMA PAGAMENTO: NÚMERARIO</td>
            </tr>
            <tr class="descricao-produto" style="font: bold 8px 'Courier New'; line-height: 10px;">
              <td style="font-weight:bold;" colspan=2>DESC.</td>
              <td style="font-weight:bold;">QTD.</td>
              <td style="text-align: right;font-weight:bold;">P.UNIT. </td>
              <td style="text-align: right;font-weight:bold;">SUBTOTAL</td>
            </tr>
            
            <tr class="descricao" style="text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
              <td colspan=2 style="width: 200px;font-weight:bold;">Deposito</td>
              <td style="font-weight:bold;">3</td>
              <td style="text-align:right;font-weight:bold;">
                34.000,34
              </td>
              <td style="text-align:right;font-weight:bold;">
                68.000,34
              </td>
            </tr>
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
              <td style="font-weight:bold;">TOTAL:</td>
              <td align="right" style="font-weight:bold;">
                <?php echo number_format((0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="descricao" style="text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
              <td style="font-weight:bold;">IVA:</td>
              <td align="right" style="font-weight:bold;"><?php echo number_format((0), 2, ',', '.'); ?>
              </td>
            </tr>
            <tr class="descricao" style="text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
              <td style="font-weight:bold;">DESCONTO:</td>
              <td align="right" style="font-weight:bold;"><?php echo number_format((0), 2, ',', '.'); ?></td>
            </tr>
            
            <tr class="descricao" style="text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
              <td style="font-weight:bold;">TOTAL À PAGAR:</td>
              <td align="right" style="font-weight: bold;">
                <?php echo number_format(0, 2, ',', '.'); ?></td>
            </tr>
      
            <tr class="descricao" style="text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
              <td style="font-weight:bold;">VALOR CASH:</td>
              <td style="font-weight:bold;" align="right" style="font-weight: bold;">
                <?php echo number_format((0), 2, ',', '.'); ?></td>
            </tr>
            <tr class="descricao" style="text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
              <td style="font-weight:bold;">VALOR MULTICAIXA:</td>
              <td style="font-weight:bold;" align="right" style="font-weight: bold;">
                <?php echo number_format((0), 2, ',', '.'); ?></td>
            </tr>
      
      
            <tr class="descricao" style="text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
              <td style="font-weight:bold;">VALOR PAGO:</td>
              <td style="font-weight:bold;" align="right" style="font-weight: bold;">
                <?php echo number_format((0), 2, ',', '.'); ?></td>
            </tr>
           
      
            <tr class="descricao" style="text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
              <td style="font-weight:bold;">TROCO:</td>
              <td align="right" style="font-weight: bold;"><?php echo number_format(0, 2, ',', '.'); ?></td>
            </tr>
      
           
            <tr class="descricao" style="text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
              <td style="font-weight:bold;">TOTAL À PAGAR:</td>
              <td align="right" style="font-weight: bold;">
                <?php echo number_format((0), 2, ',', '.'); ?>
            </td>
            </tr>
      
          </table>
          <br>
      
        
        
        
        
        


        <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td class="descricao"
                    style="font-weight:bold;text-align: left; line-height: 10px; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
                    Operador: Ndoma</td>
            </tr>
            <tr class="descricao" style=" font-size:8px;">
                <td width="100%">Programa Validado nº 395/AGT/2023</td>
            </tr>
            <tr class="descricao" style=" font-size:8px;">
                <td width="100%">Regime Empresa</td>
            </tr>
            <tr class="descricao" style="text-align: left; margin-bottom: 0px; padding-right: 12px; font-size:10px;">
                <td width="50%" align="left" style="font-weight:bold;font-size:8px;">Data: 12-12-2023</td>
            </tr>
        </table>
        <div class="titulo-cupom"
            style="line-height: 15px; text-align: center; margin-bottom: 0px;font-size:8px; text-transform: lowercase;">
            Obrigado e volte sempre
        </div>
    </div>

</body>
<script src="{{ asset('assets/plugins/jquery/jquery.min.js') }}"></script>

<!--
<script>
    /* $(document).ready(function() {

        let contador = 0;
        let wasKitchen = false;
        let balcao = 2;

        <?php
        // if ($checkCozinha) {
        ?>
            wasKitchen = true;
        <?php
        ///}
        ?>
        <?php
        //if ($balcao == 1) {
        ?>
            balcao = 1;
        <?php
        //}
        ?>

        imprimirFactura(contador);

        function imprimirFactura(contador) {
            if (wasKitchen && contador == 3) {
                if (balcao == 1) {
                    window.top.location = "/venda/balcao";
                }
                return;
            } else if (!wasKitchen && contador == 2) {
                if (balcao == 1) {
                    window.top.location = "/venda/balcao";
                }
                return;
            }
            contador++;
            window.print();
            imprimirFactura(contador);
        }
    });*/
</script>
-->

</html>
