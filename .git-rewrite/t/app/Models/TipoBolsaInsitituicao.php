<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoBolsaInsitituicao extends Model
{
    use HasFactory;
        
    protected $table = "tb_tipo_bolsa_instituicao";
    
    public $timestamps = false;
    
    protected $primaryKey = 'codigo';

    protected $fillable = [
        'tipo_bolsa',
        'instituicao',
    ];
    
    public function bolsa()
    {
        return $this->belongsTo(TipoBolsa::class, 'tipo_bolsa', 'codigo');
    }

    public function instituicao()
    {
        return $this->belongsTo(Instituicacao::class, 'instituicao', 'codigo');
    }
}
