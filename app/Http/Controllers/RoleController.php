<?php

namespace App\Http\Controllers;

use App\Models\Grupo;
use App\Models\GrupoUtilizador;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = auth()->user();
        
        // if($user->can('abertura caixa')){
        //     return back()->toast('This notification comes from the server side =)');
        //     // return redirect()->back();
        // }
       
        $data['roles'] = Role::where('sistema', 'cash')->paginate(20);
        $data['permissions'] = Permission::where('sistema', 'cash')->paginate(40);
        
        return Inertia::render('Utilizadores/Roles/Index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        
        
        $request->validate([
            'name' => 'required|unique:roles'
        ], [
            'required.name' => 'Designação é Obrigatoria',
            'unique.name' => 'Designação Já Existe',
        ]);
        
        
        Role::create([
            'name' => $request->name,
            'sistema' => "cash",
        ]);
        
        return redirect()->back();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
        $user = auth()->user();
        
        $request->validate([
            'nome' => 'required'
        ], [
            'required.nome' => 'Designação é Obrigatoria',
        ]);
        
        $upudate = Role::findById($id);
        $upudate->name = $request->nome;
        $upudate->update();
        
        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }    
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function adicionar_permissions(Request $request)
    {
        $user = auth()->user();
        
        $request->validate(
            ['role_id' => 'required'],
            ['permissions_id' => 'required'],
            ['role_id.required' => "Obrigatória"],
            ['permissions_id.required' => "Obrigatória"]
        ); 

        $permissions = Role::with('permissions')->where('id', $request->role_id)->first();
        $role = Role::findById($request->role_id);

        foreach ($permissions->permissions as $permission) {
            $permission->removeRole($role);
        }

        if($request->permissions_id){
            foreach ($request->permissions_id as $permission) {
                $role->givePermissionTo($permission);
            }
        }

        return redirect()->back();
    }
    
    
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getPermissionsRole($role_id)
    {
        $user = auth()->user();
        
        $permissions = Role::with('permissions')
            ->where('id', $role_id)
            ->first();
        return response()->json(['role' => $permissions]);
    }
    
    
    public function getUtilizadores()
    {
        $user = auth()->user();
      
        
        $validacao = Grupo::where('designacao', "Validação de Pagamentos")->select('pk_grupo')->first();
        $admins = Grupo::where('designacao', 'Administrador')->select('pk_grupo')->first();
        $finans = Grupo::where('designacao', 'Area Financeira')->select('pk_grupo')->first();
        $tesous = Grupo::where('designacao', 'Tesouraria')->select('pk_grupo')->first();

        $array_utilizadores = GrupoUtilizador::whereIn('fk_grupo', [$validacao->pk_grupo, $finans->pk_grupo, $tesous->pk_grupo])
        ->orWhere('fk_utilizador', Auth::user()->pk_utilizador)
        ->with('utilizadores')
        ->pluck('fk_utilizador');
        // ->get();
        
        // $data["utilizadores"] = $array_utilizadores;
        
        // $data["utilizadores"] = User::with('roles')->whereIn('pk_utilizador', $array_utilizadores)->where('active_state', 1)->get();
        
        $data["utilizadores"] = User::whereIn('user_pertence', ['Cash','Finance-Cash', 'Todos'])
        ->with('roles')
        // ->whereIn('pk_utilizador', $array_utilizadores)
        ->where('active_state', 1)
        ->get();
        
        
        $data['roles'] = Role::where('sistema', 'cash')->get();
    
        return Inertia::render('Utilizadores/Index', $data);;
    }     
    
    
    public function adicionar_perfil_utilizador(Request $request)
    {
        $user = auth()->user();
        
        $request->validate(
            ['role_id' => 'required'],
            ['user_id' => 'required'],
            ['role_id.required' => "Obrigatória"],
            ['user_id.required' => "Obrigatória"]
        ); 
        
        $user = User::with('roles')->where('codigo_importado', $request->user_id)->first();
        
        foreach ($user->roles as $role){
            $user->removeRole($role);
        }
        
        foreach ($request->role_id as $value) {
            $roles = Role::findById($value);
            $user->assignRole($roles);
        }
     
        return redirect()->back();
    }
    
    
    public function removerPerfilUtilizador($id)
    {
        $user = auth()->user();

        $user = User::with('roles')->where('codigo_importado', $id)->first();
        
        foreach ($user->roles as $role){
            $user->removeRole($role);
        }

        return redirect()->back();
    }
    
    
        /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getPerfilUtilizador($user_id)
    {
    
        $user = auth()->user();
        
        $user = User::with(['roles'])
            ->where('codigo_importado', $user_id)
            ->first();
     
        return response()->json(['utilizador' => $user]);
    }
    
       
}
