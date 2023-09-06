<?php

namespace App\Repositories;

use App\Http\Requests\UserRequest;
use App\Interfaces\UserInterface;
use App\Traits\ResponseAPI;
use App\User;
use DB;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;

class UserRepository implements UserInterface
{
    // Use ResponseAPI Trait in this repository
    use ResponseAPI;

    protected $client;

	public function __construct(Client $client)
	{
		$this->client = $client;
	}
 

    public function getAllUsers()
    {
        try {
            //controlar as configurações do sistema para saber se faz ou não o uso da API
            if(env('USE_API',true)){
                $users=$this->endpointRequest('http://localhost:8000/api/users');//lista de utilizadores no Sistema de Gestão

            }else{
                $users = User::limit(10)->get();  //Lista de user na BD local
            }
            

            return $this->success("All Users", $users);
        } catch(\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode());
        }
    }

    

    public function getUserById($id)
    {
        try {
            $user = User::find($id);
            
            // Check the user
            if(!$user) return $this->error("No user with ID $id", 404);

            return $this->success("User Detail", $user);
        } catch(\Exception $e) {
            return $this->error($e->getMessage(), $e->getCode());
        }
    }

    public function requestUser(UserRequest $request, $id = null)
    {
        DB::beginTransaction();
        try {
            // If user exists when we find it
            // Then update the user
            // Else create the new one.
            $user = $id ? User::find($id) : new User;

            // Check the user 
            if($id && !$user) return $this->error("No user with ID $id", 404);

            $user->name = $request->name;
            // Remove a whitespace and make to lowercase
            $user->email = preg_replace('/\s+/', '', strtolower($request->email));
            
            // I dont wanna to update the password, 
            // Password must be fill only when creating a new user.
            if(!$id) $user->password = \Hash::make($request->password);

            // Save the user
            $user->save();

            DB::commit();
            return $this->success(
                $id ? "User updated"
                    : "User created",
                $user, $id ? 200 : 201);
        } catch(\Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage(), $e->getCode());
        }
    }

    public function deleteUser($id)
    {
        DB::beginTransaction();
        try {
            $user = User::find($id);

            // Check the user
            if(!$user) return $this->error("No user with ID $id", 404);

            // Delete the user
            $user->delete();

            DB::commit();
            return $this->success("User deleted", $user);
        } catch(\Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage(), $e->getCode());
        }
    }

    public function endpointRequest($url)
	{
		try {
			$response = $this->client->request('GET', $url);
		} catch (\Exception $e) {

        
            return [];
		}

		return $this->response_handler($response->getBody()->getContents());
	}

	public function response_handler($response)
	{
		if ($response) {
			return json_decode($response);
		}
		
		return [];
	}
}