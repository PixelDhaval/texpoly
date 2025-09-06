<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrderExport implements FromArray, WithHeadings, WithTitle, WithStyles
{
    protected $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function array(): array
    {
        $data = [];

        foreach ($this->order->orderlists as $item) {
            if ($item->dispatch_qty && $item->dispatch_qty > 0) {
                $data[] = [
                    $item->packinglist->product->short_code,
                    $item->packinglist->product->name,
                    $item->packinglist->label_name,
                    $item->packinglist->customer_qty,
                    $item->packinglist->stock,
                    $item->dispatch_qty,
                ];
            }
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'Code',
            'Product',
            'Label Name',
            'Customer Qty',
            'Stock',
            'Dispatch Qty',
        ];
    }

    public function title(): string
    {
        return 'Order ' . $this->order->order_no;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
