<?php

namespace App\Services;
use Illuminate\Http\Request;
use App\PagamentoPorReferencia;
use DB;
use SoapClient;
use SoapFault;
use SoapVar;
use SoapHeader;
use Carbon\Carbon;
use DOMAttr;
use DOMDocument;
use SimpleXMLElement;
use Illuminate\Support\Facades\Log;
class PagamentoPorReferenciaService
{

   private static $response;

  public function __construct()
  {

  }
/*
   $source_id é igual ao código parcelar da fatura na visão do Mutue. É unique no BE, se enviar duas vezes, te retorna a refencia anterior
   $amount = Valor total da factura
*/
public static  function create($data)
{



//Peparando paramentros do Head e Body
$uuid=\Str::uuid();
$source_id=$data['source_id'];
$amount=$data['amount'];
$telefone=$data['telefone'];
$email=$data['email'];
$custumer_name=$data['custumer_name'];//Nome do Aluno
$endereco=$data['endereco'];

$end_date=Carbon::now()->addDays($data['expira_dentro_de'])->format('Y-m-d');


//Timestamp no formato yyyy-MM-dd'T'HH:mm:ss
$timestamp= str_replace(' ','T',Carbon::now()->format("Y-m-d H:i:s"));  //yyyy-MM-dd'T'HH:mm:ss
//Data de criação
$created_at=date('Y-m-d');
$token=env('BE_TOKEN','');
//Criando o envolop SOAP
$xml=<<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:pay="http://www.bancoeconomico.ao/xsd/paymentref">
  <soapenv:Header>
               <wsse:Security xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
               <wsse:UsernameToken wsu:Id="soaAuth">
               <wsse:Username>UMA</wsse:Username>
               <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">passwordUMA1</wsse:Password>
               <wsu:Created>$created_at</wsu:Created>
               </wsse:UsernameToken>
               </wsse:Security>
  </soapenv:Header>
   <soapenv:Body>
      <pay:PaymentRefCreateRequest>
         <pay:HEADER>
            <pay:SOURCE>UMA</pay:SOURCE>
            <pay:MSGID>?</pay:MSGID>
            <pay:USERID>UMA001</pay:USERID>
            <pay:BRANCH>000</pay:BRANCH>
            <pay:PASSWORD>?</pay:PASSWORD>
            <pay:INVOKETIMESTAMP>$timestamp</pay:INVOKETIMESTAMP>
         </pay:HEADER>
         <pay:BODY>
            <pay:Payment>
               <pay:AUTHTOKEN>$token</pay:AUTHTOKEN>
               <pay:ENTITYID>00416</pay:ENTITYID>
               <pay:PRODUCT_NO>1</pay:PRODUCT_NO>
               <pay:SOURCE_ID>$source_id</pay:SOURCE_ID>
               <pay:AMOUNT>$amount</pay:AMOUNT>
               <pay:START_DATE>$created_at</pay:START_DATE>
               <pay:END_DATE>$end_date</pay:END_DATE>
               <pay:CUSTOMER_NAME>$custumer_name</pay:CUSTOMER_NAME>
               <pay:ADDRESS>$endereco</pay:ADDRESS>
               <pay:EMAIL>$email</pay:EMAIL>
               <pay:PHONE_NUMBER>$telefone</pay:PHONE_NUMBER>
            </pay:Payment>
         </pay:BODY>
      </pay:PaymentRefCreateRequest>
   </soapenv:Body>
</soapenv:Envelope>
XML;

$client = new \GuzzleHttp\Client();
$endpoint = "https://spf-webservices-uat.bancoeconomico.ao:7443/soa-infra/services/SPF/WSI_PaymentRefCreate/WSI_PaymentRefCreate";

/* $response=$client->post($endpoint, [
   'headers' => ['Content-Type' => 'text/xml; charset=UTF8'],
   "body" => $xml
]); */

$response = $client->request('POST', $endpoint, [
   'body' => $xml,
   'headers' => [
       "Content-Type" => "text/xml; charset=utf-8"
   ]
]);



Self::$response=$response->getBody()->getContents();


//Retorna um array com detalhes do pagamento por referencia
return Self::toArray();


  }


  /*
  Verifica se a referencia já foi paga.
   $pagamentos_request = pagamentos por consultar
*/

public static  function checkStatus($pagamentos_request)
{

//Peparando paramentros do Head e Body
$uuid=\Str::uuid();
//Timestamp no formato yyyy-MM-dd'T'HH:mm:ss
$timestamp= str_replace(' ','T',Carbon::now()->format("Y-m-d H:i:s"));  //yyyy-MM-dd'T'HH:mm:ss
//Data de criação
$created_at=date('Y-m-d');
$token=env('BE_TOKEN','');

$pagamentos=collect([]);//recebe a lista de pagamentos consultados

$dom = new DOMDocument();


		$dom->encoding = 'utf-8';

		$dom->xmlVersion = '1.0';

		$dom->formatOutput = true;

      //create envelop
		$envelop = $dom->createElement('soapenv:Envelope');

		$envelop->setAttribute('xmlns:soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
		$envelop->setAttribute('xmlns:pay', 'http://www.bancoeconomico.ao/xsd/paymentrefdetails');
      //Criar soapenvHeader
      $soapenvHeader = $dom->createElement('soapenv:Header');
     //Security node
      $security=$dom->createElement('wsse:Security');
      $security->setAttribute('xmlns:wsse', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd');
      $security->setAttribute('xmlns:wsu', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd');


      $soapenvHeader->appendChild($security);
      //creat wsse:UsernameToken
      $usernameToken=$dom->createElement('wsse:UsernameToken');
      $security->setAttribute('wsu:Id','soaAuth');
      $security->appendChild($usernameToken);

      //creat wsse:Username
      $wsseUsername=$dom->createElement('wsse:Username','UMA');
      $usernameToken->appendChild($wsseUsername);
      //crete password
      $wssePassword=$dom->createElement('wsse:Password','passwordUMA1');
      $wssePassword->setAttribute('Type','http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText');
      $usernameToken->appendChild($wssePassword);

      //Create wsu:Created
      $wsuCreated=$dom->createElement('wsu:Created',$created_at);
      $usernameToken->appendChild($wsuCreated);

      //Create soapenv:Body
      $soapenvBody = $dom->createElement('soapenv:Body');
      $envelop->appendChild($soapenvBody);
      //Create pay:PaymentRefDetailsQueryRequest
      $paiment_request=$dom->createElement('pay:PaymentRefDetailsQueryRequest');
      $soapenvBody->appendChild($paiment_request);
      //create pay:HEADER
      $payHEADER=$dom->createElement('pay:HEADER');
      $paiment_request->appendChild($payHEADER);
      //create $payHEADER elements
      $paySOURCE=$dom->createElement('pay:SOURCE','UMA');
      $payHEADER->appendChild($paySOURCE);
      $payMSGID=$dom->createElement('pay:MSGID','?');
      $payHEADER->appendChild($payMSGID);

      $payUSERID=$dom->createElement('pay:USERID','UMA001');
      $payHEADER->appendChild($payUSERID);

      $payBRANCH=$dom->createElement('pay:BRANCH','000');
      $payHEADER->appendChild($payBRANCH);


      $payPASSWORD=$dom->createElement('pay:PASSWORD','?');
      $payHEADER->appendChild($payPASSWORD);

      $payINVOKETIMESTAMP=$dom->createElement('pay:INVOKETIMESTAMP',$timestamp);
      $payHEADER->appendChild($payINVOKETIMESTAMP);

      //Create pay:BODY
      $payBODY=$dom->createElement('pay:BODY');
      $paiment_request->appendChild($payBODY);
      //creat pay:Payment
      $payPayment=$dom->createElement('pay:Payment');
      $payBODY->appendChild($payPayment);
      //Create pay:Paiment element
      $payAUTHTOKEN=$dom->createElement('pay:AUTHTOKEN',$token);
      $payPayment->appendChild($payAUTHTOKEN);
      $payENTITYID=$dom->createElement('pay:ENTITYID','00416');
      $payPayment->appendChild($payENTITYID);
      //creat pay:PaymentIdList
      $payPaymentIdList=$dom->createElement('pay:PaymentIdList');
      //loop to add payment_id
     foreach($pagamentos_request as $key => $pagamento) {
       $payPaymentIdList->appendChild($dom->createElement('pay:PAYMENT_ID',$pagamento->PAYMENT_ID));
      }

      $payPayment->appendChild($payPaymentIdList);

        //creat pay:PaymentIdList
        $sourcetIdList=$dom->createElement('pay:SourcetIdList');

      //loop to add source_id
   foreach($pagamentos_request as $key => $pagamento) {
      $sourcetIdList->appendChild($dom->createElement('pay:SOURCE_ID',$pagamento->SOURCE_ID));
   }

   $payPayment->appendChild($sourcetIdList);
$envelop->appendChild($soapenvHeader);
$envelop->appendChild($soapenvBody);
$dom->appendChild($envelop);

//Gerar o xml de request
$xml=$dom->saveXML();

$endpoint = "https://spf-webservices-uat.bancoeconomico.ao:7443/soa-infra/services/SPF/WSI_PaymentRefDetailsQuery/WSI_PaymentRefDetailsQuery";
   $soap_request = $xml;

   $header = array(
       "Content-type: text/xml;charset=\"utf-8\"",
       "Accept: text/xml",
       "Cache-Control: no-cache",
       "Pragma: no-cache",
      // "SOAPAction: \"http://tracmedia.org/InTheLife\"",
       "Content-length: ".strlen($soap_request),
   );

   $soap_do = curl_init();

   curl_setopt($soap_do, CURLOPT_SSL_VERIFYPEER, 0);
   curl_setopt($soap_do, CURLOPT_URL,  $endpoint);
   curl_setopt($soap_do, CURLOPT_RETURNTRANSFER, true );
   curl_setopt($soap_do, CURLOPT_TIMEOUT, 100);
   curl_setopt($soap_do, CURLOPT_POST,           true );
   curl_setopt($soap_do, CURLOPT_POSTFIELDS,     $soap_request);
   curl_setopt($soap_do, CURLOPT_HTTPHEADER,     $header);


   $result = curl_exec($soap_do);


   if($result === false) {
       $err = 'Curl error: ' . curl_error($soap_do);
       curl_close($soap_do);
       print $err;

   }else{

//Tratar os dados
$xml_response= $result;


$xml_response = preg_replace("/(<\/?)(\w+):([^>]*>)/", '$1$2$3', $xml_response);
$xml_response = simplexml_load_string($xml_response);
$json = json_encode($xml_response);
$responseArray = collect(json_decode($json,true));



try {
   //code...
$data['header_response']=$responseArray['envBody']['PaymentRefDetailsQueryResponse']['ns0HEADER'];
//Se é mais de um pagamento

if($pagamentos_request->count()>1){

   $detalhes_dos_pagamentos=$responseArray['envBody']['PaymentRefDetailsQueryResponse']['ns0BODY']['ns0Payment_List']['ns0Payment_Details'];
}else{

   $detalhes_dos_pagamentos=$responseArray['envBody']['PaymentRefDetailsQueryResponse']['ns0BODY']['ns0Payment_List'];
}

foreach ($detalhes_dos_pagamentos as $key => $pagamento) {
   # code...
   $pagamentos->push(array(
      'PAYMENT_ID'=>$pagamento["ns0PAYMENT_ID"],
      'SOURCE_ID'=>$pagamento["ns0SOURCE_ID"],
      'ENTITY_ID'=>$pagamento["ns0ENTITY_ID"],
      'REFERENCE'=>$pagamento["ns0REFERENCE"],
      'AMOUNT'=>$pagamento["ns0AMOUNT"],
      'START_DATE'=>$pagamento["ns0START_DATE"],
      'END_DATE'=>$pagamento["ns0END_DATE"],
      'Status'=>$pagamento["ns0Status"],
      ));
}
} catch (\Exception $ex) {
   //throw $th;
   Log::error($ex->getMessage());
    dd($ex->getMessage());

}





}

return $pagamentos;
}




//Cancelar refencias antes da data de expiração
public static  function cancelarReferencia($pagamentos_request)
{

//Peparando paramentros do Head e Body
$uuid=\Str::uuid();
//Timestamp no formato yyyy-MM-dd'T'HH:mm:ss
$timestamp= str_replace(' ','T',Carbon::now()->format("Y-m-d H:i:s"));  //yyyy-MM-dd'T'HH:mm:ss
//Data de criação
$created_at=date('Y-m-d');
$token=env('BE_TOKEN','');

$pagamentos=collect([]);//recebe a lista de pagamentos consultados

$dom = new DOMDocument();


		$dom->encoding = 'utf-8';

		$dom->xmlVersion = '1.0';

		$dom->formatOutput = true;

      //create envelop
		$envelop = $dom->createElement('soapenv:Envelope');

		$envelop->setAttribute('xmlns:soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
		$envelop->setAttribute('xmlns:pay', 'http://www.bancoeconomico.ao/xsd/paymentrefcancel');
      //Criar soapenvHeader
      $soapenvHeader = $dom->createElement('soapenv:Header');
     //Security node
      $security=$dom->createElement('wsse:Security');
      $security->setAttribute('xmlns:wsse', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd');
      $security->setAttribute('xmlns:wsu', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd');


      $soapenvHeader->appendChild($security);
      //creat wsse:UsernameToken
      $usernameToken=$dom->createElement('wsse:UsernameToken');
      $security->setAttribute('wsu:Id','soaAuth');
      $security->appendChild($usernameToken);

      //creat wsse:Username
      $wsseUsername=$dom->createElement('wsse:Username','UMA');
      $usernameToken->appendChild($wsseUsername);
      //crete password
      $wssePassword=$dom->createElement('wsse:Password','passwordUMA1');
      $wssePassword->setAttribute('Type','http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText');
      $usernameToken->appendChild($wssePassword);

      //Create wsu:Created
      $wsuCreated=$dom->createElement('wsu:Created',$created_at);
      $usernameToken->appendChild($wsuCreated);

      //Create soapenv:Body
      $soapenvBody = $dom->createElement('soapenv:Body');
      $envelop->appendChild($soapenvBody);
      //Create pay:PaymentRefDetailsQueryRequest
      $paiment_request=$dom->createElement('pay:PaymentRefCancelRequest');
      $soapenvBody->appendChild($paiment_request);
      //create pay:HEADER
      $payHEADER=$dom->createElement('pay:HEADER');
      $paiment_request->appendChild($payHEADER);
      //create $payHEADER elements
      $paySOURCE=$dom->createElement('pay:SOURCE','UMA');
      $payHEADER->appendChild($paySOURCE);
      $payMSGID=$dom->createElement('pay:MSGID','?');
      $payHEADER->appendChild($payMSGID);

      $payUSERID=$dom->createElement('pay:USERID','UMA001');
      $payHEADER->appendChild($payUSERID);

      $payBRANCH=$dom->createElement('pay:BRANCH','000');
      $payHEADER->appendChild($payBRANCH);


      $payPASSWORD=$dom->createElement('pay:PASSWORD','?');
      $payHEADER->appendChild($payPASSWORD);

      $payINVOKETIMESTAMP=$dom->createElement('pay:INVOKETIMESTAMP',$timestamp);
      $payHEADER->appendChild($payINVOKETIMESTAMP);

      //Create pay:BODY
      $payBODY=$dom->createElement('pay:BODY');
      $paiment_request->appendChild($payBODY);
      //creat pay:Payment
      $payPayment=$dom->createElement('pay:Payment');
      $payBODY->appendChild($payPayment);
      //Create pay:Paiment element
      $payAUTHTOKEN=$dom->createElement('pay:AUTHTOKEN',$token);
      $payPayment->appendChild($payAUTHTOKEN);
      $payENTITYID=$dom->createElement('pay:ENTITYID','00416');
      $payPayment->appendChild($payENTITYID);
      //creat pay:PaymentIdList
      $payPaymentIdList=$dom->createElement('pay:PaymentIdList');
      //loop to add payment_id
     foreach($pagamentos_request as $key => $pagamento) {
       $payPaymentIdList->appendChild($dom->createElement('pay:PAYMENT_ID',$pagamento->PAYMENT_ID));
      }

      $payPayment->appendChild($payPaymentIdList);

        //creat pay:PaymentIdList
        $sourcetIdList=$dom->createElement('pay:SourcetIdList');

      //loop to add source_id
   foreach($pagamentos_request as $key => $pagamento) {
      $sourcetIdList->appendChild($dom->createElement('pay:SOURCE_ID',$pagamento->SOURCE_ID));
   }

   $payPayment->appendChild($sourcetIdList);
$envelop->appendChild($soapenvHeader);
$envelop->appendChild($soapenvBody);
$dom->appendChild($envelop);

//Gerar o xml de request
$xml=$dom->saveXML();

$endpoint = "https://spf-webservices-uat.bancoeconomico.ao:7443/soa-infra/services/SPF/WSI_PaymentRefCancel/WSI_PaymentRefCancel";
   $soap_request = $xml;

   $header = array(
       "Content-type: text/xml;charset=\"utf-8\"",
       "Accept: text/xml",
       "Cache-Control: no-cache",
       "Pragma: no-cache",
      // "SOAPAction: \"http://tracmedia.org/InTheLife\"",
       "Content-length: ".strlen($soap_request),
   );

   $soap_do = curl_init();

   curl_setopt($soap_do, CURLOPT_SSL_VERIFYPEER, 0);
   curl_setopt($soap_do, CURLOPT_URL,  $endpoint);
   curl_setopt($soap_do, CURLOPT_RETURNTRANSFER, true );
   curl_setopt($soap_do, CURLOPT_TIMEOUT, 100);
   curl_setopt($soap_do, CURLOPT_POST,           true );
   curl_setopt($soap_do, CURLOPT_POSTFIELDS,     $soap_request);
   curl_setopt($soap_do, CURLOPT_HTTPHEADER,     $header);


   $result = curl_exec($soap_do);


   if($result === false) {
       $err = 'Curl error: ' . curl_error($soap_do);
       curl_close($soap_do);
       print $err;

   }else{

//Tratar os dados
$xml_response= $result;


$xml_response = preg_replace("/(<\/?)(\w+):([^>]*>)/", '$1$2$3', $xml_response);
$xml_response = simplexml_load_string($xml_response);
$json = json_encode($xml_response);
$responseArray = collect(json_decode($json,true));

//dd($responseArray);
/*
try {
   //code...
$data['header_response']=$responseArray['envBody']['PaymentRefDetailsQueryResponse']['ns0HEADER'];
//Se é mais de um pagamento

if($pagamentos_request->count()>1){

   $detalhes_dos_pagamentos=$responseArray['envBody']['PaymentRefDetailsQueryResponse']['ns0BODY']['ns0Payment_List']['ns0Payment_Details'];
}else{

   $detalhes_dos_pagamentos=$responseArray['envBody']['PaymentRefDetailsQueryResponse']['ns0BODY']['ns0Payment_List'];
}

foreach ($detalhes_dos_pagamentos as $key => $pagamento) {
   # code...
   $pagamentos->push(array(
      'PAYMENT_ID'=>$pagamento["ns0PAYMENT_ID"],
      'SOURCE_ID'=>$pagamento["ns0SOURCE_ID"],
      'ENTITY_ID'=>$pagamento["ns0ENTITY_ID"],
      'REFERENCE'=>$pagamento["ns0REFERENCE"],
      'AMOUNT'=>$pagamento["ns0AMOUNT"],
      'START_DATE'=>$pagamento["ns0START_DATE"],
      'END_DATE'=>$pagamento["ns0END_DATE"],
      'Status'=>$pagamento["ns0Status"],
      ));
}
} catch (\Exception $ex) {
   //throw $th;
   Log::error($ex->getMessage());
    dd($ex->getMessage());

}

 */



}

return   true;// $pagamentos;
}









protected static function toArray(){

   // SimpleXML seems to have problems with the colon ":" in the <xxx:yyy> response tags, so take them out
$xml_response= Self::$response;


$xml_response = preg_replace("/(<\/?)(\w+):([^>]*>)/", '$1$2$3', $xml_response);
$xml_response = simplexml_load_string($xml_response);
$json = json_encode($xml_response);
$responseArray = collect(json_decode($json,true));



$detalhes_do_pagamento=$responseArray['envBody']['PaymentRefCreateResponse']['ns0BODY']['ns0Payment_Details'];



$data=array(
'PAYMENT_ID'=>$detalhes_do_pagamento["ns0PAYMENT_ID"],
'SOURCE_ID'=>$detalhes_do_pagamento["ns0SOURCE_ID"],
'ENTITY_ID'=>$detalhes_do_pagamento["ns0ENTITY_ID"],
'REFERENCE'=>$detalhes_do_pagamento["ns0REFERENCE"],
'AMOUNT'=>$detalhes_do_pagamento["ns0AMOUNT"],
'START_DATE'=>$detalhes_do_pagamento["ns0START_DATE"],
'END_DATE'=>$detalhes_do_pagamento["ns0END_DATE"],
'Status'=>$detalhes_do_pagamento["ns0Status"],
);
/*
"ns0PAYMENT_ID" => "204459"
"ns0SOURCE_ID" => "48981032022"
"ns0ENTITY_ID" => "00416"
"ns0REFERENCE" => "010001012"
"ns0AMOUNT" => "100"
"ns0START_DATE" => "2022-03-31+01:00"
"ns0END_DATE" => "2022-04-01+01:00"
"ns0Status" => "ACTIVE"
*/

return $data;//$responseArray['envBody']['PaymentRefCreateResponse']['ns0BODY']['ns0Payment_Details']['ns0REFERENCE'];//pegar referencia.


}




}
