<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MesTemp extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    
    protected $table = "mes_temp";
    
    public $timestamps = false;

    protected $fillable = [
        'designacao',
        'isencao',
        'ordem_mes',
        'ano_lectivo',
        'prestacao',
        'activo',
        'activo_posgraduacao',
        'data_limite',
        'data_inicial',
        'data_final',
    ];
    
    public function anoletivo()
    {
        return $this->belongsTo(AnoLectivo::class, 'ano_lectivo');
    }
    
    public function facturaitems()
    {
        return $this->hasMany(FacturaItem::class, 'mes_temp_id', 'id');
    }
    
    public function itemsdopagamento()
    {
        return $this->hasMany(PagamentoItems::class, 'mes_temp_id', 'id');
    }
    
    
}
