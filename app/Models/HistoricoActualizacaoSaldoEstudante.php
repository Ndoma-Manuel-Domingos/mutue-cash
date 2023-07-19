<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoricoActualizacaoSaldoEstudante extends Model
{
    use HasFactory;
    
    protected $table = "tb_instituicao";
    
    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'Instituicao',
        'nif',
        'contacto',
        'Endereco',
        'sigla',
        'updated_at',
        'created_at',

    ];
}
