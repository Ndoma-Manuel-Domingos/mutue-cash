<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MotivoIsencao extends Model
{
    use HasFactory;
    
    protected $table = "tb_motivo_isencao";
    
    public $timestamps = false;

    protected $fillable = [
        'Codigo',
        'Descricao',
    ];
}
