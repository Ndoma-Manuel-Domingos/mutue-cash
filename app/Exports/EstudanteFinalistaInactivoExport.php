<?php

namespace App\Exports;

use App\Http\Controllers\TraitHelpers;
use App\Models\AnoLectivo;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithMapping;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class EstudanteFinalistaInactivoExport implements FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithMapping,
    WithEvents,
    WithDrawings,
    WithCustomStartCell
{
    use TraitHelpers;

    public $ano_inicio, $ano_final, $grau, $curso;

    public function __construct($request)
    {
        $this->ano_inicio = $request->ano_inicio;
        $this->ano_final = $request->ano_final;
        $this->grau = $request->grau;
        $this->curso = $request->curso;
    }

    public function headings():array
    {
        return[
            'Nº Matricula',
            'Ano De Ingresso',
            'Nome',
            'Bilheite',
            'Curso',
            'E-mail',
            'Telefone',
            // 'Divida (AOA)',
        ];
    }

    public function map($item):array
    {
        return[
            $item->matricula,
            $item->anoLectivo,
            $item->nome,
            $item->bilhete,
            $item->curso,
            $item->email,
            $item->telefone,
            // number_format(0, 2, ',', '.'),
        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $ano = AnoLectivo::where('estado', 'activo')->first();

        if(!$this->ano_inicio){
            $ordem = $ano->ordem;
        }else{
            $ano = AnoLectivo::findOrFail($this->ano_inicio);
            $ordem = $ano->ordem;
        }
        
        if(!$this->ano_final){
            $ordem2 = $ano->ordem;
        }else{
            $ano = AnoLectivo::findOrFail($this->ano_final);
            $ordem2 = $ano->ordem;
        }

        return  DB::table('tb_matriculas AS tm')
        ->selectRaw('DISTINCTROW tp.Nome_Completo AS nome, tp.Bilhete_Identidade AS bilhete, tp.Sexo AS genero, tm.Codigo AS matricula, tc.Designacao AS curso, us.telefone AS telefone, us.email AS email, tal.Designacao AS anoLectivo')
        ->selectRaw('((SELECT COUNT(tpcg.codigo_grade_curricular)
            FROM tb_plano_curricular_grade tpcg
            INNER JOIN tb_plano_curricular_curso tpcc ON tpcg.codigo_plano_curricular_curso = tpcc.codigo
            WHERE tpcc.codigo_curso = tm.Codigo_Curso
            AND tpcc.codigo_ano_lectivo = 1) +
            (SELECT COUNT(tpcg.codigo_grade_curricular)
            FROM tb_plano_curricular_grade tpcg
            INNER JOIN tb_plano_curricular_curso tpcc ON tpcg.codigo_plano_curricular_curso = tpcc.codigo
            INNER JOIN tb_preinscricao tp2 ON ta.pre_incricao = tp2.Codigo
            WHERE tpcc.codigo_curso = tp2.Curso_Candidatura
            AND tpcc.codigo_ano_lectivo = 1
            AND tp2.Curso_Candidatura != tm.Codigo_Curso)) AS qdtCadeirasCurso')
        ->selectRaw('((SELECT COUNT(tgca.codigo_grade_curricular)
            FROM tb_grade_curricular_aluno tgca
            INNER JOIN tb_grade_curricular tgc ON tgc.Codigo = tgca.codigo_grade_curricular
            WHERE tgca.codigo_matricula = tm.Codigo
            AND tgca.Codigo_Status_Grade_Curricular = 3
            AND tgc.status NOT IN (0,3)
            AND tm.Codigo_Curso = tgc.Codigo_Curso)) AS qtdCadeirasConcluidas')
        ->join('tb_admissao AS ta', 'ta.codigo', '=', 'tm.Codigo_Aluno')
        ->join('tb_preinscricao AS tp', 'tp.Codigo', '=', 'ta.pre_incricao')
        ->join('users AS us', 'us.id', '=', 'tp.user_id')
        ->join('tb_provincias', 'tb_provincias.Codigo', '=', 'tp.codigo_provincia_residencia_permanente')
        ->join('tb_nacionalidades', 'tp.Codigo_Nacionalidade', '=', 'tb_nacionalidades.Codigo')
        ->join('tb_municipios AS tm2', 'tm2.Codigo', '=', 'tp.codigo_municipio')
        ->join('tb_cursos AS tc', 'tc.Codigo', '=', 'tm.Codigo_Curso')
        ->join('tb_faculdade', 'tb_faculdade.codigo', '=', 'tc.faculdade_id')
        ->join('tb_grade_curricular_aluno AS tgca2', 'tgca2.codigo_matricula', '=', 'tm.Codigo')
        ->join('tb_ano_lectivo AS tal', 'tal.Codigo', '=', 'tp.anoLectivo')
        ->where('tm.estado_matricula', '=', 'inactivo')
        ->whereBetween('tal.ordem', [$ordem, $ordem2])
        ->havingRaw('(qdtCadeirasCurso - qtdCadeirasConcluidas) <= 10')
        ->when($this->curso, function($query, $value){
            $query->where('tc.Codigo', $value);
        })
        ->when($this->grau, function($query, $value){
            $query->where('tc.tipo_candidatura', $value);
        })
        ->get();
    }


    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event){
                $event->sheet->getStyle('A6:G6')->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                            'color' => ['rgb' => '000000'],
                        ],
                    ]
                ]);

            }
        ];
    }

    public function startCell(): String
    {
        return "A6";
    }

    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('FECHO DO CAIXA');
        $drawing->setPath(public_path('/images/logotipo.png'));
        $drawing->setHeight(90);
        $drawing->setCoordinates('A1');

        return $drawing;
    }


}
