<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prestacao extends Model
{
    use HasFactory;
    
    protected $table = "prestacao";
    
    public $timestamps = false;

    protected $fillable = [
        'mes',
        'mes_id',
        'codigo_propina',
    ];
}
