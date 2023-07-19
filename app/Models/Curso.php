<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    use HasFactory;

    protected $table = "tb_cursos";
    
    protected $primaryKey = 'Codigo';
    
    public $timestamps = false;

    protected $fillable = [
        'Designacao',
        'Coordenador',
        'tipo_curso',
        'sigla',
        'grau',
        'ano_lectivo',
        'canal',
        'tipo_candidatura',
        'status',
        'faculdade_id',
        'duracao',
        'numero_max_cadeiras',
        'codigo_director',
    ];
}
