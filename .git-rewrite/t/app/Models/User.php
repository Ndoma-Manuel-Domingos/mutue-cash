<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

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

    // /**
    //  * The attributes that are mass assignable.
    //  *
    //  * @var array<int, string>
    //  */
    // protected $fillable = [
    //     'name',
    //     'email',
    //     'telefone',
    //     'tipo_de_documento',
    //     'numero_documento',
    //     'canal',
    //     'username',
    //     'grauacademico',
    //     'faculdade',
    //     'estado',
    //     'foto',
    //     'status',
    //     'ano_lectivo_id',
    //     'password',
    // ];

    // /**
    //  * The attributes that should be hidden for serialization.
    //  *
    //  * @var array<int, string>
    //  */
    // protected $hidden = [
    //     'password',
    //     'remember_token',
    // ];

    // /**
    //  * The attributes that should be cast.
    //  *
    //  * @var array<string, string>
    //  */
    // protected $casts = [
    //     'email_verified_at' => 'datetime',
    // ];
}
