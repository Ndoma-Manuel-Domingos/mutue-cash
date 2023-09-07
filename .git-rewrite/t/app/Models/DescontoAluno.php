<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DescontoAluno extends Model
{
    use HasFactory;

    protected $table = "tb_descontos_alunoo";
    
    protected $primaryKey = 'codigo';

    public $timestamps = false;

    protected $fillable = [
        'codigo_matricula',
        'codigo_tipo_desconto',
        'instituicao_id',
        'isentar_multa',
        'codigo_utilizador',
        'instituicao_id',
        'codigo_anoLectivo',
        'afectacao',
        'observacao',
        'status',
        'semestre',
        'estatus_desconto_id',
        'ref_utilizador',
        'canal'
    ];

    public function matriculas()
    {
        return $this->belongsTo(Matricula::class, 'codigo_matricula', 'Codigo');
    }
}
