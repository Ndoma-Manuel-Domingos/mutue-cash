<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faculdade extends Model
{
    use HasFactory;


    protected $table = "tb_faculdade";
    
    protected $primaryKey = 'codigo';
    
    public $timestamps = false;

    protected $fillable = [
        'designacao',
        'estado',
        'sigla',
        'decano',
        'codigo_utilizador',
        'identificador',
    ];
}
