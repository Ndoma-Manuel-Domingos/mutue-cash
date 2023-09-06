<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PagamentoPorReferencia extends Model
{
    use HasFactory;
    
    protected $table = "pagamento_por_referencias";
    
    protected $primaryKey = 'id';
    
    public $timestamps = false;

    protected $fillable = [
        'PAYMENT_ID',
        'SOURCE_ID',
        'factura_codigo',
        'ENTITY_ID',
        'REFERENCE',
        'AMOUNT',
        'START_DATE',
        'END_DATE',
        'Status',
    ];

    
    
}
