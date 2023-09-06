<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grupo extends Model
{
    use HasFactory;
    
    protected $table = "mca_tb_grupo";
    
    protected $primaryKey = 'pk_grupo';
    
    public $timestamps = false;

    protected $fillable = [
        'designacao',
        'sigla',
    ];
}
