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

class PagamentosPorValidarExport implements FromCollection,
    WithHeadings,
    ShouldAutoSize,
    WithMapping,
    WithEvents,
    WithDrawings,
    WithCustomStartCell
{
    use TraitHelpers;

    public $prestacao, $forma_pagamento, $tipo_servico, $grau_academico, $data_inicio, $data_final;

    public function __construct($request)
    {
        $this->prestacao = $request->p;
        $this->forma_pagamento = $request->f;
        $this->tipo_servico = $request->s;
        $this->grau_academico = $request->g;
        $this->data_inicio = $request->di;
        $this->data_final = $request->df;
    }

    public function headings():array
    {
        return[
            'Matricula',
            'Estudante',
            'Factura',
            'Recibo',
            'Serviço',
            'Data Pagamento',
            'Data Inserção no sistema',
            'Valor Depositado',
            'Prestação',
            'Forma Pagamento',
        ];
    }

    public function map($caixa):array
    {
        return[
            $caixa->codigo_factura,
            $caixa->Nome_Completo,
            $caixa->codigo_factura,
            $caixa->Codigo,
            $caixa->servico,
            $caixa->DataBanco,
            $caixa->Data,
            $caixa->valor_depositado,
            $caixa->prestacao,
            $caixa->forma_pagamento,
        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $ano = AnoLectivo::where('estado', 'Activo')->first();

        $pagamentos = Pagamento::when($this->prestacao, function ($query, $value) {
                $query->where('mes_temp.id', $value);
            })
            ->when($this->forma_pagamento, function ($query, $value) {
                $query->where('tb_pagamentos.forma_pagamento', $value);
            })
            ->when($this->tipo_servico, function ($query, $value) {
                $query->where('factura_items.CodigoProduto', $value);
            })
            ->when($this->grau_academico, function ($query, $value) {
                $query->where('tb_tipo_candidatura.id', ($value));
            })
            ->when($this->data_inicio, function ($query, $value) {
                $query->whereDate('tb_pagamentos.Data', '>=', Carbon::createFromDate($value));
            })
            ->when($this->data_final, function ($query, $value) {
                $query->whereDate('tb_pagamentos.Data', '<=', Carbon::createFromDate($value));
            })
            ->where('tb_pagamentos.estado', 0)
            ->where('tb_pagamentos.AnoLectivo', $ano->Codigo)
            ->join('factura_items', 'tb_pagamentos.codigo_factura', '=', 'factura_items.CodigoFactura')
            ->join('tb_tipo_servicos', 'factura_items.CodigoProduto', '=', 'tb_tipo_servicos.Codigo')
            ->join('mes_temp', 'factura_items.mes_temp_id', '=', 'mes_temp.id')
            ->join('tb_preinscricao', 'tb_pagamentos.Codigo_PreInscricao', '=', 'tb_preinscricao.Codigo')
            ->join('tb_tipo_candidatura', 'tb_preinscricao.codigo_tipo_candidatura', '=', 'tb_tipo_candidatura.id')
            ->select(
                'tb_preinscricao.Nome_Completo',
                'tb_pagamentos.codigo_factura',
                'tb_pagamentos.Codigo',
                'tb_pagamentos.DataBanco',
                'tb_pagamentos.Data',
                'tb_pagamentos.estado',
                'tb_pagamentos.forma_pagamento',
                'mes_temp.designacao AS prestacao',
                'tb_pagamentos.valor_depositado',
                'tb_tipo_candidatura.designacao AS grau_academico',
                'tb_tipo_servicos.Descricao AS servico',
            )
            ->get();
        
        return $pagamentos;
        
    }


    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event){
                $event->sheet->getStyle('A6:I6')->applyFromArray([
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
