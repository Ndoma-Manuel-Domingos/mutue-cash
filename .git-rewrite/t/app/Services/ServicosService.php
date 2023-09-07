<?php

namespace App\Services;

use App\Http\Controllers\ClassesAuxiliares\anoAtual;
use App\Repositories\AlunoRepository;
use DB;

class ServicosService
{

  public $alunoRepository;
  public $anoCorrente;
  public function __construct()
  {
    $this->alunoRepository = new AlunoRepository();
    $this->anoCorrente = new anoAtual();
  }

    public function servicoPorSigla($sigla, $codigo_ano)
    {

        $servico = DB::table('tb_tipo_servicos')
            ->where('codigo_ano_lectivo', $codigo_ano)
            ->where('sigla', $sigla)->first();

        return $servico;
    }

    public function tipoAvaliacaoPorSigla($sigla)
    {
        /*$avaliacao = DB::table('mcal_tb_tipo_avaliacao')
        ->where('sigla', $sigla)->first();*/
        $avaliacao = DB::table('tb_tipo_avaliacao')
        ->where('descricao', $sigla)->first();

        return $avaliacao;
    }
    public function servicoDePropinaPorCurso($codigo_ano)
    {
        $curso = DB::table('tb_cursos')->select('tb_cursos.Designacao as curso')->where('tb_cursos.Codigo', $this->alunoRepository->dadosAlunoLogado()->curso_matricula)->first();

        $servico = DB::table('tb_tipo_servicos')
        ->select('Descricao', 'Preco', 'TipoServico', 'Codigo', 'valor_anterior')
        ->where('Descricao', 'like', 'propina ' .$curso->curso . '%')
        ->where(function ($q) {
          if (auth()->user()->preinscricao->AlunoCacuaco == 'NAO') {
            $q->where('cacuaco', 'NAO');
          } else {
            $q->where('cacuaco', 'SIM');
          }
        })
        ->where('codigo_ano_lectivo', $codigo_ano)
        ->first();

        return $servico;
    }
}
