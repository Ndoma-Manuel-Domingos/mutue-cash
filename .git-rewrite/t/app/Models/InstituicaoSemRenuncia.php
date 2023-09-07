<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstituicaoSemRenuncia extends Model
{
    use HasFactory;
    
    protected $table = "tb_Instituicao";

    protected $primaryKey = 'codigo';

    public $timestamps = false;

    protected $fillable = [
        'Instituicao',
        'nif',
        'contacto',
        'Endereco',
        'tipo_instituicao',
        'sigla',
    ];
    
    
    public function bolsas()
    {
        return $this->hasMany(TipoBolsaInsitituicao::class, 'instituicao', 'codigo');
    }
    
    
    public function tipo()
    {
        return $this->belongsTo(TipoInstituicao::class, 'tipo_instituicao', 'codigo');
    }
    
}
