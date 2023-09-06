<?php

namespace App\Http\Controllers;

use App\Exports\InstituicaoRenunciaExport;
use App\Exports\InstituicaoSemRenunciaExport;
use App\Exports\TipoBolsaExport;
use App\Models\InstituicaoRenuncia;
use App\Models\TipoBolsa;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class PDFController extends Controller
{
    public function pdf(Request $request)
    {
        // $data['instituicao'] = InstituicaoRenuncia::when($request->tipo_instituicao, function($query, $value){
        //     $query->where('tipo_instituicao', $value);
        // })->with('tipo')->get();

        // $pdf = \App::make('dompdf.wrapper');
        // $pdf->loadView('pdf.Instituicao-renuncia.instituicoes', $data);
        // $pdf->getDOMPdf()->set_option('isPhpEnabled', true);
        // return $pdf->stream();
    }
    
}
