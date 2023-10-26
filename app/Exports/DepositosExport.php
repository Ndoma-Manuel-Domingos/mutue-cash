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
    public $total_registros;
    

    public function __construct($request, $titulo = "LISTA DE DEPOSITOS", $total_registros = 0)
    {
        $this->data_inicio = $request->data_inicio;
        $this->data_final = $request->data_final;
        $this->operador = $request->operador;
        $this->titulo = $titulo;
        $this->total_registros = $total_registros;
    }

    public function headings():array
    {
        return[
            'Nº Deposito',
            'Matricula',
            'Estudante',
            'Valor depositado',
            'Reserva após Depósito',
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
            $item->forma_pagamento->descricao ?? "CASH",
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
            $query->whereDate('data_movimento', '>=' ,Carbon::parse($value) );
        })
        ->when($this->data_final, function($query, $value){
            $query->whereDate('data_movimento', '<=' ,Carbon::parse($value));
        })
        ->when($this->operador, function($query, $value){
            $query->where('created_by', $value);
        })
        ->with(['user', 'forma_pagamento', 'ano_lectivo', 'matricula.admissao.preinscricao'])
        ->get();
        
        $this->total_registros = count($data['items']);
        
        
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
        $sheet->setCellValue('C8', strtoupper($this->titulo));
        // $sheet->setCellValue('M6', 'Semestre');
        // $sheet->setCellValue('O6', '2º');
        $sheet->setCellValue('F3', 'DATA INICIO');
        $sheet->setCellValue('G3', $this->data_inicio);
        $sheet->setCellValue('F4', 'DATA FINAL');
        $sheet->setCellValue('G4', $this->data_final);
        $sheet->setCellValue('F5', "TOTAL DE REGISTROS");
        $sheet->setCellValue('G5', $this->total_registros);
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

            'F3:G5'    => [
                'font' => ['bold' => false, 'color' => ['rgb' => 'FCFCFD']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => '2b5876']]

            ],

            // Styling a specific cell by coordinate.
            'C8' => ['font' => ['bold' => true, 'color' => ['rgb' => '00008B']]],
            'F3' => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']]],
            'F4' => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']]],
            'G3' => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']]],
            'G4' => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']]],
            'F5' => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']]],
            'G5' => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']]],

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

        if (is_string($value)) {
            $cell->setValueExplicit(strval($value), DataType::TYPE_STRING);
            return true;
        }

        // else return default behavior
        return parent::bindValue($cell, strval($value));
    }

}
