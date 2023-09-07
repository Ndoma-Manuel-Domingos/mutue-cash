<?php

namespace App\Http\Controllers;

use App\Models\AnoLectivo;
use App\Models\GradeCurricularAluno;

Trait TraitHelpers{

    /** 
     * Ano lectivo  activo
     * */
    public function anoLectivoActivo()
    {
        $ano_lectivo = AnoLectivo::where('estado', 'Activo')->first();
        if(!$ano_lectivo){
            return false;
        }
        return $ano_lectivo->Codigo;
    }
    
    public function anoLectivoActivoMestrado()
    {
        $ano_lectivo = AnoLectivo::where('Codigo', 19)->first();
        if(!$ano_lectivo){
            return false;
        }
        return $ano_lectivo->Codigo;
    }
    
    public function anoLectivoActivoDoutorado()
    {
        $ano_lectivo = AnoLectivo::where('Codigo', 20)->first();
        if(!$ano_lectivo){
            return false;
        }
        return $ano_lectivo->Codigo;
    }



    public function anoLectivoEstudante($codigo_matricula)
    {
        $anos = GradeCurricularAluno::whereIn('codigo_matricula', [$codigo_matricula])->distinct()->orderBy('codigo_ano_lectivo', "desc")->get(['codigo_ano_lectivo']);
        
        return $anos;
    }


    public function anoLectivoActivoAnterior()
    {
        $ano_lectivo = AnoLectivo::where('Codigo', $this->anoLectivoActivo())->first();

        if(!$ano_lectivo){
            return false;
        }
        $ordem = $ano_lectivo->ordem - 1;
        $ano_lectivo_ordem = AnoLectivo::where('ordem', $ordem)->first();

        if(!$ano_lectivo_ordem){
            return false;
        }
        return $ano_lectivo_ordem->Codigo;
    }
    
    function valor_por_extenso( $v ){
		
        $v = filter_var($v, FILTER_SANITIZE_NUMBER_INT);
       
        $sin = array("centavo", "real", "mil", "milhão", "bilhão", "trilhão", "quatrilhão");
        $plu = array("centavos", "", "mil", "milhões", "bilhões", "trilhões","quatrilhões");

        $c = array("", "cem", "duzentos", "trezentos", "quatrocentos","quinhentos", "seiscentos", "setecentos", "oitocentos", "novecentos");
        $d = array("", "dez", "vinte", "trinta", "quarenta", "cinquenta","sessenta", "setenta", "oitenta", "noventa");
        $d10 = array("dez", "onze", "doze", "treze", "quatorze", "quinze","dezesseis", "dezesete", "dezoito", "dezenove");
        $u = array("", "um", "dois", "três", "quatro", "cinco", "seis","sete", "oito", "nove");

        $z = 0;
    
        $v = number_format( $v, 2, ".", "." );
        $int = explode( ".", $v );
    
        for ( $i = 0; $i < count( $int ); $i++ ) 
        {
            for ( $ii = mb_strlen( $int[$i] ); $ii < 3; $ii++ ) 
            {
                $int[$i] = "0" . $int[$i];
            }
        }
    
        $rt = null;
        $fim = count( $int ) - ($int[count( $int ) - 1] > 0 ? 1 : 2);
        for ( $i = 0; $i < count( $int ); $i++ )
        {
            $v = $int[$i];
            $rc = (($v > 100) && ($v < 200)) ? "cento" : $c[$v[0]];
            $rd = ($v[1] < 2) ? "" : $d[$v[1]];
            $ru = ($v > 0) ? (($v[1] == 1) ? $d10[$v[2]] : $u[$v[2]]) : "";
    
            $r = $rc . (($rc && ($rd || $ru)) ? " e " : "") . $rd . (($rd && $ru) ? " e " : "") . $ru;
            $t = count( $int ) - 1 - $i;
            $r .= $r ? " " . ($v > 1 ? $plu[$t] : $sin[$t]) : "";
            if ( $v == "000")
                $z++;
            elseif ( $z > 0 )
                $z--;
                
            if ( ($t == 1) && ($z > 0) && ($int[0] > 0) )
                $r .= ( ($z > 1) ? " de " : "") . $plu[$t];
                
            if ( $r )
                $rt = $rt . ((($i > 0) && ($i <= $fim) && ($int[0] > 0) && ($z < 1)) ? ( ($i < $fim) ? ", " : " e ") : " ") . $r;
        }
     
        $rt = mb_substr( $rt, 1 );
    
        return($rt ? trim( $rt ) : "zero");
     
    }

}