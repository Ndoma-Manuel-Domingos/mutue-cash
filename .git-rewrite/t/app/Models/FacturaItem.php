<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacturaItem extends Model
{
    use HasFactory;
    
    protected $table = "factura_items";
    
    protected $primaryKey = 'codigo';

    public $timestamps = false;

    protected $fillable = [
        'CodigoProduto',
        'CodigoFactura',
        'Quantidade',
        'Total',
        'OBS',
        'TotalIva',
        'preco',
        'descontoProduto',
        'Mes',
        'Multa',
        'mes_temp_id',
        'codigo_anoLectivo',
        'estado',
        'valor_pago',
        'valor_a_transportar',
    ];
    

    public function mes_temp()
    {
        return $this->belongsTo(MesTemp::class, 'mes_temp_id', 'id');
    }

    public function factura()
    {
        return $this->belongsTo(Factura::class, 'CodigoFactura', 'Codigo');
    }

    public function servico()
    {
        return $this->belongsTo(TipoServico::class, 'CodigoProduto', 'Codigo');
    }
}
