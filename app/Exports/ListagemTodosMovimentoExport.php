<?php

namespace App\Exports;

use App\Http\Controllers\TraitHelpers;
use App\Models\Caixa;
use App\Models\Deposito;
use App\Models\MovimentoCaixa;
use App\Models\User;
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

class ListagemTodosMovimentoExport extends DefaultValueBinder implements FromCollection, WithMapping, WithTitle, WithHeadings, WithDrawings, WithStyles, WithCustomStartCell, WithCustomValueBinder, ShouldAutoSize
{
    use TraitHelpers, Exportable;

    public $data_inicio;
    public $data_final;
    public $operador_id;
    public $caixa_id;
    

    public function __construct($request)
    {
        $this->data_inicio = $request->data_inicio;
        $this->data_final = $request->data_final;
        $this->operador_id = $request->operador_id;
        $this->caixa_id = $request->caixa_id;
    }

    public function headings():array
    {
        return[
            'Nº',
            'Operador',
            'Caixa',
            'Estado Caixa',
            'Validação',
            'Total Abertura',
            'Total Pagamentos',
            'Total Depositos',
            'Total Fecho',
            'Data',
        ];
    }

    public function map($item):array
    {
        return[
            $item->codigo,
            $item->operador->nome?? "",
            $item->caixa->nome ?? '',
            $item->status ?? '',
            $item->status_admin ?? '',
            number_format($item->valor_abertura ?? 0, 2, ',', '.'),
            number_format($item->valor_arrecadado_pagamento ?? 0, 2, ',', '.'),
            number_format($item->valor_arrecadado_depositos ?? 0, 2, ',', '.'),
            number_format($item->valor_arrecadado_total ?? 0, 2, ',', '.'),
            $item->created_at
        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $data['items'] = MovimentoCaixa::when($this->data_inicio, function($query, $value){
            $query->where('created_at', '>=' ,Carbon::parse($value) );
        })
        ->when($this->data_final, function($query, $value){
            $query->where('created_at', '<=' ,Carbon::parse($value));
        })
        ->when($this->operador_id, function($query, $value){
            $query->where('operador_id', $value);
        })
        ->when($this->caixa_id, function($query, $value){
            $query->where('caixa_id', $value);
        })
        ->with(['operador', 'caixa'])
        ->orderBy('codigo', 'desc')
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
        return "LISAGEM DE TODOS OS MOVIMENTOS";
    }

    public function startCell(): string
    {
        return 'A10';
    }
    public function styles(Worksheet $sheet)
    {
        $operador = User::where('codigo_importado', $this->operador_id)->first();
    
        $sheet->setCellValue('D7', strtoupper("LISAGEM DE TODOS OS MOVIMENTOS"));
        $sheet->setCellValue('H2', 'DATA INICIO');
        $sheet->setCellValue('J2', $this->data_inicio);
        $sheet->setCellValue('H3', 'DATA FINAL');
        $sheet->setCellValue('J3', $this->data_final);
        $sheet->setCellValue('H4', 'OPERADOR');
        $sheet->setCellValue('J4',  $operador->nome ?? "TODOS" );
        $sheet->setCellValue('H5', 'CAIXA');
        $sheet->setCellValue('J5',  Caixa::find($this->caixa_id)->nome ?? "TODOS" );
        $coordenadas = $sheet->getCoordinates();

        return [
            // Style the first row as bold text.
            10    => [
                'font' => ['bold' => false, 'color' => ['rgb' => 'FCFCFD']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => '2b5876']]

            ],

            'H2:J5'    => [
                'font' => ['bold' => false, 'color' => ['rgb' => 'FCFCFD']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => '2b5876']]

            ],

            // Styling a specific cell by coordinate.
            'D7' => ['font' => ['bold' => true, 'color' => ['rgb' => '00008B']]],
            'I2' => ['font' => ['bold' => true, 'color' => ['rgb' => '00008B']]],
            'I3' => ['font' => ['bold' => true, 'color' => ['rgb' => '00008B']]],

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
