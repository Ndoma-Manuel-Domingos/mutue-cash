<?php

namespace App\Http\Controllers;

use App\Candidato;
use App\Http\Controllers\ClassesAuxiliares\anoAtual;
use Illuminate\Http\Request;
use DB;
use App\Http\Controllers\RotaController;
use App\Repositories\AlunoRepository;
use App\Services\DividaService;
use App\Services\AnoLectivoService;
use App\Services\prazoExpiracaoService;
use phpDocumentor\Reflection\Types\Null_;

class ParametroUmaController extends Controller
{

   public $alunoRepository;
   public $dividaService;
   public $anoLectivoService;
   public $anoLectivoCorrente;

   public $prazoExpiracaoService;




    public function __construct()
    {
       $this->alunoRepository = new AlunoRepository();
       $this->dividaService = new DividaService();
       $this->anoLectivoService = new AnoLectivoService();
       $this->prazoExpiracaoService = new prazoExpiracaoService();
       $this->anoLectivoCorrente = new anoAtual();

       $this->middleware('auth');

       /*  $urlRota = new RotaController();

        $urlRota->rotasWeb(); */
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $parametros = DB::table('tb_parametro_uma')->paginate(8);
        if($parametros->count() > 0)
            return Response()->json($parametros);
        return Response()->json(['msg'=>'Não existe nenhum registo'],404);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {


        $regras = ['designacao'=>'required|min:3|max:50',
               'descricao'=>'required|min:5|max:65535'];
        $msg = ['required'=>'O campo :attribute não pode estar vazio, preencha...',
                'designacao.min'=>'É necessário no minimo 3 caracteres na designacao',
                'designacao.max'=>'É necessário no maximo 50 caracteres na designacao',
                'descricao.min'=>'É necessário no minimo 5 caracteres na decricao',
                'descricao.max'=>'É necessário no maximo 65535 caracteres na decricao'];
        $request->validate($regras,$msg);

        DB::beginTransaction();
         //dd($request->validate($regras,$msg));
         try {
                $status = 0;
                //dd($request->get('estado'));
                if($request->get('estado')=='true'){
                   $status = 1;
                }
                $parametros = ['designacao'=>$request->get('designacao'),
                'descricao'=>$request->get('descricao'),
                'status'=> $status];
                DB::table('tb_parametro_uma')->insert($parametros);
                DB::commit();
                return Response()->json(['msg'=>'Registo efectuado com sucesso'],200);
         } catch (\Throwable $th) {
             DB::rollback();
             return Response()->json(['msg'=>'Erro ao registar'],500);
            throw $th;

         }


    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }


    public function getUltimaPrestacaoPorAnoLectivo($codigo_anoLectivo)
    {
        $isencaoMes_tempIds= $this->getIsencaoMes_tempIds($codigo_anoLectivo);

        if(auth()->user()->preinscricao->codigo_tipo_candidatura==1){
            $mes_tem=DB::table('mes_temp')->where('ano_lectivo',$codigo_anoLectivo)->where('activo',1)->whereNotIn('id', $isencaoMes_tempIds)->orderBy('prestacao','desc')->first();
        }else{
            $mes_tem=DB::table('mes_temp')->where('ano_lectivo',$codigo_anoLectivo)->where('activo_posgraduacao',1)->whereNotIn('id', $isencaoMes_tempIds)->orderBy('prestacao','desc')->first();
        }

        return response()->json($mes_tem);
    }

    public function getPrimeiraPrestacaoPorAnoLectivo($codigo_anoLectivo)
    {
        $isencaoMes_tempIds= $this->getIsencaoMes_tempIds($codigo_anoLectivo);

        if(auth()->user()->preinscricao->codigo_tipo_candidatura==1){
            $mes_tem=DB::table('mes_temp')->where('ano_lectivo',$codigo_anoLectivo)->where('activo',1)->whereNotIn('id', $isencaoMes_tempIds)->orderBy('prestacao','asc')->first();
        }else{
            $mes_tem=DB::table('mes_temp')->where('ano_lectivo',$codigo_anoLectivo)->where('activo_posgraduacao',1)->whereNotIn('id', $isencaoMes_tempIds)->orderBy('prestacao','asc')->first();
        }

        $data['primeira_prestacao']=$mes_tem;

        if($codigo_anoLectivo==$this->anoLectivoCorrente->index()){
            try {
              //code...
                $data['prazo_desconto_ano_todo']=$this->prazoExpiracaoService->prazoPagamentoAnoTodoComDesconto($codigo_anoLectivo,1);// prazo para ter o desconto de 5% pelo pagamento do ano todo. Neste caso dentro do mes da primeira prestacao
            } catch (\Throwable $th) {
                $data['prazo_desconto_ano_todo']=null;
            }
        }

        //primeira prestacao do ano lectivo, sem isencao


      return response()->json($data);
    }

    public function getPrestacoesPorAnoLectivo($codigo_anoLectivo)
    {
        $isencaoMes_tempIds = $this->getIsencaoMes_tempIds($codigo_anoLectivo);
        $isencaoMesIds = $this->getIsencaoMesIds($codigo_anoLectivo);
        $verificaPagamentoMarco= $this->alunoRepository->verificaPagamentoMarco($codigo_anoLectivo);
        $anosLectivo = $this->anoLectivoService->AnosLectivo($codigo_anoLectivo);
        $todos_meses_pagos = null;

        if((int)$anosLectivo->Designacao<=2019 && $anosLectivo->Designacao != $this->anoLectivoCorrente->cicloMestrado()->Designacao && $anosLectivo->Designacao != $this->anoLectivoCorrente->cicloDoutoramento()->Designacao){
            $todos_meses_pagos = DB::table('tb_pagamentosi')->join('tb_pagamentos', 'tb_pagamentos.Codigo', 'tb_pagamentosi.Codigo_Pagamento')->where('tb_pagamentos.Codigo_PreInscricao', $this->alunoRepository->dadosAlunoLogado()->codigo_inscricao)->where('tb_pagamentosi.Ano', $anosLectivo->Designacao)->where('tb_pagamentosi.mes_id', '!=', null)->pluck('mes_id');

        }else{

            $todos_meses_pagos = DB::table('factura_items')
                ->join('factura', 'factura.Codigo', 'factura_items.CodigoFactura')
                ->where('factura_items.mes_temp_id', '!=', null)
                ->where('factura.ano_lectivo', $codigo_anoLectivo)
                ->where('factura.CodigoMatricula', $this->alunoRepository->dadosAlunoLogado()->matricula)
                ->where('factura.corrente', 1)
                ->where('factura.codigo_descricao', '!=', 5)
                ->where('factura.estado', '!=', 3)
                ->pluck('mes_temp_id');
        }

        if(auth()->user()->preinscricao->codigo_tipo_candidatura==1){
            if($verificaPagamentoMarco){
                $mes_temp_marco=DB::table('mes_temp')->where('ano_lectivo',$codigo_anoLectivo)->where('activo',1)->whereNotIn('id', $isencaoMes_tempIds)->select('id as mes_temp_id')->orderBy('id','asc')->get();
                $array_meses_id = json_decode($mes_temp_marco->pluck('mes_temp_id'), true);
                array_push($array_meses_id, 1);
                $mes_tem = DB::table('mes_temp')->where('ano_lectivo',$codigo_anoLectivo)->whereIn('activo',[0,1])->whereIn('id', $array_meses_id)->orderBy('id','asc')->limit(10)->get();
            }else{
                $mes_tem = DB::table('mes_temp')->where('ano_lectivo',$codigo_anoLectivo)->where('activo',1)->whereNotIn('id', $isencaoMes_tempIds)->orderBy('id','asc')->get();

                if((int)$anosLectivo->Designacao<=2019 && $anosLectivo->Designacao != $this->anoLectivoCorrente->cicloMestrado()->Designacao && $anosLectivo->Designacao != $this->anoLectivoCorrente->cicloDoutoramento()->Designacao){
                    $mes_tem = DB::table('meses')->whereNotIn('codigo', $isencaoMesIds)->orderBy('codigo','asc')->pluck('mes');
                }
            }
        }else{
            $mes_tem = DB::table('mes_temp')->where('ano_lectivo',$codigo_anoLectivo)->where('activo_posgraduacao',1)->whereNotIn('id', $isencaoMes_tempIds)->orderBy('id','asc')->get();
        }

        $data['mes_temp'] = $mes_tem;

        $data['prestacoes_por_ano'] = count($this->totalPrestacoesPagarPorAno($codigo_anoLectivo));

        $data['todos_meses_pagos'] = count($todos_meses_pagos);

        return response()->json($data);
    }

    public function totalPrestacoesPagarPorAno($codigo_anoLectivo, $codigo_tipo_candidatura)
    {
        $anosLectivo = $this->anoLectivoService->AnosLectivo($codigo_anoLectivo);
        if($codigo_tipo_candidatura==1){
            $mes_tem = DB::table('mes_temp')->where('ano_lectivo',$codigo_anoLectivo)->where('activo',1)->orderBy('id','asc')->get();

            if((int)$anosLectivo->Designacao<=2019 && $anosLectivo->Designacao != $this->anoLectivoCorrente->cicloMestrado()->Designacao && $anosLectivo->Designacao != $this->anoLectivoCorrente->cicloDoutoramento()->Designacao){
                $mes_tem = DB::table('meses')->orderBy('codigo','asc')->pluck('mes');
            }
        }else{
            $mes_tem = DB::table('mes_temp')->where('ano_lectivo',$codigo_anoLectivo)->where('activo_posgraduacao',1)->orderBy('id','asc')->get();
        }

        return $mes_tem;
    }

    public function getIsencaoMes_tempIds($codigo_anoLectivo)
    {

        $aluno=$this->alunoRepository->dadosAlunoLogado();

        $isencao=DB::table('tb_isencoes')
          ->where('mes_temp_id','!=',null)
          ->where('codigo_anoLectivo', $codigo_anoLectivo)
          ->where('codigo_matricula',$aluno->matricula)
          ->where('estado_isensao', 'Activo')
          ->where('Codigo_motivo', '!=', 42)
          ->select('mes_temp_id as mes_temp_id')->get();

          $isencaoMes_tempIds=$isencao->pluck('mes_temp_id');

          return $isencaoMes_tempIds;
    }

    public function getIsencaoMesIds($codigo_anoLectivo)
    {

        $aluno=$this->alunoRepository->dadosAlunoLogado();

        $isencao=DB::table('tb_isencoes')
          ->where('mes_id','!=',null)
          ->where('codigo_anoLectivo', $codigo_anoLectivo)
          ->where('codigo_matricula', $aluno->matricula)
          ->where('estado_isensao', 'Activo')
          ->select('mes_id as mes_id')->get();

          $isencaoMesIds = $isencao->pluck('mes_id');

          return $isencaoMesIds;
    }



    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
    public function procurarParametro(Request $request)
    {
        $estado='';
        $campo = 'designacao';
        $busca = '';
        //dd(strcasecmp($request->get('palavra_chave'),'Activado')==0);
        if(strcasecmp($request->get('palavra_chave'),'Activado')==1 || strcasecmp($request->get('palavra_chave'),'Activado')==1){
            $campo = 'designacao';
            $busca = $request->get('palavra_chave');
        }else{
            $busca = $request->get('palavra_chave');
            if(strcasecmp($request->get('palavra_chave'),'Activado')==0)
           {
               $estado = 1;
               $campo = 'status';
               $busca =  $estado;
           }
        if(strcasecmp($request->get('palavra_chave'),'Desactivado')==0)
          {
               $estado = 0;
               $campo = 'status';
               $busca =  $estado;
        }
    }
        //dd(''.$campo.'',$busca);
        $parametros = DB::table('tb_parametro_uma')
                      ->where(''.$campo.'','LIKE','%'.$busca.'%')
                     // ->orWhere('designacao','LIKE','%'.$request->get('palavra_chave').'%')
                      //->orWhere('designacao','LIKE','%'.$request->get('palavra_chave').'%')
                     ->paginate(8);
        if($parametros->count() > 0)
            return Response()->json($parametros);
        return Response()->json(['msg'=>'Não existe nenhum registo'],404);
    }



    public function getEstudantes(Request $request){

        $query = json_decode($request->get('query'), true);
        //As datas ao serem selecionadas, nao deve somente ser selecionado uma,
        //ou seja se selecionar a data inicial também é obrigatorio selecionar a data final e vice-versa

        if ($query['dateInit'] != '' && $query['dateFinal'] == '') {
            return Response()->json(['msg' => 'Selecione a data Final'], 404);
        } elseif ($query['dateFinal'] != '' && $query['dateInit'] == '') {
            return Response()->json(['msg' => 'Selecione a data Inicial'], 404);
        }elseif ($query['dateFinal'] < $query['dateInit']) {
            return Response()->json(['msg' => 'Selecione uma data válida'], 404);
        }

        $condicoes = [];

        foreach ($query as $key => $value) {
            if (strcasecmp($key, "curso") == 0) {
                if ($value != 0) {
                    array_push($condicoes, ['tb_cursos.Codigo', '=', $value]);
                }

            }elseif (strcasecmp($key, "codigo_tipo_candidatura") == 0) {
                if ($value != 0) {
                    array_push($condicoes, ['tb_preinscricao.codigo_tipo_candidatura', '=', $value]);
                }

            }elseif (strcasecmp($key, "periodo") == 0) {
                if ($value != 0) {
                    array_push($condicoes, ['tb_periodos.Codigo', '=', $value]);
                }

            } elseif (strcasecmp($key, "dateInit") == 0) {
                if ($value != 0) {
                    array_push($condicoes, ['tb_matriculas.Data_Matricula', '>=', $value]);
                }

            } elseif (strcasecmp($key, "dateFinal") == 0) {
                if ($value != 0) {
                    array_push($condicoes, ['tb_matriculas.Data_Matricula', '<=', $value]);
                }

            }elseif (strcasecmp($key, "ano_letivo") == 0) {
                if ($value != 0) {
                    array_push($condicoes, ['tb_ano_lectivo.Codigo', '=', $value]);
                }

            } elseif (strcasecmp($key, "palavra_chave") == 0) {
                $palavra_chave = $value;
                if (strlen($palavra_chave) > 0) {
                    if (is_numeric($palavra_chave)) {
                        array_push($condicoes, ['tb_matriculas.Codigo', 'LIKE', '%' . $palavra_chave . '%']);
                    } else {
                        array_push($condicoes, ['tb_preinscricao.Nome_Completo', 'LIKE', '%' . $palavra_chave . '%']);
                    }
                }
            }
        }

        $data['tipos_candidaturas'] = DB::table('tb_tipo_candidatura')->get();

        if (($query['codigo_tipo_candidatura']!='')) {
            $data['cursos_candidatura'] = DB::table('tb_cursos')->where('tipo_candidatura', $query['codigo_tipo_candidatura'])->orderBy('Designacao')->get();
        }

        $data['matriculas'] = DB::table('tb_preinscricao')
        ->join('tb_cursos', 'tb_cursos.codigo', '=', 'tb_preinscricao.Curso_Candidatura')
        ->join('tb_periodos', 'tb_periodos.codigo', '=', 'tb_preinscricao.Codigo_Turno')
        ->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', '=', 'tb_preinscricao.anoLectivo')
        ->join('tb_admissao', 'tb_admissao.pre_incricao', '=', 'tb_preinscricao.Codigo')
        ->join('tb_matriculas', 'tb_matriculas.Codigo_Aluno', '=', 'tb_admissao.codigo')
        ->join('users', 'users.id', '=', 'tb_preinscricao.user_id')
        ->join('tb_tipo_candidatura', 'tb_tipo_candidatura.id', '=', 'tb_preinscricao.codigo_tipo_candidatura')
        ->select('tb_preinscricao.*', 'tb_admissao.*', 'tb_preinscricao.Codigo as codigo_preinscricao', 'users.id as id_user', 'tb_matriculas.Codigo As Codigo_Aluno', 'tb_matriculas.estado_matricula As estado_matricula', 'tb_matriculas.Data_Matricula As Data_Matricula', 'tb_cursos.Designacao As curso', 'tb_periodos.Designacao As periodo', 'tb_ano_lectivo.Designacao As Ano_Lectivo', 'tb_tipo_candidatura.designacao as tipo_candidatura')
        ->where($condicoes)
        ->orderBy('tb_preinscricao.Nome_Completo', 'ASC')
        ->distinct('tb_matriculas.Codigo')->paginate();

        return Response()->json($data, 200);
    }

    public function actualizarIsencaoMultaParaTodos(Request $request)
    {

        $mensagens = [
            'dateInit.required' => 'É obrigatório o preenchimento da primeira data',
            'dateFinal.required' => 'É obrigatório o preenchimento da segunda data.',
            'dateInit.before_or_equal' => 'A data não pode ser maior que a data actual/final.',
            'dateFinal.before_or_equal' => 'A data não pode ser maior que a data actual.',
            'dateFinal.after_or_equal' => 'A data final não pode ser menor do que a data inicial.',
            'ano_letivo.required' => 'É obrigatório o preenchimento do ano acadêmico.',
            'isencao_multa.required' => 'É obrigatório o preenchimento do valor da isenção da Multa.'
        ];

        $request->validate([
            'dateInit' => ['required', 'date','before_or_equal:' . date('Y-m-d'), 'before_or_equal:' . $request->get('dateFinal')],
            'dateFinal' => ['required', 'date', 'before_or_equal:' . date('Y-m-d'),'after_or_equal:' . $request->get('dateInit')],
            'ano_letivo' => ['required'],
            'isencao_multa' => ['required'],
        ], $mensagens);

        if(($request->get('codigo_tipo_candidatura')==0) && ($request->get('curso')==0)){
            $data['matriculas'] = DB::table('tb_preinscricao')
            ->join('tb_cursos', 'tb_cursos.codigo', '=', 'tb_preinscricao.Curso_Candidatura')
            ->join('tb_periodos', 'tb_periodos.codigo', '=', 'tb_preinscricao.Codigo_Turno')
            ->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', '=', 'tb_preinscricao.anoLectivo')
            ->join('tb_admissao', 'tb_admissao.pre_incricao', '=', 'tb_preinscricao.Codigo')
            ->join('tb_matriculas', 'tb_matriculas.Codigo_Aluno', '=', 'tb_admissao.codigo')
            ->join('users', 'users.id', '=', 'tb_preinscricao.user_id')
            ->join('tb_tipo_candidatura', 'tb_tipo_candidatura.id', '=', 'tb_preinscricao.codigo_tipo_candidatura')
            ->select('tb_preinscricao.*', 'tb_admissao.*', 'tb_preinscricao.Codigo as codigo_preinscricao', 'users.id as id_user', 'tb_matriculas.Codigo As Codigo_Aluno', 'tb_matriculas.estado_matricula As estado_matricula', 'tb_matriculas.Data_Matricula As Data_Matricula', 'tb_cursos.Designacao As curso', 'tb_periodos.Designacao As periodo', 'tb_ano_lectivo.Designacao As Ano_Lectivo', 'tb_tipo_candidatura.designacao as tipo_candidatura')
            ->where('tb_ano_lectivo.Codigo', $request->get('ano_letivo'))
            ->whereBetween('tb_matriculas.Data_Matricula', [$request->get('dateInit'), $request->get('dateFinal')])
            ->distinct('tb_matriculas.Codigo')->update(['isencao_multa'=>$request->get('isencao_multa')]);

            return Response()->json("Operação feita com sucesso");

        }elseif(($request->get('codigo_tipo_candidatura')!=0) && ($request->get('curso')==0)){
            $data['matriculas'] = DB::table('tb_preinscricao')
            ->join('tb_cursos', 'tb_cursos.codigo', '=', 'tb_preinscricao.Curso_Candidatura')
            ->join('tb_periodos',  'tb_periodos.codigo', '=', 'tb_preinscricao.Codigo_Turno')
            ->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', '=', 'tb_preinscricao.anoLectivo')
            ->join('tb_admissao', 'tb_admissao.pre_incricao', '=', 'tb_preinscricao.Codigo')
            ->join('tb_matriculas', 'tb_matriculas.Codigo_Aluno', '=', 'tb_admissao.codigo')
            ->join('users', 'users.id', '=', 'tb_preinscricao.user_id')
            ->join('tb_tipo_candidatura', 'tb_tipo_candidatura.id', '=', 'tb_preinscricao.codigo_tipo_candidatura')
            ->select('tb_preinscricao.*', 'tb_admissao.*', 'tb_preinscricao.Codigo as codigo_preinscricao', 'users.id as id_user', 'tb_matriculas.Codigo As Codigo_Aluno', 'tb_matriculas.estado_matricula As estado_matricula', 'tb_matriculas.Data_Matricula As Data_Matricula', 'tb_cursos.Designacao As curso', 'tb_periodos.Designacao As periodo', 'tb_ano_lectivo.Designacao As Ano_Lectivo', 'tb_tipo_candidatura.designacao as tipo_candidatura')
            ->where('tb_tipo_candidatura.id', $request->get('codigo_tipo_candidatura'))
            ->where('tb_ano_lectivo.Codigo', $request->get('ano_letivo'))
            ->whereBetween('tb_matriculas.Data_Matricula', [$request->get('dateInit'), $request->get('dateFinal')])
            ->distinct('tb_matriculas.Codigo')->update(['isencao_multa'=>$request->get('isencao_multa')]);

            return Response()->json("Operação feita com sucesso");

        }elseif(($request->get('codigo_tipo_candidatura')!=0) && ($request->get('curso')!=0)){
            $data['matriculas'] = DB::table('tb_preinscricao')
            ->join('tb_cursos', 'tb_cursos.codigo', '=', 'tb_preinscricao.Curso_Candidatura')
            ->join('tb_periodos',  'tb_periodos.codigo', '=', 'tb_preinscricao.Codigo_Turno')
            ->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', '=', 'tb_preinscricao.anoLectivo')
            ->join('tb_admissao', 'tb_admissao.pre_incricao', '=', 'tb_preinscricao.Codigo')
            ->join('tb_matriculas', 'tb_matriculas.Codigo_Aluno', '=', 'tb_admissao.codigo')
            ->join('users', 'users.id', '=', 'tb_preinscricao.user_id')
            ->join('tb_tipo_candidatura', 'tb_tipo_candidatura.id', '=', 'tb_preinscricao.codigo_tipo_candidatura')
            ->select('tb_preinscricao.*', 'tb_admissao.*', 'tb_preinscricao.Codigo as codigo_preinscricao', 'users.id as id_user', 'tb_matriculas.Codigo As Codigo_Aluno', 'tb_matriculas.estado_matricula As estado_matricula', 'tb_matriculas.Data_Matricula As Data_Matricula', 'tb_cursos.Designacao As curso', 'tb_periodos.Designacao As periodo', 'tb_ano_lectivo.Designacao As Ano_Lectivo', 'tb_tipo_candidatura.designacao as tipo_candidatura')
            ->where('tb_cursos.codigo', $request->get('curso'))
            ->where('tb_tipo_candidatura.id', $request->get('codigo_tipo_candidatura'))
            ->where('tb_ano_lectivo.Codigo', $request->get('ano_letivo'))
            ->whereBetween('tb_matriculas.Data_Matricula', [$request->get('dateInit'), $request->get('dateFinal')])
            ->distinct('tb_matriculas.Codigo')->update(['isencao_multa'=>$request->get('isencao_multa')]);

            return Response()->json("Operação feita com sucesso");
        }
    }


    public function actualizarIsencaoMultaByEstudante(Request $request, $codigo)
    {
        $candidato= Candidato::find($codigo);
        $mensagens = [
            'isencao_multa.required' => 'É obrigatório o preenchimento do valor da isenção da Multa.'
        ];

        $request->validate([
            'isencao_multa' => ['required'],
        ], $mensagens);

        $candidato->update($request->all());

        return Response()->json("Operação feita com sucesso");
    }


    public function actualizarDescontosParaTodos(Request $request)
    {

        $mensagens = [
            'dateInit.required' => 'É obrigatório o preenchimento da primeira data',
            'dateFinal.required' => 'É obrigatório o preenchimento da segunda data.',
            'dateInit.before_or_equal' => 'A data não pode ser maior que a data actual/final.',
            'dateFinal.before_or_equal' => 'A data não pode ser maior que a data actual.',
            'dateFinal.after_or_equal' => 'A data final não pode ser menor do que a data inicial.',
            'ano_letivo.required' => 'É obrigatório o preenchimento do ano acadêmico.',
            'desconto.required' => 'É obrigatório o preenchimento do valor do desconto.'
        ];

        $request->validate([
            'dateInit' => ['required', 'date','before_or_equal:' . date('Y-m-d'), 'before_or_equal:' . $request->get('dateFinal')],
            'dateFinal' => ['required', 'date', 'before_or_equal:' . date('Y-m-d'),'after_or_equal:' . $request->get('dateInit')],
            'ano_letivo' => ['required'],
            'desconto' => ['required', 'integer', 'min:1'],
        ], $mensagens);

        if(($request->get('codigo_tipo_candidatura')==0) && ($request->get('curso')==0)){
            $data['matriculas'] = DB::table('tb_preinscricao')
            ->join('tb_cursos', 'tb_cursos.codigo', '=', 'tb_preinscricao.Curso_Candidatura')
            ->join('tb_periodos', 'tb_periodos.codigo', '=', 'tb_preinscricao.Codigo_Turno')
            ->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', '=', 'tb_preinscricao.anoLectivo')
            ->join('tb_admissao', 'tb_admissao.pre_incricao', '=', 'tb_preinscricao.Codigo')
            ->join('tb_matriculas', 'tb_matriculas.Codigo_Aluno', '=', 'tb_admissao.codigo')
            ->join('users', 'users.id', '=', 'tb_preinscricao.user_id')
            ->join('tb_tipo_candidatura', 'tb_tipo_candidatura.id', '=', 'tb_preinscricao.codigo_tipo_candidatura')
            ->select('tb_preinscricao.*', 'tb_admissao.*', 'tb_preinscricao.Codigo as codigo_preinscricao', 'users.id as id_user', 'tb_matriculas.Codigo As Codigo_Aluno', 'tb_matriculas.estado_matricula As estado_matricula', 'tb_matriculas.Data_Matricula As Data_Matricula', 'tb_cursos.Designacao As curso', 'tb_periodos.Designacao As periodo', 'tb_ano_lectivo.Designacao As Ano_Lectivo', 'tb_tipo_candidatura.designacao as tipo_candidatura')
            ->where('tb_ano_lectivo.Codigo', $request->get('ano_letivo'))
            ->whereBetween('tb_matriculas.Data_Matricula', [$request->get('dateInit'), $request->get('dateFinal')])
            ->distinct('tb_matriculas.Codigo')->update(['desconto'=>$request->get('desconto')]);

            return Response()->json("Prestação atualizada com sucesso");

        }elseif(($request->get('codigo_tipo_candidatura')!=0) && ($request->get('curso')==0)){
            $data['matriculas'] = DB::table('tb_preinscricao')
            ->join('tb_cursos', 'tb_cursos.codigo', '=', 'tb_preinscricao.Curso_Candidatura')
            ->join('tb_periodos',  'tb_periodos.codigo', '=', 'tb_preinscricao.Codigo_Turno')
            ->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', '=', 'tb_preinscricao.anoLectivo')
            ->join('tb_admissao', 'tb_admissao.pre_incricao', '=', 'tb_preinscricao.Codigo')
            ->join('tb_matriculas', 'tb_matriculas.Codigo_Aluno', '=', 'tb_admissao.codigo')
            ->join('users', 'users.id', '=', 'tb_preinscricao.user_id')
            ->join('tb_tipo_candidatura', 'tb_tipo_candidatura.id', '=', 'tb_preinscricao.codigo_tipo_candidatura')
            ->select('tb_preinscricao.*', 'tb_admissao.*', 'tb_preinscricao.Codigo as codigo_preinscricao', 'users.id as id_user', 'tb_matriculas.Codigo As Codigo_Aluno', 'tb_matriculas.estado_matricula As estado_matricula', 'tb_matriculas.Data_Matricula As Data_Matricula', 'tb_cursos.Designacao As curso', 'tb_periodos.Designacao As periodo', 'tb_ano_lectivo.Designacao As Ano_Lectivo', 'tb_tipo_candidatura.designacao as tipo_candidatura')
            ->where('tb_tipo_candidatura.id', $request->get('codigo_tipo_candidatura'))
            ->where('tb_ano_lectivo.Codigo', $request->get('ano_letivo'))
            ->whereBetween('tb_matriculas.Data_Matricula', [$request->get('dateInit'), $request->get('dateFinal')])
            ->distinct('tb_matriculas.Codigo')->update(['desconto'=>$request->get('desconto')]);

            return Response()->json("Prestação atualizada com sucesso");

        }elseif(($request->get('codigo_tipo_candidatura')!=0) && ($request->get('curso')!=0)){
            $data['matriculas'] = DB::table('tb_preinscricao')
            ->join('tb_cursos', 'tb_cursos.codigo', '=', 'tb_preinscricao.Curso_Candidatura')
            ->join('tb_periodos',  'tb_periodos.codigo', '=', 'tb_preinscricao.Codigo_Turno')
            ->join('tb_ano_lectivo', 'tb_ano_lectivo.Codigo', '=', 'tb_preinscricao.anoLectivo')
            ->join('tb_admissao', 'tb_admissao.pre_incricao', '=', 'tb_preinscricao.Codigo')
            ->join('tb_matriculas', 'tb_matriculas.Codigo_Aluno', '=', 'tb_admissao.codigo')
            ->join('users', 'users.id', '=', 'tb_preinscricao.user_id')
            ->join('tb_tipo_candidatura', 'tb_tipo_candidatura.id', '=', 'tb_preinscricao.codigo_tipo_candidatura')
            ->select('tb_preinscricao.*', 'tb_admissao.*', 'tb_preinscricao.Codigo as codigo_preinscricao', 'users.id as id_user', 'tb_matriculas.Codigo As Codigo_Aluno', 'tb_matriculas.estado_matricula As estado_matricula', 'tb_matriculas.Data_Matricula As Data_Matricula', 'tb_cursos.Designacao As curso', 'tb_periodos.Designacao As periodo', 'tb_ano_lectivo.Designacao As Ano_Lectivo', 'tb_tipo_candidatura.designacao as tipo_candidatura')
            ->where('tb_cursos.codigo', $request->get('curso'))
            ->where('tb_tipo_candidatura.id', $request->get('codigo_tipo_candidatura'))
            ->where('tb_ano_lectivo.Codigo', $request->get('ano_letivo'))
            ->whereBetween('tb_matriculas.Data_Matricula', [$request->get('dateInit'), $request->get('dateFinal')])
            ->distinct('tb_matriculas.Codigo')->update(['desconto'=>$request->get('desconto')]);

            return Response()->json("Desconto atualizada com sucesso");
        }
    }


    public function actualizarDescontosByEstudante(Request $request, $codigo)
    {
        $mensagens = [
            'desconto.required' => 'É obrigatório o preenchimento do valor do desconto.'
        ];

        $request->validate([
            'desconto' => ['required'],
        ], $mensagens);

        $candidato= Candidato::find($codigo);
        $candidato->update($request->all());

        return Response()->json("Desconto atualizada com sucesso");
    }


    public function pegarDependencias(Request $request)
    {

        $query = json_decode($request->get('query'));

        if (isset($query->codigo_tipo_candidatura)) {
            $data['cursos_candidatura'] = DB::table('tb_cursos')->where('tipo_candidatura', $query->codigo_tipo_candidatura)->orderBy('Designacao')->get();
        }

        $data['periodos'] = DB::table('tb_periodos')->where('status', 1)->limit(1)->orderBy('Codigo','DESC')->get();
        $data['tipos_candidaturas'] = DB::table('tb_tipo_candidatura')->get();
        $data['anos_lectivos'] = DB::table('tb_ano_lectivo')->orderBy('Designacao', 'DESC')->get();

        return Response()->json($data);
    }
}
