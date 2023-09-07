<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banco extends Model
{
    use HasFactory;
    
    protected $table = "tb_banco";

    protected $primaryKey = 'pk_banco';
    
    public $timestamps = false;

    protected $fillable = [
        'descricao',
    ];
}
