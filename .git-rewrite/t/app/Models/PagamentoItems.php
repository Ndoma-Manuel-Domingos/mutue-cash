<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PagamentoItems extends Model
{
    use HasFactory;
    
    protected $table = "tb_pagamentosi";

    protected $primaryKey = 'codigo';

    public $timestamps = false;

    protected $fillable = [
        'Codigo_Pagamento',
        'Codigo_Servico',
        'Valor_Pago',
        'Mes',
        'Quantidade',
        'Valor_Total',
        'Multa',
        'Deconnto',
        'Ano',
        'Estado',
        'mes_id',
        'mes_temp_id',
    ];

    public function servico()
    {
        return $this->belongsTo(TipoServico::class, 'Codigo_Servico', 'Codigo');
    }


    public function mes_temps()
    {
        return $this->belongsTo(MesTemp::class, 'mes_temp_id', 'id');
    }
    
    public function mes()
    {
        return $this->belongsTo(Mes::class, 'mes_id');
    }

}
