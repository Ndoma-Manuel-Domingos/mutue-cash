<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoServico extends Model
{
    use HasFactory;

    protected $table = "tb_tipo_servicos";

    protected $primaryKey = 'Codigo';
    
    public $timestamps = false;

    protected $fillable = [
        'Preco',
        'Descricao',
        'TipoServico',
        'dataCriacao',
        'estado',
        'data',
        'disponibilizar_aluno',
        'codigo_grade_currilular',
        'Mestrado',
        'canal',
        'polo_id',
        'cacuaco',
        'codigo_ano_lectivo',
        'valor_anterior',
        'visualizar_no_portal',
        'sigla',
        'estado_solicitacao',
        'tipo_candidatura',
    ];

    public function polo()
    {
        return $this->belongsTo(Polo::class);
    }
}
