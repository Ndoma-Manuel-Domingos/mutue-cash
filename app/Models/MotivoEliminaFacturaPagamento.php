<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MotivoEliminaFacturaPagamento extends Model
{
    use HasFactory;
    
    protected $table = "tb_motivo_eliminacao_factura_pagamento";
    
    protected $primaryKey = "codigo";
    
    public $timestamps = false;

    protected $fillable = [
        'descricao',
    ];
}
