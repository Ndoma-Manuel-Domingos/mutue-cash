<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnoLectivo extends Model
{
    use HasFactory;

    protected $table = "tb_ano_lectivo";

    protected $primaryKey = 'Codigo';

    public $timestamps = false;

    protected $fillable = [
        'Designacao',
        'dataInicioPrimeiroSemestre',
        'dataFimPrimeiroSemestre',
        'dataInicioPrimeiroSemestre',
        'dataInicioSegundoSemestre',
        'dataFimSegundoSemestre',
        'estado',
        'data_ultima_atualizacao',
        'utilizador',
        'status',
        'ordem',
    ];


    public function mes_temps()
    {
        return $this->hasMany(MesTemp::class, 'ano_lectivo', 'Codigo');
    }

}
