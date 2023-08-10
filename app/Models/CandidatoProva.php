<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidatoProva extends Model
{
    protected $guarded = ['id'];

    protected $table="candidato_provas";

    protected $casts = [
        'provaFeita' => 'object',
    ];

    public function candidato(){
        return  $this->belongsTo('App\Candidato','candidato_id');
    }

    public function prova(){
       return $this->belongsTo('App\Prova','prova_id');
    }

    public function horario(){
        return $this->belongsTo('App\HorarioProva','horario_prova_id');
    }

}
