<?php

namespace App\Services;

use DB;
use Barryvdh\DomPDF\Facade as PDF;
use App\Pessoa;
use App\CandidatoDocente;

class DocenteService
{

    public function pessoaPeloBi($bi)
    {
        $pessoa = DB::table('tb_pessoa')
            ->join('tb_sexo', 'tb_sexo.Codigo', '=', 'tb_pessoa.fk_genero')
            ->where('num_doc_identificacao', $bi)->select('tb_pessoa.*', 'tb_sexo.Designacao as genero')->first();

        return $pessoa;
    }
    public function pessoaPelaCandidatura($codigo_candidatura)
    {

        //$candidatura = DB::table('mgd_tb_candidatura')->where('Codigo', $codigo_candidatura)->first();
        $pessoa = DB::table('mgd_tb_candidatura')
            ->join('tb_pessoa', 'tb_pessoa.pk_pessoa', '=', 'mgd_tb_candidatura.fk_pessoa->pk_pessoa')
            ->join('tb_sexo', 'tb_sexo.Codigo', '=', 'tb_pessoa.fk_genero')
            ->where('mgd_tb_candidatura.Codigo', $codigo_candidatura)->select('tb_pessoa.*', 'tb_sexo.Designacao as genero')->first();


        //  $candidatura->fk_pessoa->pk_pessoa;
        /*$pessoa = DB::table('tb_pessoa')
        ->join('tb_sexo', 'tb_sexo.Codigo', '=', 'tb_pessoa.fk_genero')
        ->where('tb_pessoa.pk_pessoa', $candidatura->fk_pessoa->pk_pessoa)->select('tb_pessoa.*','tb_sexo.Designacao as genero')->first();*/


        return $pessoa;
    }

    public function candidaturaPorCodigo($codigo)
    { // bi e numer
        $docente = DB::table('mgd_tb_candidatura')
            ->join('tb_pessoa', 'tb_pessoa.pk_pessoa', '=', 'mgd_tb_candidatura.fk_pessoa->pk_pessoa')
            ->where('mgd_tb_candidatura.Codigo', $codigo)
            ->select('*', 'mgd_tb_candidatura.Codigo as codigo_candidatura')->first();


        return $docente;
    }
    public function candidaturaPorBi($bi)
    { // bi e numer
        $docente = DB::table('mgd_tb_candidatura')
            ->join('tb_pessoa', 'tb_pessoa.pk_pessoa', '=', 'mgd_tb_candidatura.fk_pessoa->pk_pessoa')
            ->where('tb_pessoa.num_doc_identificacao', $bi)
            ->select('*', 'mgd_tb_candidatura.Codigo as codigo_candidatura')->first();

        return $docente;
    }
    public function candidaturaPorCodigoValidacao($codigo_validacao)
    { // bi e numer 

        $docente = DB::table('mgd_tb_candidatura')
            ->join('tb_pessoa', 'tb_pessoa.pk_pessoa', '=', 'mgd_tb_candidatura.fk_pessoa->pk_pessoa')
            ->join('tb_sexo', 'tb_sexo.Codigo', '=', 'tb_pessoa.fk_genero')
            ->join('tb_estado_civil', 'tb_estado_civil.Codigo', '=', 'tb_pessoa.fk_estado_civil')
            ->join('tb_nacionalidades', 'tb_nacionalidades.Codigo', '=', 'tb_pessoa.fk_nacionalidade')
            ->where('mgd_tb_candidatura.codigo_validacao', $codigo_validacao)
            ->select(
                '*',
                'mgd_tb_candidatura.Codigo as codigo_candidatura',
                'tb_sexo.Designacao as genero',
                'tb_sexo.Codigo as codigo_genero',
                'tb_estado_civil.Codigo as codigo_estado_civil',
                'tb_nacionalidades.Codigo as codigo_nacionalidade',
                'tb_nacionalidades.Designacao as nacionalidade'
            )->first();

        return $docente;
    }



    public function reciboInscricao($bi, $codigo_candidatura, $param)
    {
        $pessoa = $this->pessoaPelaCandidatura($codigo_candidatura);

        if ($param == 0) {

            $pessoa = $this->pessoaPelaCandidatura($codigo_candidatura);
        } elseif ($param == 1) {


            $pessoa = $this->pessoaPeloBi($bi);
        }

        
        if ($pessoa) {
           
            $candidatura = DB::table('mgd_tb_candidatura')->where('fk_pessoa->pk_pessoa', $pessoa->pk_pessoa)->first();
            

            $ultimoId = $candidatura->Codigo;


            $foto = DB::table('mgd_tb_documentos_candidatura_docente')
                ->join('tb_documentos_necessarios', 'tb_documentos_necessarios.codigo', 'mgd_tb_documentos_candidatura_docente.fk_tipo_documento')

                ->where('tb_documentos_necessarios.descricao', 'FOTOGRAFIAS')
                ->where('mgd_tb_documentos_candidatura_docente.fk_candidatura', $ultimoId)
                ->first();

            $tipo_documento_bi = DB::table('tb_documentos_necessarios')
                ->select('Codigo', 'descricao')->where('descricao', 'BI')->first();


            $tipo_documento_certificado = DB::table('tb_documentos_necessarios')
                ->select('Codigo', 'descricao')->where('descricao', "CERTIFICADO")->first();

            $tipo_documento_inaares = DB::table('tb_documentos_necessarios')
                ->select('Codigo', 'descricao')->where('descricao', "DECLARAÇÃO INAARES")->first();

            $tipo_documento_agregacao = DB::table('tb_documentos_necessarios')
                ->select('Codigo', 'descricao')->where('descricao', "DECLARAÇÃO FORMAÇÃO PEDAGÓGICA")->first();

            $tipo_documento_cv = DB::table('tb_documentos_necessarios')
                ->select('Codigo', 'descricao')->where('descricao', "CURRICULUM VITAE")->first();

            $tipo_documento_foto = DB::table('tb_documentos_necessarios')
                ->select('Codigo', 'descricao')->where('descricao', "FOTOGRAFIAS")->first();

            $tipo_documento_carta = DB::table('tb_documentos_necessarios')
                ->select('Codigo', 'descricao')->where('descricao', "CARTA DE APRESENTAÇÃO")->first();



                //dd($pessoa, $tipo_documento_carta, $tipo_documento_foto, $tipo_documento_cv, $tipo_documento_agregacao, $tipo_documento_inaares, $tipo_documento_certificado, $tipo_documento_bi, $foto, $ultimoId, $candidatura);

            $faculdades = DB::table('mgd_tb_area_candidatura_docente')
                ->join('tb_faculdade', 'mgd_tb_area_candidatura_docente.fk_faculdade', '=', 'tb_faculdade.Codigo')
                ->select('tb_faculdade.Designacao')->where('mgd_tb_area_candidatura_docente.fk_candidatura', $ultimoId)->first();


            $disciplinas = DB::table('mgd_tb_area_candidatura_docente')
                ->join('tb_disciplinas', 'mgd_tb_area_candidatura_docente.fk_disciplina', '=', 'tb_disciplinas.Codigo')
                ->select('tb_disciplinas.Designacao')->where('mgd_tb_area_candidatura_docente.fk_candidatura', $ultimoId)->get();

            $estado = DB::table('tb_estado_civil')
                ->join('tb_pessoa', 'tb_estado_civil.Codigo', '=', 'tb_pessoa.fk_estado_civil')
                ->select('Designacao')
                ->where('tb_pessoa.pk_pessoa', $pessoa->pk_pessoa)->first();
                

            $formacao = DB::table('mgd_tb_formacao_academica')
                ->select()
                ->where('fk_candidatura', $ultimoId)->get();


            $formacao_profissional = DB::table('mgd_tb_formacao_academico_profissional')
                ->select()
                ->where('fk_candidatura', $ultimoId)->get();
            //?
            $experiencia_docente = DB::table('mgd_tb_experiencia_como_docente')
                ->select()
                ->where('fk_candidatura', $ultimoId)->get();

            $outra_experiencia = DB::table('mgd_tb_outras_experiencia_profissional')
                ->select()
                ->where('fk_candidatura', $ultimoId)->get();


            $cursos = DB::table('tb_cursos')
                ->join('tb_grade_curricular', 'tb_grade_curricular.Codigo_Curso', '=', 'tb_cursos.Codigo')
                ->join('mgd_tb_area_candidatura_docente', 'tb_cursos.Codigo', '=', 'mgd_tb_area_candidatura_docente.fk_curso')
                ->select('tb_cursos.Designacao')->distinct()
                ->where('mgd_tb_area_candidatura_docente.fk_candidatura', $ultimoId)
                ->get();

            $faculdade = DB::table('tb_faculdade')
                ->join('mgd_tb_candidatura', 'tb_faculdade.Codigo', '=', 'mgd_tb_candidatura.faculdade')
                ->select('*', 'tb_faculdade.Designacao')
                ->where('mgd_tb_candidatura.Codigo', $ultimoId)->first();

            $grau = DB::table('tb_grau_academico')
                ->join('mgd_tb_formacao_academica', 'tb_grau_academico.Codigo', '=', 'mgd_tb_formacao_academica.fk_grau_academico')
                ->select('Designacao')
                ->where('mgd_tb_formacao_academica.fk_candidatura', $ultimoId)->get();

            $nacionalidade = DB::table('tb_nacionalidades')
                ->join('tb_pessoa', 'tb_nacionalidades.Codigo', '=', 'tb_pessoa.fk_nacionalidade')
                ->select('Designacao')
                ->where('tb_pessoa.pk_pessoa', $pessoa->pk_pessoa)->first();

            

            $pdf = PDF::loadView('/candidatura_docente/recibo', compact('pessoa', 'candidatura', 'foto', 'estado', 'formacao', 'grau', 'nacionalidade', 'tipo_documento_bi', 'tipo_documento_certificado', 'tipo_documento_inaares', 'tipo_documento_agregacao', 'tipo_documento_cv', 'tipo_documento_foto', 'tipo_documento_carta', 'formacao_profissional', 'experiencia_docente', 'outra_experiencia', 'cursos', 'faculdades', 'disciplinas'));            
            $data['codigo'] = $ultimoId;
            $data['pdf'] = $pdf;
            return $data;
        } else {
            return 0;
        }
        //dd($pdf);
        //return $pdf->setPaper('A4')->stream('candidatura_docente.pdf');
        //PDF::loadHTML($html)->setPaper('a4', 'landscape')->setWarnings(false)->save('myfile.pdf')

    }
    public function removeData()
    {
    }
}
