<?php

namespace App\Http\Controllers;

use App\Models\AlunoAdmissao;
use App\Models\AnoLectivo;
use App\Models\Bolseiro;
use App\Models\Factura;
use App\Models\GradeCurricularAluno;
use App\Models\GrupoAcesso;
use App\Models\GrupoUtilizador;
use App\Models\LoginAcesso;
use App\Models\Mes;
use App\Models\MesTemp;
use App\Models\Pagamento;
use App\Models\TipoServico;
use App\Models\Turno;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function dashboard(Request $request)
    {
        $user = auth()->user();
        
        $data['items'] = "";

        return Inertia::render('Dashboard', $data);
    }

}
