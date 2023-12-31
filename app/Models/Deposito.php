<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deposito extends Model
{
    use HasFactory;
    
    protected $table = "tb_valor_alunos";
    
    protected $primaryKey = 'codigo';
    
    public $timestamps = false;

    protected $fillable = [
        'codigo_matricula_id',
        'Codigo_PreInscricao',
        'canal_cominucacao_id',
        'valor_depositar',
        'saldo_apos_movimento',
        'forma_pagamento_id',
        'tipo_folha',
        'status',
        'caixa_id',
        'data_movimento',
        'ano_lectivo_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    
    public function caixa()
    {
        return $this->belongsTo(Caixa::class, 'caixa_id', 'codigo');
    }
    
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by', 'codigo_importado');
    }
    
    public function forma_pagamento()
    {
        return $this->belongsTo(FormaPagamento::class, 'forma_pagamento_id', 'Codigo');
    }
    
    public function ano_lectivo()
    {
        return $this->belongsTo(AnoLectivo::class, 'ano_lectivo_id', 'Codigo');
    }
    
    public function matricula()
    {
        return $this->belongsTo(Matricula::class, 'codigo_matricula_id', 'Codigo');
    }
    public function candidato()
    {
        return $this->belongsTo(PreInscricao::class, 'Codigo_PreInscricao', 'Codigo');
    }
}
