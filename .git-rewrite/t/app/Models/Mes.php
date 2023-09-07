<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mes extends Model
{
    use HasFactory;

    protected $primaryKey = 'codigo';
    
    protected $table = "meses";
    
    public $timestamps = false;

    protected $fillable = [
        'mes'
    ];
    
    public function pagamentositems()
    {
        return $this->hasMany(PagamentoItems::class, 'mes_id', 'codigo');
    }
    
    
}
