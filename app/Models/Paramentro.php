<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paramentro extends Model
{
    use HasFactory;
                
    protected $table = "tb_parametros";
    
    protected $primaryKey = "Codigo";
    
    public $timestamps = false;

    protected $fillable = [
        'Designacao',
        'Valor',
        'Descricao',
        'CodigoEmpresa',
        'Num_max_faltas',
        'Num_meses_atraso',
        'control_ip',
        'ip_interno',
        'ip_externo',
        'polo_marc_assuididade',
        'turno_marc_assuididade',
        'estado',
    ];
}
