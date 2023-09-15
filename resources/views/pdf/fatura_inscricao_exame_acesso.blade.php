<?php use SimpleSoftwareIO\QrCode\Facades\QrCode; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Factura</title>

  <link rel="stylesheet" href="css/style_matricula.css" media="all" />

</head>
<body>

<header class="clearfix">
      <div id="logo">
        <img src="img/logo.png">
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

<h5 style="background-color: lightgray;"><center>COMPROVATIVO DE PAGAMENTO - FACTURA</center></h5>

<!--div style="text-align: right;font-size:10px;"><i>Ano Lectivo: {{--$aluno->anoLectivo--}}</i></div-->

  <table style="font-size:10px;">
    <thead>
        <tr>
          <th style="text-align: left;padding: 1px; background-color: lightgray;"><b>Fatura Nº: </b>{{$aluno->numero_fatura}}</th>
          <th style="text-align: right;padding: 1px;">&nbsp;&nbsp;&nbsp;&nbsp;</th>
          <th style="text-align: left;padding: 1px; border-top: solid 1px lightgray;border-left: solid 1px lightgray; border-right: solid 1px lightgray;">Nº Pre-inscrição: {{$aluno->codigo_preinscricao}}</th>
        </tr>

        <tr>
          <th style="text-align: left;padding: 1px;background-color: lightgray;">Data de Movimento: {{$aluno->DataFactura}}</th>
          <th style="text-align: right;padding: 1px;">&nbsp;&nbsp;&nbsp;&nbsp;</th>
          <th style="text-align: left;padding: 1px; border-left: solid 1px lightgray; border-right: solid 1px lightgray;">Nome: {{$aluno->Nome_Completo}}</th>
        </tr>

        <tr>
          <th style="text-align: left;padding: 1px; background-color: lightgray;">Valor Depositado: {{number_format($aluno->valor_depositado,2,",",".")}}</th>
          <th style="text-align: right;padding: 1px;">&nbsp;&nbsp;&nbsp;&nbsp;</th>
          <th style="text-align: left;padding: 1px; border-left: solid 1px lightgray; border-right: solid 1px lightgray;">Curso: {{$aluno->curso}}</th>
        </tr>

        <tr>
          @php
          /*$saldo=0;
          $saldo=$aluno->valor_depositado-$aluno->TotalPreco;*/
          @endphp
          <th style="text-align: left;padding: 1px;background-color: lightgray;"> Moeda: KZ <br>  {{--number_format($aluno->saldo,2,",",".")--}}<br>

        </th>
          <th style="text-align: right;padding: 1px;">&nbsp;&nbsp;&nbsp;&nbsp;</th>
          <th style="text-align: left;padding: 1px;border-bottom: solid 1px lightgray;border-left: solid 1px lightgray; border-right: solid 1px lightgray;"><b>Turno:</b> {{$aluno->turno}} <br><b>Polo:</b> {{$aluno->polo}}</th>
        </tr>


    </thead>

</table>

</div>

<table style="font-size: 10px!important;"  >
  <thead >
    <tr style="">
      <th style="background-color: #2e306e; color:white; border:solid 1px white; padding: 0px;" >Nº</th>
      <!--th class="wd-40p">Valor</th-->
      <th style="text-align: left!important;background-color: #2e306e; color:white;font-size:10px; border:solid 1px white; padding: 0px;" >Descrição&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</th>
      <th style="background-color: #2e306e; color:white; border:solid 1px white; padding: 0px;">Preço Unit.</th>
      <th style="background-color: #2e306e; color:white; border:solid 1px white; padding: 0px;">Qtd.</th>
      <th style="background-color: #2e306e; color:white; border:solid 1px white; padding: 0px;">Multa</th>
      <th style="background-color: #2e306e; color:white; border:solid 1px white; padding: 0px;">Desc.</th>
      <th style="background-color: #2e306e; color:white;border:solid 1px white; padding: 0px;" >Total</th>
    </tr>
    <thead >
      <tbody style="text-align: center!important">
        @foreach($faturas as $item=> $fatura)
        <tr >
          <td style=""> {{++$item}}</td>
          <td style="text-align: left!important;font-size: 11px!important;" >


             {{$fatura->servico}}

          </td>

         <?php
              $desconto = 0;
              $desconto = ($fatura->desconto/$fatura->total)*100;
          ?>


          <th style="">{{number_format($fatura->preco,2,",",".")}}</th>
          <th style="">1</th>
          <th style="">{{ number_format(0,2,",",".")}}</th>
          <th style="">{{ number_format($desconto,2,",",".")}} %</th>
          <td style="">
          <span>{{number_format($fatura->total,2,",",".")}}</span>
          </td>
        </tr>
        @endforeach
 </tbody>
 </table>

 <table style="margin-right:0px">
    <thead>
        <tr>
            <th style="text-align:left;padding: 0px;font-size:9px;">COORDENADAS BANCÁRIAS<br>
            BAI: AKZ 12761513810001<br>
            IBAN:AO06 004000002761513810122<br>
            KEVE: AKZ 133241110001
            </th>
            <th style="text-align:left;padding: 0px;font-size:9px;"></th>
            <th style="text-align:left;padding: 0px;font-size:9px;">Total da Factura<br>
            Multa<br>
            Desconto<br>
            Total a Pagar<br>
            Total Pago<br>
            <!--Saldo de Movimento<br>-->
            <span>&nbsp;</span>
            </th>
            <th style="text-align:right;padding: 0px;font-size:9px;">
            {{number_format($aluno->TotalPreco,2,",",".")}}<br>
            {{number_format(0,2,",",".")}}<br>
            {{number_format($aluno->Desconto,2,",",".")}}<br>
            {{number_format($aluno->ValorAPagar,2,",",".")}}<br>
            {{number_format($aluno->valor_depositado,2,",",".")}}<br>
            {{--number_format($aluno->Troco,2,",",".")--}}<!--br-->
            <span>&nbsp;</span>
            </th>



        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align:left;padding: 0px;font-size:9px;"> São {{$extenso}}</td>
        </tr>

    </tbody>
 </table>

 <div style="font-size: 10px;">
      <span style="width: 90%; word-break: break-all; float: left;text-justify: distribute-all-lines;"> Observação:
        @if ($aluno->obs)
        {{$aluno->obs}} <br>
        @endif

      </span>
      <?php echo '<img style="Width:10%;  float: right;" src="data:image/png;base64,'. base64_encode(QrCode::format("png")->size(200)->generate("Pre-inscrição: ".$aluno->codigo_preinscricao ."  Fatura: ".$aluno->Nome_Completo ."  Ano Lectivo: ".$aluno->anoLectivo. "  Total Fatura: ".$aluno->TotalPreco)) .'">';?>
 </div>

 <div >
    <br>
    <br><br><br>
    <p style="text-align:center;">Assinatura<br><br>
        __________________________ <br><br>
        {{ Auth::user()->nome ?? '' }}

    </p>
</div>

<footer style="width: 100%; left: -10px; font-size: 10px!important;">
Documento processado pelo software MUTUE - Gestão Universitária, desenvolvido pela Mutue - Soluções Tecnológicas Inteligentes.</td>

</footer>

</body>

</html>
