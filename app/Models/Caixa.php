<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Caixa extends Model
{
    use HasFactory;

    protected $table = "tb_caixas";

    protected $primaryKey = 'codigo';
    protected $guarded = ['codigo'];

    public $timestamps = false;

    // protected $fillable = [
    //     'nome',
    //     'status',
    //     'created_by',
    //     'updated_by',
    //     'deleted_by',
    // ];
}
