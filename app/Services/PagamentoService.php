<?php

namespace App\Services;

use App\Models\Factura;
use App\FacturaItens;
use Illuminate\Support\Facades\DB;
//use App\Pagamento\HistoricoSaldo;
use App\Repositories\AlunoRepository;
// nunca usar prezoExpiracaoService nesta classe vai, dar um ciclo infinito porque ela ja chama o pagamentoService
use App\Http\Controllers\ClassesAuxiliares\anoAtual;
use App\Models\AnoLectivo;
use App\Models\GradeCurricularAluno;
use App\Models\Pagamento;
use App\PagamentoItem;
use App\Models\Preinscricao;
use App\Servico;
use App\Services\ServicosService;
use \Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Keygen\Keygen;
use App\Services\FaturaService;
use App\Services\GerarRefereciaDePagamento;
use App\PagamentoPorReferencia;
use Illuminate\Support\Facades\Http;

class PagamentoService
{
  public $alunoRepository;
  public $aluno;
  public $anoCorrente;
  public $servicosService;
  public $pagamentoService;
  public $faturaService;

  public function __construct()
  {
    $this->alunoRepository = new AlunoRepository();


    $this->anoCorrente = new anoAtual();
    $this->servicosService = new ServicosService();
    $this->faturaService = new FaturaService();
  }

  private function aluno()
  {
    return $this->alunoRepository->dadosAlunoLogado();
  }


  public  function salvarPagamMovimentoConta($codigo_pagamento, $codigo_matricula)
  {

    DB::beginTransaction();

    try {
      DB::table('historico_movimento_conta_estudante')->insert([
        'referencia' => $codigo_pagamento,
        'data_movimento' => date('Y-m-d'), 'credito' => 0, 'debito' => 0, 'estado' => 0, 'matricula' => $codigo_matricula, 'saldo_operacao' => 0, 'saldo_geral' => 0, 'codigoTipoMovimento' => 2, 'codigoMotivo' => null,
        'codigoUtilizador' => null, 'observacao' => 'pagamento enviado', 'Factura' => null
      ]);
    }
    //H:i:s
    catch (\Illuminate\Database\QueryException $e) {

      DB::rollback();
      return Response()->json('ocorreu um erro(mc)');
    }
    DB::commit();
  }
  public function getSaldoAluno()
  {

    $aluno = $this->alunoRepository->dadosAlunoLogado();
    $historico = DB::table('tb_pagamento_dependencia')
      ->join('tb_pagamentos', 'tb_pagamentos.Codigo', 'tb_pagamento_dependencia.pagamentoPrincipal')
      ->join('factura', 'tb_pagamentos.codigo_factura', 'factura.Codigo')
      ->where('factura.CodigoMatricula', $aluno->matricula)
      ->where('factura.corrente', 1)->where('factura.estado', 1)
      ->where('tb_pagamentos.corrente', 1)->where('tb_pagamentos.estado', 1)->where('tb_pagamento_dependencia.estado', 1)->select('tb_pagamento_dependencia.*', 'saldoRestante as saldo')->first();



    return $historico;
  }

  public function usarSaldoAluno($valorApagar)
  {

    $aluno = $this->alunoRepository->dadosAlunoLogado();
    $historico = DB::table('tb_pagamento_dependencia')
      ->join('tb_pagamentos', 'tb_pagamentos.Codigo', 'tb_pagamento_dependencia.pagamentoPrincipal')
      ->join('factura', 'tb_pagamentos.codigo_factura', 'factura.Codigo')
      ->where('factura.CodigoMatricula', $aluno->matricula)
      ->where('factura.corrente', 1)->where('factura.estado', 1)
      ->where('tb_pagamentos.corrente', 1)->where('tb_pagamentos.estado', 1)->where('tb_pagamento_dependencia.estado', 1)->first();



    return $historico;
  }


  public function erroAoGerarReferenciaDeletePagmento($factura)
  {
    try {
      DB::table('factura')->where('factura.Codigo', $factura)->update(['ValorEntregue' => 0]);
      DB::table('tb_pagamentos')->where('tb_pagamentos.codigo_factura', $factura)->delete();
    } catch (\Exception $e) {
      DB::rollback();
      $erro['msg'] = 'Ocorreu um erro!';
      return $erro;
    }

    return "";
  }


  public function pagamentoPorPrestacao($prestacao)
  { // no ano corrente



    $aluno = $this->alunoRepository->dadosAlunoLogado();
    $pagamento = DB::table('tb_pagamentos')
      //->join('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', 'tb_pagamentos.Codigo')
      ->join('tb_preinscricao', 'tb_pagamentos.Codigo_PreInscricao', '=', 'tb_preinscricao.Codigo')
      ->join('tb_admissao', 'tb_admissao.pre_incricao', '=', 'tb_preinscricao.Codigo')
      ->join('tb_matriculas', 'tb_matriculas.Codigo_Aluno', 'tb_admissao.codigo')
      ->join('factura', 'factura.Codigo', '=', 'tb_pagamentos.codigo_factura')
      ->join('factura_items', 'factura_items.CodigoFactura', 'factura.Codigo')
      ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', 'factura_items.CodigoProduto')
      ->join('mes_temp', 'mes_temp.id', 'factura_items.mes_temp_id')
      ->where('tb_tipo_servicos.TipoServico', 'Mensal')
      ->where('mes_temp.prestacao', $prestacao)
      ->where('tb_pagamentos.estado', 1)
      //->where('factura.estado',1)
      ->where('tb_pagamentos.corrente', 1)
      ->where('factura.corrente', 1)
      ->where('factura_items.estado', 1)
      ->where('factura.codigo_descricao', 2) // para garantir que é propina
      ->where('tb_preinscricao.user_id', $aluno->user_id)

      //->where('factura.ano_lectivo', $this->anoCorrente->index())
      ->where('tb_pagamentos.AnoLectivo', $this->anoCorrente->index())->select('factura_items.*')->first();

    //dd($aluno->user_id);

    return $pagamento;
  }


  public function carregarSaldo($request)
  {

    $data = json_decode($request->pagamento, true); // transforma em array

    $codigo_ano = $this->anoCorrente->index();
    //associar o ano lectivo activo
    $anoLectivo = DB::table('tb_ano_lectivo')
      ->where('Codigo', $codigo_ano)
      ->first();
    $aluno = $this->alunoRepository->dadosAlunoLogado();

    $taxa_servico = $this->servicosService->servicoPorSigla('Csnc', $codigo_ano);



    $keygen = Keygen::numeric(9)->generate();

    DB::beginTransaction();



    try {

      //Guardar Factura
      $factura = Factura::create([
        'DataFactura' => Carbon::now(),
        'TotalPreco' =>  $data['valor_depositado'],
        'CodigoMatricula' => $aluno->matricula,
        'polo_id' => 1,
        'Referencia' => $keygen,
        'ValorAPagar' => $data['valor_depositado'],
        'Descricao' => 'Carregamento de Saldo',
        'codigo_descricao' => 2, //Exame de acesso
        'canal' => 3, //Portal 1
        'ano_lectivo' => $anoLectivo->Codigo,
        'estado' => 0

      ]);
    } catch (\Illuminate\Database\QueryException $e) {

      DB::rollback();
      $erro['status'] = 201;
      $erro['msg'] = 'Ocorreu um erro ao efectuar o pagamento(0f1)!';
      return $erro;
      //return Response()->json($e->getMessage(),201);
    }
    try {
      $this->faturaService->salvarFacturaMovimentoConta($factura->Codigo);
    } catch (\Exception $e) {
      //DB::rollback();
      //throw $e;
    }
    try {

      $factura_itens =  FacturaItens::create([
        'CodigoProduto' => $taxa_servico->Codigo, //Exames de Accesso
        'CodigoFactura' => $factura->Codigo,
        'preco' => $data['valor_depositado'],
        'Total' => $data['valor_depositado'],
        'Quantidade' => 1,
        'estado' => 0
        //fucturamente multiplicar pela quantidade

      ]);
    } catch (\Illuminate\Database\QueryException $e) {

      DB::rollback();
      $erro['status'] = 201;
      $erro['msg'] = 'Ocorreu um erro ao efectuar o pagamento(0fi2)!';
      return $erro;

      //return Response()->json($e->getMessage(),201);
    }

    try {
      //Guardar pagamento

      $data['Data'] = date('Y-m-d');
      $data['AnoLectivo'] = $anoLectivo->Codigo;
      $data['Totalgeral'] = $data['valor_depositado'];
      $data['Codigo_PreInscricao'] = $aluno->codigo_inscricao;
      $data['DataRegisto'] = Carbon::now();
      $data['canal'] = 3;
      $data['estado'] = 0;
      $data['codigo_factura'] = $factura->Codigo;
      //guardar anexo se existe
      if ($request->hasFile('talao_banco')) {
        $fileName =  rand(0, $aluno->codigo_inscricao) . time() . '.' . $request->talao_banco->getClientOriginalExtension();
        $request->talao_banco->storeAs('documentos', $fileName);
        $data['nome_documento'] = $fileName;
      }
      $pagamento = Pagamento::create($data);
      //Gerar Referencia do BE By Ndongala Nguinamau
      /*if($data['forma_pagamento']=='POR REFERÊNCIA'){
            $pagamento_referencia= GerarRefereciaDePagamento::run($factura->Codigo,$data['valor_depositado']);
            //Recupera o pagamento recem inserido na tb_pagamentos e actualiza o numero de operacao pelo codigo da referencia(SOURCE_ID)
              $pagamento= Pagamento::find($pagamento->Codigo);
              $pagamento->update(['N_Operacao_Bancaria'=>$pagamento_referencia->SOURCE_ID]);
           }*/
    } catch (\Illuminate\Database\QueryException $e) {

      DB::rollback();
      $erro['status'] = 201;
      $erro['msg'] = 'Ocorreu um erro ao efectuar o pagamento(0p3)!';
      return $erro;

      //return Response()->json($e->getMessage(),201);
    }

    try {
      $this->salvarPagamMovimentoConta($pagamento->Codigo);
    } catch (\Exception $e) {
      DB::rollback();
      throw $e;
    }


    try {
      //Guardar pagamentoi

      $data1['Codigo_Pagamento'] = $pagamento->Codigo;
      $data1['Codigo_Servico'] = $taxa_servico->Codigo; //Exames de Acessos
      $data1['Valor_Pago'] = $data['valor_depositado']; //DB::table('');
      $data1['Valor_Total'] = $data['valor_depositado']; //DB::table('');
      $data1['Ano'] = $anoLectivo->Designacao; //DB::table('');
      $data1['Quantidade'] = 1;


      $pagamentosi = PagamentoItem::create($data1);
    } catch (\Illuminate\Database\QueryException $e) {

      DB::rollback();

      $erro['status'] = 201;
      $erro['msg'] = 'Ocorreu um erro ao efectuar o pagamento(0pi4)!';
      return $erro;
      //return Response()->json($e->getMessage(),201);
    }





    $resultado['msg'] = 'Pagamento efectuado com sucesso!';
    $resultado['codigo_factura'] = $factura->Codigo;
    $resultado['status'] = 200;


    DB::commit();
    return $resultado;
  }
  //Feito pelo Imaculado, para pegar dinamicamente codigo do serviço por sigla
  public function taxaServicoPorSigla($sigla)
  {
    $id_servico = DB::table('tb_tipo_servicos as servicos')
      ->where('servicos.sigla', $sigla)
      ->where('servicos.codigo_ano_lectivo', $this->anoCorrente->index())
      ->first()->Codigo ?? null;

    return $id_servico;
  }

  public function pagamentoInscricaoForaPrazoAndReingresso($ano)
  {

    $reingresso = DB::table('tb_preinscricao')
      ->join('tb_pagamentos', 'tb_pagamentos.Codigo_PreInscricao', '=', 'tb_preinscricao.Codigo')
      ->join('tb_admissao', 'tb_admissao.pre_incricao', '=', 'tb_preinscricao.Codigo')
      ->join('tb_matriculas', 'tb_matriculas.Codigo_Aluno', 'tb_admissao.codigo')
      ->join('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', 'tb_pagamentos.Codigo')
      ->join('factura', 'factura.Codigo', '=', 'tb_pagamentos.codigo_factura')
      ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', 'tb_pagamentosi.Codigo_Servico')
      ->where(function ($query) {
        $query->where('tb_tipo_servicos.Codigo', $this->taxaServicoPorSigla('TdR'));
      })
      ->where('tb_pagamentos.AnoLectivo', $ano)
      ->where('tb_pagamentos.estado', 1)
      ->where('tb_preinscricao.user_id', auth()->user()->id)
      ->select('*')->first();

    $inscricao_fora_prazo = DB::table('tb_preinscricao')
      ->join('tb_pagamentos', 'tb_pagamentos.Codigo_PreInscricao', '=', 'tb_preinscricao.Codigo')
      ->join('tb_admissao', 'tb_admissao.pre_incricao', '=', 'tb_preinscricao.Codigo')
      ->join('tb_matriculas', 'tb_matriculas.Codigo_Aluno', 'tb_admissao.codigo')
      ->join('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', 'tb_pagamentos.Codigo')
      ->join('factura', 'factura.Codigo', '=', 'tb_pagamentos.codigo_factura')
      ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', 'tb_pagamentosi.Codigo_Servico')
      ->where(function ($query) {
        $query->where('tb_tipo_servicos.Codigo', $this->taxaServicoPorSigla('Rfdp'));
      })
      ->where('tb_pagamentos.AnoLectivo', $ano)
      ->where('tb_pagamentos.estado', 1)
      ->where('tb_preinscricao.user_id', auth()->user()->id)
      ->select('*')->first();

    if ($reingresso /*|| $inscricao_fora_prazo*/) {
      $resultado = 1;
    } else {
      $resultado = null;
    }
    return $resultado;
  }

  public function pagamentoInscricaoForaPrazo()
  {

    $anoCorrente = $this->anoCorrente->index();

    $aluno = $this->alunoRepository->dadosAlunoLogado();

    $pagamento = DB::table('factura')
      ->join('tb_pagamentos', 'tb_pagamentos.codigo_factura', 'factura.Codigo')
      ->join('tb_pagamentosi', 'tb_pagamentosi.Codigo_Pagamento', 'tb_pagamentos.Codigo')
      ->join('tb_tipo_servicos', 'tb_tipo_servicos.Codigo', 'tb_pagamentosi.Codigo_Servico')
      ->where(function ($query) {
        $query->where('tb_tipo_servicos.sigla', 'Rfdp');
        //->orWhere('tb_tipo_servicos.Codigo', 1588);
      })
      ->where('tb_pagamentos.AnoLectivo', $anoCorrente)
      ->where('tb_pagamentos.estado', 1)
      ->where('factura.CodigoMatricula', $aluno->matricula)
      ->select('*')->first();



    $inscricao = DB::table('tb_grade_curricular_aluno')
      ->join('tb_grade_curricular', 'tb_grade_curricular.Codigo', 'tb_grade_curricular_aluno.codigo_grade_curricular')
      ->where('tb_grade_curricular_aluno.codigo_matricula', $aluno->matricula)
      ->where('tb_grade_curricular_aluno.codigo_ano_lectivo', $anoCorrente)
      ->where('tb_grade_curricular.Codigo_Semestre', 1)
      ->whereIn('Codigo_Status_Grade_Curricular', [2, 3, 1])
      ->first();


    if ($pagamento || $inscricao) {
      $resultado = 1;
    } else {
      $resultado = null;
    }

    return $resultado;
  }


  public function salvarPagamentoPreinscricao($request)
  {

    //--DADOS--
    //Recupera os dados do formulário
    // $data = json_decode($request->pagamento, true); //

    $preinscriao = Preinscricao::whereUser_id(auth()->user()->id)->first();

    $taxa_servico = Servico::whereCodigo($this->taxaServicoPorSigla("TdEdA"))->first();
    // definir taxa para pos-graduacao
    if ($preinscriao && $preinscriao->codigo_tipo_candidatura != 1) {
      $taxa_servico = Servico::whereCodigo($this->taxaServicoPorSigla("TdIMeP"))->first();
    }

    $data['forma_pagamento'] = "POR REFERÊNCIA";
    $data['valor_depositado'] = $taxa_servico->Preco;

    $mensagens = [
      'N_Operacao_Bancaria.alpha_num' => 'O número de operação bancária digitado é inválido. Por favor digite números e/ou letras sem espaços em branco.',
      'N_Operacao_Bancaria.unique' => 'O número de operação bancária digitado já existe no sistema.',
      'DataBanco.before_or_equal' => 'A data do banco não pode ser superior a data de hoje.'
    ];

    $validate = Validator::make($data, [
      'forma_pagamento' => ['required'],
      'valor_depositado' => ['numeric']
    ], $mensagens);

    // if ($data['forma_pagamento'] != "POR REFERÊNCIA") {
    //     $request->validate([
    //         'talao_banco' => 'required|file|max:6144',
    //     ]);
    // }

    //Preparar variaveis default quando o pagamento é por referencia
    if ($data['forma_pagamento'] == "POR REFERÊNCIA") {
      $data['N_Operacao_Bancaria'] = time(); //Ndongala Nguinamau
      $data['DataBanco'] = date('Y-m-d');
    }
    if ($data['forma_pagamento'] != 'POR REFERÊNCIA') {
      $validate = Validator::make($data, [
        'N_Operacao_Bancaria' => ['required', 'unique:tb_pagamentos', 'alpha_num'],
        'Observacao' => ['max:255'],
        'DataBanco' => ['required', 'before_or_equal:' . date('Y-m-d')],
        'forma_pagamento' => ['required'],
        'ContaMovimentada' => ['required'],
        'valor_depositado' => ['numeric']
      ], $mensagens);
    }


    if ($validate->fails()) {

      return response()->json(['errors' => $validate->errors()], 422);
    }

    //associar o ano lectivo activo
    $anoLectivo = DB::table('tb_ano_lectivo')
      ->where('estado', 'Activo')
      ->first();

    $keygen = Keygen::numeric(9)->generate();
    $codigo_inscricao = auth()->user()->preinscricao->Codigo;
    DB::beginTransaction();

    if ($data['valor_depositado'] < $taxa_servico->Preco) {

      return response()->json("O valor introduzido não é permitido para
            realizar a operação! O valor não pode ser inferior ao valor do serviço!", 201);
    } else {

      try {

        //Guardar Factura
        $factura = Factura::create([
          'DataFactura' => Carbon::now(),
          'TotalPreco' =>  $taxa_servico->Preco,
          'codigo_preinscricao' => $codigo_inscricao,
          'polo_id' => 1,
          'Referencia' => $keygen,
          'ValorAPagar' => $taxa_servico->Preco,
          // 'ValorEntregue' => $data['forma_pagamento'] == "POR REFERÊNCIA"?$taxa_servico->Preco:0,
          'Descricao' => 'Inscrição de Exames de Accesso',
          'codigo_descricao' => 9, //Exame de acesso
          'canal' => 3, //Portal 1
          'ano_lectivo' => $anoLectivo->Codigo,

        ]);
      } catch (\Illuminate\Database\QueryException $e) {

        DB::rollback();
        return Response()->json('Ocorreu um erro ao efectuar o pagamento(0f1)!', 201);
        //return Response()->json($e->getMessage(),201);
      }
      try {
        $this->faturaService->salvarFacturaMovimentoConta($factura->Codigo);
      } catch (\Exception $e) {
        //DB::rollback();
        //throw $e;
      }
      try {

        $factura_itens =  FacturaItens::create([
          'CodigoProduto' => $taxa_servico->Codigo, //Exames de Accesso
          'CodigoFactura' => $factura->Codigo,
          'preco' => $taxa_servico->Preco,
          // 'valor_pago' => $data['forma_pagamento'] == "POR REFERÊNCIA"?$taxa_servico->Preco:0,
          'Total' => $taxa_servico->Preco,  //fucturamente multiplicar pela quantidade
          'codigo_anoLectivo' => $anoLectivo->Codigo,

        ]);
      } catch (\Illuminate\Database\QueryException $e) {

        DB::rollback();
        return Response()->json('Ocorreu um erro ao efectuar o pagamento(0fi2)!', 201);
        //return Response()->json($e->getMessage(),201);
      }

      try {
        //Guardar pagamento

        $data['Data'] = date('Y-m-d');
        $data['AnoLectivo'] = $anoLectivo->Codigo;
        $data['Totalgeral'] = $taxa_servico->Preco;
        $data['Codigo_PreInscricao'] = $codigo_inscricao;
        $data['DataRegisto'] = Carbon::now();
        $data['canal'] = 3;
        $data['estado'] = 0;
        $data['codigo_factura'] = $factura->Codigo;
        //guardar anexo se existe
        if ($request->hasFile('anexo')) {
          $fileName =  rand(0, $codigo_inscricao) . time() . '.' . $request->anexo->getClientOriginalExtension();
          $request->anexo->storeAs('documentos', $fileName);
          $data['nome_documento'] = $fileName;
        }
        $pagamento = Pagamento::create($data);
        //Gerar Referencia do BE By Ndongala Nguinamau
        if ($data['forma_pagamento'] == 'POR REFERÊNCIA') {
          $pagamento_referencia = GerarRefereciaDePagamento::run($factura->Codigo, $data['valor_depositado']);
          //Recupera o pagamento recem inserido na tb_pagamentos e actualiza o numero de operacao pelo codigo da referencia(SOURCE_ID)
          $pagamento = Pagamento::find($pagamento->Codigo);
          $pagamento->update(['N_Operacao_Bancaria' => $pagamento_referencia->SOURCE_ID]);
        }
      } catch (\Illuminate\Database\QueryException $e) {

        DB::rollback();
        return Response()->json('Ocorreu um erro ao efectuar o pagamento(0p3)!', 201);
        //return Response()->json($e->getMessage(),201);
      }

      /* try{
                $this->pagamentoService->salvarPagamMovimentoConta($pagamento->Codigo);
               } catch (\Exception $e) {
                DB::rollback();
                throw $e;
              }*/


      try {
        //Guardar pagamentoi

        $data1['Codigo_Pagamento'] = $pagamento->Codigo;
        $data1['Codigo_Servico'] = $taxa_servico->Codigo; //Exames de Acessos
        $data1['Valor_Pago'] =  $taxa_servico->Preco; //DB::table('');
        $data1['Valor_Total'] = $taxa_servico->Preco; //DB::table('');
        $data1['Ano'] = $anoLectivo->Designacao; //DB::table('');
        $data1['Quantidade'] = 1;


        $pagamentosi = PagamentoItem::create($data1);
      } catch (\Illuminate\Database\QueryException $e) {

        DB::rollback();
        return Response()->json('Ocorreu um erro ao efectuar o pagamento(0pi4)!', 201);
        //return Response()->json($e->getMessage(),201);
      }
      try {
        $candidato = Preinscricao::whereCodigo($codigo_inscricao)->first();
        // $candidato->update(['saldo' => ($candidato->saldo - $taxa_servico->Preco)]);
      } catch (\Illuminate\Database\QueryException $e) {

        DB::rollback();
        return Response()->json('Ocorreu um erro ao efectuar o pagamento(0s5)!', 201);
        //return Response()->json($e->getMessage(), 201);
      }

      // $resultado['msg'] = 'Pagamento efectuado com sucesso!';
      $resultado = $factura;

      DB::commit();

      return $resultado;
    }
  }


  public function getPagamentosByCandidato($codigo)
  {
    //Especificamente para pagamentos antigos feitos sem referência
    // $data['pagamentos'] = Pagamento::whereHas('pagamento_itens', function ($query) {
    //   $query->where('Codigo_Servico', auth()->user()->preinscricao->codigo_tipo_candidatura == 1 ? $this->taxaServicoPorSigla("TdM") : $this->taxaServicoPorSigla("TdMPP"));
    // })->with('pagamento_itens.servico', 'banco')
    //   ->where('Codigo_PreInscricao', $codigo)->get();

    $data['facturas'] = Factura::where('CodigoMatricula', $this->alunoRepository->dadosAlunoLogado()->matricula)->where('codigo_descricao', 1)->where('ano_lectivo', $this->anoCorrente->index())->get();

    //Especificamente para pagamentos feitos sem referência
    $data['pagamentos_sem_referencia'] = Factura::doesntHave('pagamentoPorReferencias')
      ->where('codigo_descricao', 1)->where('CodigoMatricula', $this->alunoRepository->dadosAlunoLogado()->matricula)
      ->where('ano_lectivo', $this->anoCorrente->index())->get();

    //Especificamente para pagamentos recentes feitos por referência, com estado pendente ou pago
    $data['pagamentos_referencia'] = PagamentoPorReferencia::whereIn('factura_codigo', $data['facturas']->pluck('Codigo'))->with('factura')->get();

    //Especificamente para pagamentos por referência com estado pago
    $data['referencias_pagas'] = PagamentoPorReferencia::where('Status', 'PAID')->whereIn('factura_codigo', $data['facturas']->pluck('Codigo'))->get();

    //Especificamente para pagamentos por referência com estado pago
    $data['referencias_pendentes'] = PagamentoPorReferencia::where('Status', 'ACTIVE')->whereIn('factura_codigo', $data['facturas']->pluck('Codigo'))->get();

    //Especificamente para pagamentos por referência com estado pago
    $data['referencias_expirada'] = PagamentoPorReferencia::where('Status', 'EXPIRED')->whereIn('factura_codigo', $data['facturas']->pluck('Codigo'))->get();
    // dd($data);
    return response()->json($data);
  }

  public function permitirCarregarSaldo()
  {

    $parametro = DB::table('parametro_saldo_estudante')->where('estado', 1)->where('sigla', 'CSE')->first();

    return $parametro;
  }

  public function pagamentoExpiradoPorTipoFatura($tipo_factura)
  {  // pagamento por referencia expirado.

    //Especificamente para pagamentos por referência com estado expirado
    $pagamento = PagamentoPorReferencia::where('Status', 'EXPIRED')->whereHas('factura', function ($q) use ($tipo_factura) {
      $q->where('codigo_descricao', $tipo_factura)->where('CodigoMatricula', $this->aluno()->matricula);
    })->first();


    return $pagamento;
  }


  public function salvarPagamentoPorReferencia($codigo_fatura)
  { // funcao generica para salvar pagamento por referencia



    $fatura = DB::table('factura')->where('Codigo', $codigo_fatura)->first();
    $factura_items = DB::table('factura_items')->join('factura', 'factura_items.CodigoFactura', '=', 'factura.Codigo')->select('factura_items.*')->where('factura.Codigo', $fatura->Codigo)->get();
    $anoLectivo = DB::table('tb_ano_lectivo')
      ->where('Codigo', $fatura->ano_lectivo)
      ->first();
    $array_items = json_decode($factura_items, true);

    try {

      if ($this->getFormaPagamentoReferencia()->status == 1) {
        //Guardar pagamento
        $data['Data'] = date('Y-m-d');
        $data['AnoLectivo'] = $fatura->ano_lectivo;
        $data['Totalgeral'] = $fatura->ValorAPagar;
        $data['Codigo_PreInscricao'] = $this->aluno()->codigo_inscricao;
        $data['DataRegisto'] = Carbon::now();
        $data['canal'] = 3;
        $data['estado'] = 0;
        $data['codigo_factura'] = $fatura->Codigo;
        $data['valor_depositado'] = $fatura->ValorAPagar;
        $data['DataBanco'] = date('Y-m-d');
        $data['forma_pagamento'] = 'POR REFERÊNCIA';
        //$data['N_Operacao_Bancaria'] = time();

        //guardar anexo se existe
        /*    if ($request->hasFile('anexo')) {
          $fileName =  rand(0, $codigo_inscricao) . time() . '.' . $request->anexo->getClientOriginalExtension();
          $request->anexo->storeAs('documentos', $fileName);
          $data['nome_documento'] = $fileName;
        } */
        $pagamento = Pagamento::create($data);
        //Gerar Referencia do BE By Ndongala Nguinamau
        $pagamento_referencia = GerarRefereciaDePagamento::run($fatura->Codigo, $fatura->ValorAPagar);
        //Recupera o pagamento recem inserido na tb_pagamentos e actualiza o numero de operacao pelo codigo da referencia(SOURCE_ID)
        $pagamento1 = Pagamento::find($pagamento->Codigo);
        $pagamento1->update(['N_Operacao_Bancaria' => $pagamento_referencia->SOURCE_ID]);
      }
    } catch (\Illuminate\Database\QueryException $e) {

      return 'Ocorreu um erro ao efectuar o pagamento(0p3)!';
      //return Response()->json($e->getMessage(),201);
    }



    /* try{
        $this->pagamentoService->salvarPagamMovimentoConta($pagamento->Codigo);
        } catch (\Exception $e) {
        DB::rollback();
        throw $e;
      }*/


    try {
      //Guardar pagamentoi

      if ($this->getFormaPagamentoReferencia()->status == 1) {
        foreach ($array_items as $key => $fac) {

          DB::table('tb_pagamentosi')->insert(
            [
              'Codigo_Pagamento' => $pagamento->Codigo,
              'Codigo_Servico' => $fac['CodigoProduto'],
              'Valor_Pago' => $fac['Total'],
              'Quantidade' => 1,
              'Valor_Total' => $fac['Total'],
              'Ano' => $anoLectivo->Designacao,
            ]
          );
        }
      }
    } catch (\Illuminate\Database\QueryException $e) {

      return 'Ocorreu um erro ao efectuar o pagamento(0pi5)!';
      //return Response()->json($e->getMessage(),201);
    }
    /*   try {
          $candidato = Preinscricao::whereCodigo($codigo_inscricao)->first();
          $candidato->update(['saldo' => ($candidato->saldo - $taxa_servico->Preco)]);
        } catch (\Illuminate\Database\QueryException $e) {

          DB::rollback();
          return Response()->json('Ocorreu um erro ao efectuar o pagamento(0s5)!', 201);
          //return Response()->json($e->getMessage(), 201);
        }
    */

    return "";
  }

  public function getFormaPagamentoReferencia()
  {
    $forma_pagamneto = DB::table('tb_forma_pagamento')->where('Codigo', 5)->first();

    return $forma_pagamneto;
  }

  public function validarPagamentoDocumentoUCPorReferencia($codigo_factura)
  {

    $anoCorrente = $this->anoCorrente->index();

    $factura_items = FacturaItens::where('CodigoFactura', $codigo_factura)->get();
    $pagamento = Pagamento::where('codigo_factura', $codigo_factura)->first();
    $factura = Factura::where('Codigo', $codigo_factura)->first();

    $array_fatura = json_decode($factura_items, true);

    foreach ($array_fatura as $key => $item) {
      $id_documento_validacao = '';
      $servico_doc = DB::table('tb_tipo_servicos')
        ->where('codigo_ano_lectivo', $anoCorrente)
        ->where('Codigo', $item['CodigoProduto'])->select('*')->first();

      if ($servico_doc && ($servico_doc->sigla == 'CdF' || $servico_doc->sigla == 'CdHaC')) {

        try {

          if ($servico_doc->sigla == 'CdF') {
            $tipo_documento = DB::table('tb_tipo_documentos')
              ->where('Codigo', 6)->first();
          } elseif ($servico_doc->sigla == 'CdHaC') {
            $tipo_documento = DB::table('tb_tipo_documentos')
              ->where('Codigo', 7)->first();
          }

          $hashcode = strtoupper(bin2hex(random_bytes(4)));

          $documento['documento'] = $tipo_documento->Designacao;
          $documento['ano_letivo'] = $anoCorrente;
          $documento['utilizador'] = 1;
          $documento['DataRegisto'] = date('Y-m-d');
          $documento['status'] = 'Ativo';

          $documento['codigo_documento'] = $hashcode;

          $documento['codigo_matricula'] = $factura->CodigoMatricula;
          $documento['tipo_documento'] = $tipo_documento->Codigo;

          $id_documento_validacao = $this->gerarDocumentos($documento);
        } catch (\Exception $e) {
          DB::rollback();
          throw $e;
        }
      }
      break;
    }
    try {
      $validacao_documento = DB::table('tb_pagamentos')->where('Codigo', $pagamento->Codigo)->update(['info_adicional' => $id_documento_validacao]);
    } catch (\Exception $e) {
      DB::rollback();
      throw $e;
    }

    return $validacao_documento;
  }

  public function gerarDocumentos($documento)
  {
    $codigo_documento = DB::table('tb_documentos_uc')->insertGetId($documento);

    return $codigo_documento;
  }

  //Api para validação de pagamento via ADMIN JSF
  public function validarPagamentoAdmin($pagamento_id, $operador_id)
  {
    $user = auth()->user();
    if(env('APP_ENV')=='production'){
      $response = Http::get("http://mutue.co.ao/mutue/maf/validacao_pagamento?pkPagamento={$pagamento_id}&pkUtilizador={$operador_id}");
    }else{
      $response = Http::get("http://192.168.30.39:5000/mutue/maf/validacao_pagamento?pkPagamento={$pagamento_id}&pkUtilizador={$operador_id}");
    }
    
    $data = $response->json();
        
    return $data;
  }

  //Api para validação de pagamento via ADMIN JSF
  public function corrigirFalhaDeValidacaoDaAPI($pagamento_id)
  {
    $ano = AnoLectivo::where('estado', 'Activo')->where('status', 1)->first();
    $pagamento = Pagamento::findOrFail($pagamento_id);
    $avaliacoes = DB::table('inscricao_avaliacoes')->where('codigo_factura',$pagamento->codigo_factura)->get();

    try {
      if ($ano) {

        if ($pagamento) {

          $pagamento->estado = 1;
          $pagamento->forma_pagamento = 6;
          $pagamento->fk_utilizador = $pagamento->Utilizador;
          $pagamento->update();

          $preinscricao = Preinscricao::leftJoin('tb_admissao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
            ->leftJoin('tb_matriculas', 'tb_admissao.Codigo', '=', 'tb_matriculas.Codigo_Aluno')
            ->select('tb_matriculas.Codigo AS codigo_matricula', 'tb_preinscricao.Codigo AS codigo_preinscricao')
            ->findOrFail($pagamento->Codigo_PreInscricao);
          
          if ($preinscricao) {
            $grades = GradeCurricularAluno::where('codigo_matricula', $preinscricao->codigo_matricula)->where('codigo_ano_lectivo', $ano->Codigo)->get();
            if ($grades) {
              foreach ($grades as $grade) {
                $update = GradeCurricularAluno::findOrFail($grade->codigo);
                $update->Codigo_Status_Grade_Curricular = 2;
                $update->update();
              }
            }
            if(filled($avaliacoes)){
              foreach ($avaliacoes as $avaliacao) {
                $avaliacoes = DB::table('inscricao_avaliacoes')->where('codigo_factura', $avaliacao->codigo_factura)->update(['estado' => 'validado']);
              }
            }
          }
        }
      }
    } catch (\Throwable $th) {
      return Response()->json($th->getMessage());
    }

  }
}
