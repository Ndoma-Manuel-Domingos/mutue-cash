<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Utilizador extends Model
{
    use HasFactory;

    protected $table = "mca_tb_utilizador";

    protected $primaryKey = 'pk_utilizador';
    
    public $timestamps = false;

    protected $fillable = [
        'codigo_importado',
        'nome',
        'userName',
        'password',
        'obs',
    ];

}
