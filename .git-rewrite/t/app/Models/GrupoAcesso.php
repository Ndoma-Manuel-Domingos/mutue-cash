<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrupoAcesso extends Model
{
    use HasFactory;
    
    protected $primaryKey = "pk_grupo_acesso";

    protected $table = "mca_tb_grupo_acesso";
    
    public $timestamps = false;

    protected $fillable = [
        'fk_grupo',
        'fk_acesso',
        'obs',
        'ordem',
        'obs',
    ];
    
    public function grupo() {
        return $this->belongsTo(Grupo::class, 'fk_grupo', 'pk_grupo');
    }
    
    
    public function acesso() {
        return $this->belongsTo(Acesso::class, 'fk_acesso', 'pk_acesso');
    }
    
    public function acessos() {
        return $this->belongsToMany(Acesso::class, 'fk_acesso', 'pk_acesso');
    }
}
