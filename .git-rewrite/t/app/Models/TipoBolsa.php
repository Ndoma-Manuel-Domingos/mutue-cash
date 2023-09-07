<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoBolsa extends Model
{
    use HasFactory;

    protected $table = "tb_tipo_bolsas";
    
    protected $primaryKey = 'codigo';

    public $timestamps = false;
    
    protected $fillable = [
        'designacao',
    ];
        
    public function instituicoes()
    {
        return $this->hasMany(TipoBolsaInsitituicao::class, 'instituicao', 'codigo');
    }
}
