<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FacturaItens extends Model
{
    protected $table="factura_items";
    protected $primaryKey="codigo";
    protected $guarded = ['codigo'];
    public $timestamps = false;

    public function factura(){
        return $this->belongsTo('App\Factura','CodigoFactura','Codigo');
    }

}
