<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoInstituicao extends Model
{
    use HasFactory;
    
    protected $table = "tb_tipo_instituicao";
    
    protected $primaryKey = 'codigo';

    public $timestamps = false;
    
    protected $fillable = [
        'designacao',
        'descricao',
        'ref_utilizador',
    ];
    
    public function instituicoes()
    {
        return $this->hasMany(TipoBolsaInsitituicao::class, 'tipo_bolsa', 'codigo');
    }
}
