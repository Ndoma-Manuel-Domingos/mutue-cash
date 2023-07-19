<?php

namespace App\Exports;

use App\Http\Controllers\TraitHelpers;
use App\Models\AnoLectivo;
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

class FechoGeralExport implements FromCollection, 
    WithHeadings, 
    ShouldAutoSize, 
    WithMapping,
    WithEvents,
    WithDrawings,
    WithCustomStartCell
{
    use TraitHelpers;

    public $o, $s, $g, $a, $ep, $fp, $di, $df;

    public function __construct($o, $s, $g, $a, $ep, $fp, $di, $df)
    {
        $this->o = $o;
        $this->s = $s;
        $this->g = $g;
        $this->a = $a;
        $this->ep = $ep;
        $this->fp = $fp;
        $this->di = $di;
        $this->df = $df;
    }

    public function headings():array
    {
        return[
            'Operador',
            'Data Validação',
            'Valor',
            'Recibo',
            'Forma de Pagamento',
            'Estado',
            'Ano Lectivo',
        ];
    }

    public function map($caixa):array
    {
        return[
            $caixa->nomeUtilizador,
            $caixa->dataValidacaoPagamento,
            $caixa->valorPagamento,
            $caixa->reciboPagamento,
            $caixa->formaPagamento,
            $caixa->estadoPagamento,
            $caixa->AnoLectivoPagamento,
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

        return Pagamento::when($anoSelecionado, function ($query, $value) {
            $query->where('tb_pagamentos.AnoLectivo', '=', $value);
        })
        ->when($this->o, function ($query, $value) {
            $query->where('tb_pagamentos.Utilizador', '=', $value);
        })
        
        ->when($this->di, function ($query, $value) {
            $query->whereDate('tb_pagamentos.Data', '>=', Carbon::createFromDate($value));
        })
        ->when($this->df, function ($query, $value) {
            $query->whereDate('tb_pagamentos.Data', '<=', Carbon::createFromDate($value));
        })
        ->when($this->ep, function ($query, $value) {
            $query->where('tb_pagamentos.estado', '=', $value);
        })
        ->when($this->g, function ($query, $value) {
            $query->where('tb_grau_academico.Codigo', '=', $value);
        })
        ->when($this->fp, function ($query, $value) {
            $query->where('tb_pagamentos.forma_pagamento', '=', $value);
        })
        ->when($this->s, function ($query, $value) {
            $query->where('factura_items.CodigoProduto', '=', $value);
        })
        ->join('tb_ano_lectivo', 'tb_pagamentos.AnoLectivo', '=', 'tb_ano_lectivo.Codigo')
        ->join('mca_tb_utilizador', 'tb_pagamentos.Utilizador', '=', 'mca_tb_utilizador.codigo_importado')
        ->join('tb_preinscricao', 'tb_pagamentos.Codigo_PreInscricao', '=', 'tb_preinscricao.Codigo')
        ->join('tb_grau_academico', 'tb_preinscricao.codigo_grau_academico', '=', 'tb_grau_academico.Codigo')
        ->join('factura_items', 'tb_pagamentos.codigo_factura', '=', 'factura_items.CodigoFactura')
        ->select('mca_tb_utilizador.nome AS nomeUtilizador', 
            'tb_pagamentos.updated_at AS dataValidacaoPagamento', 
            'tb_pagamentos.Codigo AS reciboPagamento', 
            'tb_pagamentos.estado AS estadoPagamento', 
            'tb_pagamentos.valor_depositado AS valorPagamento', 
            'tb_ano_lectivo.Designacao AS AnoLectivoPagamento', 
            'tb_pagamentos.forma_pagamento AS formaPagamento')
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
