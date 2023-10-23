<?php

namespace App\Models;

use App\Scopes\ActiveUserScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Permission;
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
        'active_state'
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new ActiveUserScope());
    }


    public function tipo_grupo()
    {
        return $this->hasOne(GrupoUtilizador::class, 'fk_utilizador', 'pk_utilizador');
    }
    

     /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['all_permissions', 'can'];

    /**
     * Get all user permissions.
     *
     * @return bool
     */
    public function getAllPermissionsAttribute()
    {
        return $this->getAllPermissions();
    }

    /**
     * Get all user permissions in a flat array.
     *
     * @return array
     */
    public function getCanAttribute()
    {
        $permissions = [];
        foreach (Permission::all() as $permission) {

            if (Auth::user())
                if (Auth::user()->can($permission->name)) {
                    $permissions[$permission->name] = true;
                } else {
                    $permissions[$permission->name] = false;
                }
        }
        return $permissions;
    }

}
