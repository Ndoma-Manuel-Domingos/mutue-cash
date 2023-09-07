<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrupoUtilizador extends Model
{
    use HasFactory;

    protected $table = "mca_tb_grupo_utilizador";
    
    public $timestamps = false;

    protected $fillable = [
        'fk_grupo',
        'fk_utilizador',
        'pk_grupo_utilizador'
    ];

    public function utilizadores()
    {
        return $this->belongsTo(Utilizador::class, 'fk_utilizador', 'pk_utilizador');
    }
    
    public function grupo()
    {
        $validacao = Grupo::where('designacao', "Validação de Pagamentos")->select('pk_grupo')->first();
        $admins = Grupo::where('designacao', 'Administrador')->select('pk_grupo')->first();
        $finans = Grupo::where('designacao', 'Area Financeira')->select('pk_grupo')->first();
        $tesous = Grupo::where('designacao', 'Tesouraria')->select('pk_grupo')->first();
    
        return $this->belongsTo(Grupo::class, 'fk_grupo', 'pk_grupo');
    }
}
