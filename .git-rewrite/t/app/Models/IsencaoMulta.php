<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IsencaoMulta extends Model
{
    use HasFactory;

    protected $primaryKey = "codigo";
    
    protected $table = "tb_isencoe_multa";
    
    public $timestamps = false;

    protected $fillable = [
        'codigo_matricula',
        'codigo_servico',
        'codigo_utilizador',
        'mes_temp_id',
        'data_isencao',
        'canal',
        'obs',
        'estado_isensao',
        'codigo_anoLectivo',
        'Codigo_motivo',
        'mes_id',
        'ref_utilizado',
    ];

    public function servico()
    {
        return $this->belongsTo(TipoServico::class, 'codigo_servico', 'Codigo');
    }

    public function mes()
    {
        return $this->belongsTo(MesTemp::class, 'mes_temp_id', 'id');
    }
}
