<?php

namespace App\Services;
use DB;
class DocumentoService
{
  public function tiposDocumentos()
        {
            
          $documentos= DB::table('tb_tipo_documentos')->whereIn('Codigo',[1,2])->get();
            
            return $documentos;
        }

       
}