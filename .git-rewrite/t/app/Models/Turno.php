<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Turno extends Model
{
    use HasFactory;

    protected $table = "tb_periodos";
    
    protected $primaryKey = 'Codigo';
    
    public $timestamps = false;

    protected $fillable = [
        'Designacao',
        'status',
    ];
}
