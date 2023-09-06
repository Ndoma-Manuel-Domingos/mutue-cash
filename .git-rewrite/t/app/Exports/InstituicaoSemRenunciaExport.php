<?php

namespace App\Exports;

use App\Http\Controllers\TraitHelpers;
use App\Models\InstituicaoRenuncia;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithMapping;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class InstituicaoSemRenunciaExport implements FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithMapping,
    WithEvents,
    WithDrawings,
    WithCustomStartCell
{
    use TraitHelpers;
    
    public $instituicao;
    
    public function __construct($instituicao)
    {
        $this->instituicao  = $instituicao;
    }

    public function headings():array
    {
        return[
            'Instituicao',
            'Sigla',
            'NIF',
            'Tipo Instituição',
            'Contacto',
            'Endereco',
        ];
    }

    public function map($item):array
    {
        return[
            $item->Instituicao,
            $item->sigla,
            $item->nif,
            $item->tipo->designacao,
            $item->contacto,
            $item->Endereco,
        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return InstituicaoRenuncia::with('tipo')->where('tipo_instituicao',  $this->instituicao)->get();
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
        $drawing->setDescription('INSTITUIÇÕES SEM RENUNCIAS');
        $drawing->setPath(public_path('/images/logotipo.png'));
        $drawing->setHeight(90);
        $drawing->setCoordinates('A1');

        return $drawing;
    }


}
