<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bolsa extends Model
{
    use HasFactory;

    protected $table = "tb_tipo_bolsas";
    
    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'Designacao'

    ];
}
