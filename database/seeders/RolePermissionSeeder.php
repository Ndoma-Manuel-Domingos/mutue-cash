<?php

namespace Database\Seeders;

use App\Models\Caixa;
use App\Models\MovimentoCaixa;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $criar_caixa = Permission::create(['name' => 'criar caixa']);
        $alterar_caixa = Permission::create(['name' => 'alterar caixa']);
        $excluir_caixa = Permission::create(['name' => 'excluir caixa']);
        $listar_caixa = Permission::create(['name' => 'listar caixa']);
        $visualizar_caixa_abertos = Permission::create(['name' => 'visualizar caixa abertos']);
                
        
        $criar_operador = Permission::create(['name' => 'criar operador']);
        $alterar_operador = Permission::create(['name' => 'alterar operador']);
        $excluir_operador = Permission::create(['name' => 'excluir operador']);
        $listar_operador = Permission::create(['name' => 'listar operador']);
        
        $abertura_caixa = Permission::create(['name' => 'abertura caixa']);
        $fecho_caixa = Permission::create(['name' => 'fecho caixa']);
        $validar_caixa = Permission::create(['name' => 'validar caixa']);
        $relatorio_caixa = Permission::create(['name' => 'relatorio caixa']);
        
        $criar_relatorio = Permission::create(['name' => 'criar relatorio']);
        $alterar_relatorio = Permission::create(['name' => 'alterar relatorio']);
        $excluir_relatorio = Permission::create(['name' => 'excluir relatorio']);
        $listar_relatorio = Permission::create(['name' => 'listar relatorio']);
        
        $criar_deposito = Permission::create(['name' => 'criar deposito']);
        $alterar_deposito = Permission::create(['name' => 'alterar deposito']);
        $excluir_deposito = Permission::create(['name' => 'excluir deposito']);
        $listar_deposito = Permission::create(['name' => 'listar deposito']);
        
        $criar_pagamento = Permission::create(['name' => 'criar pagamento']);
        $alterar_pagamento = Permission::create(['name' => 'alterar pagamento']);
        $excluir_pagamento = Permission::create(['name' => 'excluir pagamento']);
        $listar_pagamento = Permission::create(['name' => 'listar pagamento']);
        $extrato_pagamento = Permission::create(['name' => 'extrato pagamento']);
        
        $extrato_deposito = Permission::create(['name' => 'extrato deposito']);
        $relatorio_operador = Permission::create(['name' => 'relatorio operador']);
        $relatorio_diario = Permission::create(['name' => 'relatorio diario']);
        
        $relatorio_diario_caixa = Permission::create(['name' => 'relatorio diario caixa']);
        
        
        $gestor_caixa = Role::create(['name' => 'Gestor de Caixa'])->syncPermissions([
            $criar_caixa,
            $alterar_caixa,
            $excluir_caixa,
            $listar_caixa,
            $criar_operador,
            $alterar_operador,
            $excluir_operador,
            $listar_operador,
            $abertura_caixa,
            $fecho_caixa,
            $validar_caixa,
            $relatorio_caixa,
            $criar_relatorio,
            $alterar_relatorio,
            $excluir_relatorio,
            $listar_relatorio,
            $criar_deposito,
            $alterar_deposito,
            $excluir_deposito,
            $listar_deposito,
            $criar_pagamento,
            $alterar_pagamento,
            $excluir_pagamento,
            $listar_pagamento,
            $extrato_pagamento,
            $extrato_deposito,
            $relatorio_operador,
            $relatorio_diario,
            $visualizar_caixa_abertos
        ]);
        
        $supervisor_caixa = Role::create(['name' => 'Supervisor'])->syncPermissions([
            $abertura_caixa,
            $fecho_caixa,
            $validar_caixa,
            $criar_deposito,
            $listar_deposito,
            $criar_pagamento,
            $listar_pagamento,
            $relatorio_operador,
            $relatorio_diario_caixa,
            $visualizar_caixa_abertos
        ]);
        
        $operador_caixa = Role::create(['name' => 'Operador Caixa'])->syncPermissions([
            $fecho_caixa,
            $criar_deposito,
            $listar_deposito,
            $criar_pagamento,
            $listar_pagamento,
            $relatorio_operador,
            $relatorio_diario_caixa,
        ]);

        // fornecendo permission perfil ao um utilizador em especifico para depois estte distribuir para todos
        $user_id = '1513';
        $user = User::findOrFail($user_id);
        $user->assignRole($gestor_caixa);
        
        $caixas = Caixa::get();
        
        foreach ($caixas as $caixa) {
            $update = Caixa::find($caixa->id);
            $update->code = NULL;
            $update->status = "fachado";
            $update->operador_id = NULL;
            $update->created_at = NULL;
            $update->bloqueio = "N";
            
            $update->update();
        }
        
        $movimentos = MovimentoCaixa::get();
        
        foreach ($movimentos as $movimento) {
            $update_movimento = Caixa::find($movimento->id);
            $update_movimento->status = "fachado";
            $update_movimento->update();
        }
        
        

    }
}
