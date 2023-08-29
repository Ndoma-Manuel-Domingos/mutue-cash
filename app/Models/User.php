<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use HasRoles;

    protected $primaryKey = "pk_utilizador";

    protected $table = "mca_tb_utilizador";
    
    public $timestamps = false;

    protected $fillable = [
        'codigo_importado',
        'nome',
        'userName',
        'password',
        'obs',
    ];
    
    public function tipo_grupo()
    {
        return $this->hasOne(GrupoUtilizador::class, 'fk_utilizador', 'pk_utilizador');
    }

}
