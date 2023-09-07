<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Acesso extends Model
{
    use HasFactory;
    
    protected $table = "mca_tb_acesso";
    
    protected $primaryKey = 'pk_acesso';
    
    public $timestamps = false;

    protected $fillable = [
        'designacao',
        'descricao',
        'sigla',
        'icone',
        'fk_modulo',
        'fk_submenu',
        'fk_pagina',
        'fk_tipo_acesso',
        'obs',
        'ordem',
    ];
}
