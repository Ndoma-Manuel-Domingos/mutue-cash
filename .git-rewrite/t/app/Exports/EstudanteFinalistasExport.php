<?php

namespace App\Exports;

use App\Http\Controllers\TraitHelpers;
use App\Models\Matricula;
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

class EstudanteFinalistasExport implements FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithMapping,
    WithEvents,
    WithDrawings,
    WithCustomStartCell
{
    use TraitHelpers;

    public $searchCurso, $searchTurno;

    public function __construct($request)
    {
        $this->searchCurso = $request->searchCurso;
        $this->searchTurno = $request->searchTurno;

    }

    public function headings():array
    {
        return[
            'Nº Matricula',
            'Nome',
            'Bilheite',
            'Curso',
            'Periodo',
        ];
    }

    public function map($item):array
    {
        return[
            $item->matricula,
            $item->bilhete,
            $item->nome,
            $item->curso,
            $item->turno,
        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Matricula::whereNotNull('tb_matriculas.Codigo')
        ->selectRaw('DISTINCT
        tb_preinscricao.Nome_Completo AS nome,
        tb_preinscricao.Bilhete_Identidade AS bilhete,
        tb_periodos.Designacao AS turno,
        COALESCE(tb_matriculas.Codigo,"") AS matricula,
        tb_cursos.Designacao AS curso,
        (
            (
                SELECT COUNT(tb_plano_curricular_grade.codigo_grade_curricular)
                FROM tb_plano_curricular_grade
                INNER JOIN tb_plano_curricular_curso ON tb_plano_curricular_grade.codigo_plano_curricular_curso = tb_plano_curricular_curso.codigo
                WHERE tb_plano_curricular_curso.codigo_curso = tb_matriculas.Codigo_Curso
                AND tb_plano_curricular_curso.codigo_ano_lectivo = 18
            ) +
            (
                SELECT COUNT(tb_plano_curricular_grade.codigo_grade_curricular)
                FROM tb_plano_curricular_grade
                INNER JOIN tb_plano_curricular_curso ON tb_plano_curricular_grade.codigo_plano_curricular_curso = tb_plano_curricular_curso.codigo
                INNER JOIN tb_preinscricao ON tb_admissao.pre_incricao = tb_preinscricao.Codigo
                WHERE tb_plano_curricular_curso.codigo_curso = tb_preinscricao.Curso_Candidatura
                AND tb_plano_curricular_curso.codigo_ano_lectivo = 18
                AND tb_preinscricao.Curso_Candidatura != tb_matriculas.Codigo_Curso
            )
        ) AS qdtCadeirasCurso,
        (
            (
                SELECT COUNT(tb_grade_curricular_aluno.codigo_grade_curricular)
                FROM tb_grade_curricular_aluno
                INNER JOIN tb_grade_curricular ON tb_grade_curricular.Codigo = tb_grade_curricular_aluno.codigo_grade_curricular
                WHERE tb_grade_curricular_aluno.codigo_matricula = tb_matriculas.Codigo
                AND tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular = 3
                AND tb_grade_curricular.status NOT IN (0, 3)
                AND tb_matriculas.Codigo_Curso = tb_grade_curricular.Codigo_Curso
            )
        ) AS qtdCadeirasConcluidas')
        ->when($this->searchCurso, function ($query, $value) {
            $query->where('tb_cursos.Codigo', '=', $value);
        })
        ->when($this->searchTurno, function ($query, $value) {
            $query->where('tb_periodos.Codigo', '=', $value);
        })
        ->join('tb_admissao', 'tb_admissao.codigo', '=', 'tb_matriculas.Codigo_Aluno')
        ->join('tb_preinscricao', 'tb_preinscricao.Codigo', '=', 'tb_admissao.pre_incricao')
        ->join('tb_periodos', 'tb_preinscricao.Codigo_Turno', '=', 'tb_periodos.Codigo')
        ->join('tb_provincias', 'tb_provincias.Codigo', '=', 'tb_preinscricao.codigo_provincia_residencia_permanente')
        ->join('tb_nacionalidades', 'tb_preinscricao.Codigo_Nacionalidade', '=', 'tb_nacionalidades.Codigo')
        ->join('tb_municipios', 'tb_municipios.Codigo', '=', 'tb_preinscricao.codigo_municipio')
        ->join('tb_cursos', 'tb_cursos.Codigo', '=', 'tb_matriculas.Codigo_Curso')
        ->join('tb_faculdade', 'tb_faculdade.codigo', '=', 'tb_cursos.faculdade_id')
        ->join('tb_grade_curricular_aluno', 'tb_grade_curricular_aluno.codigo_matricula', '=', 'tb_matriculas.Codigo')
        ->whereNotIn('tb_matriculas.estado_matricula', ['inactivo', 'diplomado'])
        ->where('tb_grade_curricular_aluno.Codigo_Status_Grade_Curricular', 2)
        ->where('tb_grade_curricular_aluno.codigo_ano_lectivo', 18)

        ->havingRaw('(qdtCadeirasCurso - qtdCadeirasConcluidas) <= 3')
        ->whereNotNull('tb_matriculas.Codigo')
        ->orderBy('tb_preinscricao.Nome_Completo', 'asc')
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
