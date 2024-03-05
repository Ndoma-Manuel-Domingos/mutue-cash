<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Acesso extends Model
{
    use HasFactory;
    
    protected $table = "log_mutue_cash";
    
    protected $primaryKey = 'id';
    
    public $timestamps = false;

    protected $fillable = [
        'designacao',
        'descricao',
        'ip_maquina',
        'browser',
        'nome_maquina',
        'rota_acessado',
        'utilizador_id',
    ];
    
    public function operador()
    {
        return $this->belongsTo(User::class, 'utilizador_id', 'pk_utilizador');
    }

}
