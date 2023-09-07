<?php

namespace App\Http\Controllers\ClassesAuxiliares;

use Illuminate\Http\Request;
use App\Categoria;
use Illuminate\Support\Facades\DB;
use App\LogAcesso;
use Illuminate\Support\Facades\Auth;
use App\AnoLectivo;

class anoAtual
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    //public $meses;
    //public $matri;



    public function index()
    {

        $ano_lectivo = DB::table('tb_ano_lectivo')
            ->where('estado', 'Activo')->select('Codigo', 'Designacao')
            ->first();
        $anoAtual = $ano_lectivo->Codigo;

        return $anoAtual;
    }

    public function anoAnterior()
    {

        $anoCorrente = $this->index();

        $ano_lectivo = DB::table('tb_ano_lectivo')
            ->where('Codigo', $anoCorrente)
            ->first();
        $ano_lectivo_anterior_ordem = $ano_lectivo->ordem - 1;
        $ano_lectivo_anterior_id = DB::table('tb_ano_lectivo')
            ->where('ordem', $ano_lectivo_anterior_ordem)
            ->first()->Codigo;

        return $ano_lectivo_anterior_id;
    }
    public function anosAnteriores()
    {

        $anoCorrente = $this->index();

        $ano_lectivo = DB::table('tb_ano_lectivo')
            ->where('Codigo', $anoCorrente)
            ->first();
        $ano_lectivo_anterior_ordem = $ano_lectivo->ordem;

        $anos_lectivos_anteriores_ids = DB::table('tb_ano_lectivo')
            ->where('ordem', '>', 0)
            ->where('ordem', '<=', $ano_lectivo_anterior_ordem)
            ->get();

        return $anos_lectivos_anteriores_ids;
    }

    public function anosLectivosComConfirmacao($matricula, $ano_lectivo){
        $ano_lectivo_sem_grade = DB::table('tb_grade_curricular_aluno')
        ->where('codigo_matricula', $matricula)
        ->where('codigo_ano_lectivo', $ano_lectivo)
        ->count();

        return $ano_lectivo_sem_grade;
    }

    public function AnoActivoDados()
    {

        $ano = AnoLectivo::whereCodigo($this->index())->first();

        return $ano;
    }
    
    //Adicionei condição do Ciclos pós-graduação
    public function cicloMestrado()
    {

       $ano_lectivo = DB::table('tb_ano_lectivo')
            ->where('Designacao', 'Ciclo Mestrado')->select('Codigo', 'Designacao')
            ->first();

        return $ano_lectivo;
    }
    //Adicionei condição do Ciclos pós-graduação
    public function cicloDoutoramento()
    {

       $ano_lectivo = DB::table('tb_ano_lectivo')
            ->where('Designacao', 'Ciclo Doutoramento')->select('Codigo', 'Designacao')
            ->first();

        return $ano_lectivo;
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
        //
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
}
