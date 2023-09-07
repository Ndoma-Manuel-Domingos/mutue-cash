<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Polo extends Model
{
    use HasFactory;

    protected $table = "polos";
    
    protected $primaryKey = 'id';
    
    public $timestamps = false;

    protected $fillable = [
        'designacao',
        'observacao',
    ];

    public function servico()
    {
        return $this->hasOne(TipoServico::class);
    }
}
