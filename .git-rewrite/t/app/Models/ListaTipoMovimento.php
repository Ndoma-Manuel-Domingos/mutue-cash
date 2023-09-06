<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListaTipoMovimento extends Model
{
    use HasFactory;
                
    protected $table = "historico_movimento_conta_estudante";
    
    protected $primaryKey = "codigo";
    
    public $timestamps = false;

    protected $fillable = [
        'referencia',
        'data_movimento',
        'credito',
        'debito',
        'estado',
        'matricula',
        'saldo_operacao',
        'saldo_geral',
        'codigoTipoMovimento',
        'codigoMotivo',
        'codigoUtilizador',
        'observacao',
        'ref_utilizador',
    ];

    public function tipo_movimento()
    {
        return $this->belongsTo(TipoMovimento::class, 'codigoTipoMovimento', 'Codigo');
    }

    public function operador()
    {
        return $this->belongsTo(Utilizador::class, 'codigoUtilizador', 'codigo_importado');
    }
}
