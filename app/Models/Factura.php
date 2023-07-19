<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Factura extends Model
{
    use HasFactory;

    protected $table = "factura";

    protected $primaryKey = 'Codigo';

    public $timestamps = false;

    protected $fillable = [
        'DataFactura',
        'TotalPreco',
        'CodigoMatricula',
        'polo_id',
        'Referencia',
        'ValorEntregue',
        'Desconto',
        'ValorAPagar',
        'Troco',
        'ValorAPagarExtenso',
        'Descricao',
        'ValorEntregueMltCX',
        'codigo_descricao',
        'totalIVA',
        'NextFactura',
        'dataVencimento',
        'obs',
        'hashValor',
        'contaCorrente',
        'faturaReference',
        'canal',
        'ano_lectivo',
        'estado',
        'TotalMulta',
        'corrente',
        'codigo_preinscricao',
    ];

    public function matriculas()
    {
        return $this->belongsTo(Matricula::class, 'CodigoMatricula', 'Codigo');
    }

    public function items()
    {
        return $this->hasOne(FacturaItem::class, 'CodigoFactura', 'Codigo');
    }

    public function items_factura()
    {
        return $this->hasMany(FacturaItem::class, 'CodigoFactura', 'Codigo');
    }


}
