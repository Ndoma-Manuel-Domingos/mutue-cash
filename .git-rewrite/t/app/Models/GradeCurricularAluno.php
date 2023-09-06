<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradeCurricularAluno extends Model
{
    use HasFactory;

    protected $table = "tb_grade_curricular_aluno";

    protected $primaryKey = 'codigo';
    
    public $timestamps = false;

    protected $fillable = [
        'codigo_grade_curricular',
        'turma',
        'codigo_confirmacao',
        'codigo_matricula',
        'estado',
        'Nota',
        'user_id',
        'canal',
        'Codigo_Status_Grade_Curricular',
        'codigo_ano_lectivo',
        'epoca',
        'observacao',
        'codigo_utilizador',
        'equivalencia',
        'ref_horario',
        'ref_utilizador',
    ];
}
