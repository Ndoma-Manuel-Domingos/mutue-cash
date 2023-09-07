<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Simestre extends Model
{
    use HasFactory;
        
    protected $table = "tb_semestres";
    
    public $timestamps = false;

    protected $fillable = [
        'Designacao',
    ];
}
