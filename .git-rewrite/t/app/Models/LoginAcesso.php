<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginAcesso extends Model
{
    use HasFactory;

    protected $table = "log_acessos";
    
    public $timestamps = false;

    protected $fillable = [
        'ip',
        'maquina',
        'browser',
        'user_name',
        'outra_informacao',
        'user_id',
    ];
}
