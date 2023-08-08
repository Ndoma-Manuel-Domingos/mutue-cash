<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimentoCaixa extends Model
{
    use HasFactory;


    protected $table = "tb_movimentos_caixas";

    protected $primaryKey = 'codigo';
    protected $guarded = ['codigo'];

    public $timestamps = false;

    // protected $fillable = [
    //     'caixa_id',
    //     'operador_id',
    //     'operador_admin_id',
    //     'valor_abertura',
    //     'valor_arrecadado_total',
    //     'valor_arrecadado_depositos',
    //     'valor_arrecadado_pagamento',
    //     'valor_facturado_pagamento',
    //     'status',
    //     'status_admin',
    //     'created_by',
    //     'updated_by',
    //     'deleted_by',
    // ];

    public function operador_admin()
    {
        return $this->belongsTo(User::class, 'operador_admin_id', 'codigo_importado');
    }

    public function operador()
    {
        return $this->belongsTo(User::class, 'operador_id', 'codigo_importado');
    }

    public function caixa()
    {
        return $this->belongsTo(Caixa::class, 'caixa_id', 'codigo');
    }
}
