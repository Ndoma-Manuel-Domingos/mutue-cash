<?php

namespace App\Exports;

use App\Http\Controllers\TraitHelpers;
use App\Models\AnoLectivo;
use App\Models\GradeCurricularAluno;
use App\Models\Matricula;
use App\Models\Pagamento;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithMapping;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ListarEstudantesExport implements FromCollection, 
    WithHeadings, 
    ShouldAutoSize, 
    WithMapping,
    WithEvents,
    WithDrawings,
    WithCustomStartCell
{
    use TraitHelpers;

    public $anolectivo, $faculdade, $curso, $turno;

    public function __construct($request)
    {
        $this->anolectivo = $request->anolectivo;
        $this->faculdade = $request->faculdade;
        $this->curso = $request->curso;
        $this->turno = $request->turno;
    }

    public function headings():array
    {
        return[
            'Nº Matricula',
            'Nome',
            'Bilheite',
            'Faculdade',
            'Curso',
            'Turno',
        ];
    }

    public function map($caixa):array
    {
        return[
            $caixa->codigo,
            $caixa->nome,
            $caixa->bilheite,
            $caixa->faculdade,
            $caixa->curso,
            $caixa->periodo,
        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $ano = AnoLectivo::where('estado', 'Activo')->first();

        $anoSelecionado = $this->anolectivo;

        if(!$anoSelecionado){
            $anoSelecionado = $ano->Codigo;
        }
        
        $grade_curriculares = GradeCurricularAluno::when($anoSelecionado, function ($query, $value) {
            $query->where('codigo_ano_lectivo', '=', $value);
            $query->whereIn('Codigo_Status_Grade_Curricular', [2,3]);
        })->distinct('codigo_matricula')->pluck('codigo_matricula');

        return Matricula::when($this->turno, function ($query, $value) {
            $query->where('tb_periodos.Codigo', '=', $value);
        })
        ->when($this->faculdade, function ($query, $value) {
            $query->where('tb_faculdade.codigo', '=', $value);
        })
        ->when($this->curso, function ($query, $value) {
            $query->where('tb_cursos.Codigo', '=', $value);
        })
        ->whereIn('tb_matriculas.Codigo', $grade_curriculares)
        ->join('tb_cursos', 'tb_matriculas.Codigo_Curso', '=', 'tb_cursos.Codigo')
        ->join('tb_faculdade', 'tb_cursos.faculdade_id', '=', 'tb_faculdade.codigo')
        ->join('tb_admissao', 'tb_matriculas.Codigo_Aluno', '=', 'tb_admissao.Codigo')
        ->join('tb_preinscricao', 'tb_admissao.pre_incricao', '=', 'tb_preinscricao.Codigo')
        ->join('tb_periodos', 'tb_preinscricao.Codigo_Turno', '=', 'tb_periodos.Codigo')
        ->select('tb_matriculas.Codigo AS codigo', 
                'tb_preinscricao.Nome_Completo AS nome',
                'tb_preinscricao.Bilhete_Identidade AS bilheite',
                'tb_faculdade.designacao AS faculdade',
                'tb_periodos.Designacao AS periodo',
                'tb_cursos.Designacao AS curso',
        )
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
