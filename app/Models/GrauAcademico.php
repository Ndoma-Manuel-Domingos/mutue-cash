<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrauAcademico extends Model
{
    use HasFactory;

    protected $table = "tb_tipo_candidatura";
    
    protected $primaryKey = 'id';
    
    public $timestamps = false;

    protected $fillable = [
        'designacao',
        'status',
        'ordem',
    ];
}
