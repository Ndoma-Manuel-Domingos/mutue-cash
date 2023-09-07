<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActualizarSaldoAluno extends Model
{
    use HasFactory;

    protected $table = "tb_actualizacao_saldo_aluno";
    
    protected $primaryKey = 'id';
        
    public $timestamps = false;

    protected $fillable = [
        'aluno_id',
        'user_id',
        'data_actualizacao',
        'saldo_anterior',
        'saldo_actual',
        'canal',
        'obs',
        'url_anexo',
        'ref_utilizador',       
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function utilizadores()
    {
        return $this->belongsTo(Utilizador::class, 'user_id', 'pk_utilizador');
    }

    public function aluno()
    {
        return $this->belongsTo(PreInscricao::class, 'aluno_id', 'Codigo');
    }

}
