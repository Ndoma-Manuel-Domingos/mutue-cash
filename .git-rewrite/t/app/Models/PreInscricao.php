<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreInscricao extends Model
{
    use HasFactory;

    protected $table = "tb_preinscricao";
    
    public $timestamps = false;
    
    protected $primaryKey = 'Codigo';

    protected $fillable = [
        'Naturaza_Inscricao',
        'Curso_Candidatura',
        'Modalidade_Frequencia',
        'Nome_Completo',
        'Bilhete_Identidade',
        'Numero_Identificacao_Fiscal',
        'Sexo',
        'Data_Nascimento',
        'Estado_Civil',
        'Contactos_Telefonicos',
        'contacto_de_emergencia',
        'Morada_Completa',
        'Email',
        'Nome_Pessoa_Contacto_Telefone',
        'Instituicao_Formacao_Acesso',
        'Data_Conclusao',
        'Media_Final',
        'Numero_Ordem_Medicos',
        'Instituicao_Exerce_Funcao',
        'Data_Inicio_Trabalho',
        'Provincia_Trabalho',
        'codigo_utilizador',
        'data_emissao_bi',
        'data_validade_bi',
        'desconto',
        'Codigo_Turno',
        'data_preescrincao',
        'data_ultima_actualizacao',
        'Pai',
        'Mae',
        'Naturalidade',
        'Codigo_Nacionalidade',
        'tipo_identificacao',
        'Instituicao_Formacao',
        'anoLectivo',
        'provincia_origem',
        'user_id',
        'estado',
        'polo_id',
        'cursoOpcional1_id',
        'cursoOpcional2_id',
        'Deslocado_Permanente',
        'Codigo_Ocupacao',
        'Codigo_Profissao',
        'Codigo_Habilitacao_Anterior',
        'Codigo_Tipo_Estabelecimento_Secundario',
        'Codigo_pais_habilitacao_anterior',
        'Codigo_Turno_optional',
        'canal',
        'codigo_grau_academico',
        'local_emissao_bi',
        'ocupacao_pai',
        'ocupacao_mae',
        'ocupacao_conjuge',
        'profissao_pai',
        'profissao_mae',
        'profissao_conjuge',
        'grau_academico_pai',
        'grau_academico_mae',
        'grau_academico_conjuge',
        'codigo_provincia_residencia_permanente',
        'codigo_provincia_naturalidade',
        'codigo_tipo_candidatura',
        'codigo_forma_ingresso',
        'AlunoCacuaco',
        'curso_ensino_medio',
        'codigo_curso_pagamento',
        'codigo_municipio',
        'saldo',
        'saldo_anterior',
        'obs_saldo',
        'obs_desconto',
        'codigo_validacao_email',
        'estado_atualizacao_email',
        'permitir_inscricao',
        'isencao_multa',
    ];
    
    public function admissao()
    {
        return $this->hasOne(AlunoAdmissao::class, 'pre_incricao', 'Codigo');
    }

    public function polo()
    {
        return $this->belongsTo(Polo::class, 'polo_id', 'id');
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'Curso_Candidatura', 'Codigo');
    }
    

    public function grau_academico()
    {
        return $this->belongsTo(GrauAcademico::class, 'codigo_tipo_candidatura', 'id');
    }

}
