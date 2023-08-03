<?php

namespace App\Exports;

use App\Http\Controllers\TraitHelpers;
use App\Models\Deposito;
use Carbon\Carbon;
use Maatwebsite\Excel\Cell;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithMapping;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell as CellCell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DepositosExport extends DefaultValueBinder implements FromCollection, WithMapping, WithTitle, WithHeadings, WithDrawings, WithStyles, WithCustomStartCell, WithCustomValueBinder, ShouldAutoSize
{
    use TraitHelpers, Exportable;

    public $data_inicio;
    public $data_final;
    public $operador;
    public $titulo;
    

    public function __construct($request)
    {
        $this->data_inicio = $request->data_inicio;
        $this->data_final = $request->data_final;
        $this->titulo = "LISTA DE DEPOSITOS";
    }

    public function headings():array
    {
        return[
            'Nº Deposito',
            'Matricula',
            'Estudante',
            'Saldo depositado',
            'Saldo apos Movimento',
            'Forma Pagamento',
            'Operador',
            'Ano Lectivo',
            'Data',
        ];
    }

    public function map($item):array
    {
        return[
            $item->codigo,
            $item->codigo_matricula_id,
            $item->matricula->admissao->preinscricao->Nome_Completo,
            number_format($item->valor_depositar ?? 0, 2, ',', '.'),
            number_format($item->saldo_apos_movimento ?? 0, 2, ',', '.'),
            $item->forma_pagamento->descricao,
            $item->user->nome,
            $item->ano_lectivo->Designacao,
            date("Y-m-d", strtotime($item->created_at)),
        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
    
        $data['items'] = Deposito::when($this->data_inicio, function($query, $value){
            $query->where('created_at', '>=' ,Carbon::parse($value) );
        })
        ->when($this->data_final, function($query, $value){
            $query->where('created_at', '<=' ,Carbon::parse($value));
        })
        ->when($this->operador, function($query, $value){
            $query->where('created_by', $value);
        })
        ->with(['user', 'forma_pagamento', 'ano_lectivo', 'matricula.admissao.preinscricao'])
        ->get();
        
        return $data['items'];
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

    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('LISTA DE DEPOSITOS');
        $drawing->setPath(public_path('/images/logotipo.png'));
        $drawing->setHeight(90);
        $drawing->setCoordinates('A1');

        return $drawing;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return $this->titulo;
    }

    public function startCell(): string
    {
        return 'A10';
    }
    public function styles(Worksheet $sheet)
    {
        //$sheet->getStyle('A7:D7')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('000000');
        //$sheet->mergeCells('A1:D6');
        //Adicionar Título na Célula C5.
        //$sheet->setCellValue('G6', 'UNIVERSIDADE METODISTA DE ANGOLA');
        $sheet->setCellValue('D7', strtoupper($this->titulo));
        // $sheet->setCellValue('M6', 'Semestre');
        // $sheet->setCellValue('O6', '2º');
        $sheet->setCellValue('M7', 'Mês');
        $sheet->setCellValue('O7', date('m'));
        $sheet->setCellValue('M8', 'Ano');
        $sheet->setCellValue('O8', date('Y'));
        //$sheet->styles('')//setBorder('A1', 'solid');
        // $sheet->setBorder('A1:F10', 'thin');
        //Recuperar todas cordenadas envolvidadas
        $coordenadas = $sheet->getCoordinates();

        return [
            // Style the first row as bold text.
            10    => [
                'font' => ['bold' => false, 'color' => ['rgb' => 'FCFCFD']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => '2b5876']]

            ],

            'M6:O8'    => [
                'font' => ['bold' => false, 'color' => ['rgb' => 'FCFCFD']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => '2b5876']]

            ],

            // Styling a specific cell by coordinate.
            'D7' => ['font' => ['bold' => true, 'color' => ['rgb' => '00008B']]],
            'F7' => ['font' => ['bold' => true, 'color' => ['rgb' => '00008B']]],
            'G6' => ['font' => ['bold' => true, 'color' => ['rgb' => '00008B']]],

            'A11:' . end($coordenadas) => ['borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ]],


            // Styling an entire column.
            //'C'  => ['font' => ['size' => 16]],
        ];
        //$sheet->getStyle('A7')->getFont()->setBold(true);
    }

    public function bindValue(CellCell $cell, $value)
    {

        /* if($cell->getCoordinate()=='A7'){
          
         dd($cell->getCoordinate());
        }
        
         dd('Não'); */

        if (is_string($value)) {
            $cell->setValueExplicit(strval($value), DataType::TYPE_STRING);
            return true;
        }

        // else return default behavior
        return parent::bindValue($cell, strval($value));
    }

}
