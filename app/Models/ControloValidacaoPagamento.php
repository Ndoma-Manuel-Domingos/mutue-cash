<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ControloValidacaoPagamento extends Model
{
    use HasFactory;
    
    protected $table = "tb_controle_validacao_pagamentos";
    
    protected $primaryKey = 'codigo';
    
    public $timestamps = false;

    protected $fillable = [
        'pagamento',
        'estado_utilizacao',
        'utilizador',
        'observacao',
        'status',
    ];
}
