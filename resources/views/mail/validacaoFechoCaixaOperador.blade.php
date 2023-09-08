<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=p, initial-scale=1.0">
    <title>{{ $assunto }}</title>
</head>

<body>
    <p>
        ​Saudações Caro(a) Operador(a) {{ $nome_user }}
    </p>
    <p>
        Gostariamos de informá-lo(a) que o seu fecho de caixa referente ao <strong>{{$caixa}}</strong> aberto em {{$data_abertura_caixa }} e encerrado em {{ $data_fecho_caixa }} foi <strong>{{$descricao}}</strong> pelo(a) Supervisor(a) {{ $admin->nome }} no dia {{ $data_validacao }}
        <strong>{{ ($movimento && $movimento->status_admin=='nao validado') ? 'Pelo motivo: '+$movimento->motivo_rejeicao : ''}}</strong>
    </p>
    <p>
        Clica no link abaixo para acessar o sistema 👇
    </p>
    <p>
        <b>Link: </b>
        <a href="{{url($linkLogin)}}" target="_blank">{{$linkLogin}}</a> <br>
    </p>

    <p>
        Para toda e qualquer questão, contacte o seu supervisor de caixa.
    </p>

    <p style="Margin:0;-webkit-text-size-adjust:none;-ms-text-size-adjust:none;mso-line-height-rule:exactly;font-size:11px;font-family:arial, 'helvetica neue', helvetica, sans-serif;line-height:17px;color:#333333">©&nbsp; Copyright&nbsp;{{ $ano }}&nbsp;<strong><span>MUTUE-CASH</span></strong>. Todos os direitos reservados</p>
</body>

</html>