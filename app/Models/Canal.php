<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Canal extends Model
{
    use HasFactory;
    
    protected $table = "tb_canal_comunicacao";
    
    protected $primaryKey = "codigo";
    
    public $timestamps = false;

    protected $fillable = [
        'designacao',
        'status',
        'descricao',
    ];
}
