<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlunoAdmissao extends Model
{
    use HasFactory;
    
    protected $table = "tb_admissao";
    
    protected $primaryKey = 'codigo';
        
    public $timestamps = false;

    protected $fillable = [
        'pre_incricao',
        'mediaFinal',
        'data',
        'resultado',
        'canal',
        'polo_id',
    ];

    public function preinscricao()
    {
        return $this->belongsTo(PreInscricao::class, 'pre_incricao', 'Codigo');
    }

    public function matricula()
    {
        return $this->hasOne(Matricula::class, 'Codigo_Aluno', 'codigo');
    }

    public function matriculaTeste()
    {
        return $this->hasOne(Matricula::class, 'Codigo');
    }


}
