<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ActiveUtilizadorScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $builder->where('active_state', 1); // Substitua 'estado' pelo nome do campo de estado em sua tabela de usuários, se necessário.
    }
}
