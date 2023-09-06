<?php

namespace App\Exports;

use App\Http\Controllers\TraitHelpers;
use App\Models\AnoLectivo;
use App\Models\Isencao;
use App\Models\Pagamento;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithMapping;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ListarEstudanteIsentoExport implements FromCollection, 
    WithHeadings, 
    ShouldAutoSize, 
    WithMapping,
    WithEvents,
    WithDrawings,
    WithCustomStartCell
{
    use TraitHelpers;
    
    public $a, $f, $c, $t, $s;

    public function __construct($a, $f, $c, $t, $s)
    {
        $this->a = $a;
        $this->f = $f;
        $this->c = $c;
        $this->t = $t;
        $this->s = $s;
        
    }

    public function headings():array
    {
        return[
            'Nº Matricula',
            'Nome',
            'Isento de:',
            'Mês',
            'Turno',
            'Curso',
            'Ano Lectivo',
        ];
    }

    public function map($caixa):array
    {
        return[
            $caixa->codigoMatricula,
            $caixa->nomeEstudante,
            $caixa->servicoIsencao,
            $caixa->mesIsencao,
            $caixa->turnoIsencao,
            $caixa->cursoIsencao,
            $caixa->Designacao,
        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $ano = AnoLectivo::where('estado', 'Activo')->first();
        $anoSelecionado = $this->a;

        if(!$anoSelecionado){
            $anoSelecionado = $ano->Codigo;
        }

        return Isencao::when($anoSelecionado, function ($query, $value) {
            $query->where('tb_isencoes.codigo_anoLectivo', '=', $value);
        })
        ->when($this->t, function ($query, $value) {
            $query->where('tb_periodos.Codigo', '=', $value);
        })
        ->when($this->f, function ($query, $value) {
            $query->where('tb_faculdade.codigo', '=', $value);
        })
        ->when($this->s, function ($query, $value) {
            $query->where('tb_isencoes.codigo_servico', '=', $value);
        })
        ->when($this->c, function ($query, $value) {
            $query->where('tb_cursos.Codigo', '=', $value);
        })
        ->join('tb_ano_lectivo', 'tb_isencoes.codigo_anoLectivo', '=', 'tb_ano_lectivo.Codigo')
        ->join('mes_temp', 'tb_isencoes.mes_temp_id', '=', 'mes_temp.id')
        ->join('tb_tipo_servicos', 'tb_isencoes.codigo_servico', '=', 'tb_tipo_servicos.Codigo')
        ->join('tb_matriculas', 'tb_isencoes.codigo_matricula', '=', 'tb_matriculas.Codigo')
        ->join('tb_cursos', 'tb_matriculas.Codigo_Curso', '=', 'tb_cursos.Codigo')
        ->join('tb_faculdade', 'tb_cursos.faculdade_id', '=', 'tb_faculdade.codigo')
        ->join('tb_admissao', 'tb_matriculas.Codigo_Aluno', '=', 'tb_admissao.Codigo')
        ->join('tb_preinscricao', 'tb_admissao.pre_incricao', '=', 'tb_preinscricao.Codigo')
        ->join('tb_periodos', 'tb_preinscricao.Codigo_Turno', '=', 'tb_periodos.Codigo')
        ->select('tb_isencoes.codigo_matricula AS codigoMatricula', 
                'tb_ano_lectivo.Designacao', 
                'mes_temp.designacao AS mesIsencao', 
                'tb_tipo_servicos.Descricao AS servicoIsencao',
                'tb_preinscricao.Nome_Completo AS nomeEstudante',
                'tb_periodos.Designacao AS turnoIsencao',
                'tb_cursos.Designacao AS cursoIsencao',
        )
        ->limit(1000)
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
