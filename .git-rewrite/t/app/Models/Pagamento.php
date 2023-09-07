<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pagamento extends Model
{
    use HasFactory;

    protected $table = "tb_pagamentos";

    protected $primaryKey = 'Codigo';
    
    public $timestamps = false;

    protected $fillable = [
        'Data',
        'N_Operacao_Bancaria',
        'N_Operacao_Bancaria2',
        'Observacao',
        'AnoLectivo',
        'Totalgeral',
        'DataBanco',
        'Codigo_PreInscricao',
        'forma_pagamento',
        'valor_depositado',
        'ContaMovimentada',
        'Utilizador',
        'DataRegisto',
        'canal',
        'nome_documento',
        'nome_documento2',
        'estado',
        'codigo_factura',
        'statusMovimento',
        'info_adicional',
        'corrente',
        'fk_utilizador',
    ];
    
    public function operador_antigo()
    {
        return $this->belongsTo(Utilizador::class, 'Utilizador', 'codigo_importado');
    }
    
    public function operador_novos()
    {
        return $this->belongsTo(Utilizador::class, 'fk_utilizador', 'codigo_importado');
    }

    public function items()
    {
        return $this->hasOne(PagamentoItems::class, 'Codigo_Pagamento', 'Codigo');
    }

    public function preinscricao()
    {
        return $this->belongsTo(PreInscricao::class, 'Codigo_PreInscricao', 'Codigo');
    }

    public function factura()
    {
        return $this->belongsTo(Factura::class, 'codigo_factura', 'Codigo');
    }

    public function anolectivo()
    {
        return $this->belongsTo(AnoLectivo::class, 'AnoLectivo', 'Codigo');
    }

    public function utilizadores()
    {
        return $this->belongsTo(Utilizador::class, 'fk_utilizador', 'pk_utilizador');
    }

    public function canal()
    {
        return $this->belongsTo(Canal::class, 'canal', 'codigo');
    }

}
