<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstadoPagamento extends Model
{
    use HasFactory;

    protected $table = "tb_estado_lista_presenca";
    
    public $timestamps = false;

    protected $fillable = [
        'designacao',
    ];
}
