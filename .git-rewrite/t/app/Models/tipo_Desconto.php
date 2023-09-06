<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tipo_Desconto extends Model
{
    use HasFactory;

    use HasFactory;

    protected $table = "tb_tipo_descontos";
    
    protected $primaryKey = 'Codigo';

    public $timestamps = false;

    protected $fillable = [
        'designacao',
        'valor_desconto',
        'codigo_utlizador',
        'codigo_status',
        'canal',
    ];

    public function utilizador()
    {
        return $this->belongsTo(Utilizador::class, 'codigo_utlizador', 'Codigo');
    }
}


