<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Caixa extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = "tb_caixas";

    protected $primaryKey = 'codigo';
    protected $guarded = ['codigo'];
    protected $dates = ['deleted_at'];

    public $timestamps = false;

    public static function boot(){

        parent::boot();

        self::creating(function($model){
            $model->created_by = auth()->user()->codigo_importado;
        });
    }

    public function operador()
    {
        return $this->belongsTo(User::class, 'operadpr_id', 'pk_utilizador');
    }
    
    public function operador_que_abriu()
    {
        return $this->belongsTo(User::class, 'created_by', 'codigo_importado');
    }
}
