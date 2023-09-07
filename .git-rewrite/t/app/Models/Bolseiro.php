<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bolseiro extends Model
{
    use HasFactory;
    
    protected $table = "tb_bolseiros";
    
    public $timestamps = false;
    
    protected $primaryKey = 'codigo';

    protected $fillable = [
        'codigo_matricula',
        'codigo_tipo_bolsa',
        'desconto',
        'isentar_multa',
        'codigo_utilizador',
        'data_inicio_bolsa',
        'data_fim_bolsa',
        'codigo_Instituicao',
        'pagarTaxasAdicionais',
        'codigo_anoLectivo',
        'afectacao',
        'observacao',
        'historico',
        'status',
        'semestre',
        'estadoBolsa',
        'ref_utilizador',
        'canal',
        'created_at',
        'updated_at',
    ];

    public function matriculas()
    {
        return $this->belongsTo(Matricula::class, 'codigo_matricula', 'Codigo');
    }
}
