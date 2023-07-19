<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class estados extends Model
{
    use HasFactory;

    protected $table = "tb_status";
    
    protected $primaryKey = 'Codigo';

    public $timestamps = false;

    protected $fillable = [
        'Designacao',
    ];
}
