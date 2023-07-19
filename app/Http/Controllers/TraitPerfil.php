<?php

namespace App\Http\Controllers;

use App\Models\Grupo;
use App\Models\GrupoUtilizador;
use App\Models\User;

Trait TraitPerfil {

    public function user_validado($user)
    {
        $validacao = Grupo::where('designacao', "Validação de Pagamentos")->select('pk_grupo')->first();
        $admins = Grupo::where('designacao', 'Administrador')->select('pk_grupo')->first();
        $finans = Grupo::where('designacao', 'Area Financeira')->select('pk_grupo')->first();
        $tesous = Grupo::where('designacao', 'Tesouraria')->select('pk_grupo')->first();
        
        /**
         * recuperando todos os utilizadores que fazem parte dos grupos selecionados acima
         */

         /**
          * grupo admin
          */
        $find = GrupoUtilizador::whereIn('fk_grupo', [$admins->pk_grupo, $validacao->pk_grupo, $tesous->pk_grupo, $finans->pk_grupo])->where('fk_utilizador', $user->pk_utilizador)->first();

        return $find;

    }


}