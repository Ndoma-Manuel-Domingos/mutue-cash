<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoMovimento extends Model
{
    use HasFactory;
            
    protected $table = "tb_tipo_movimento";
    
    public $timestamps = false;

    protected $fillable = [
        'designacao',
    ];

}
