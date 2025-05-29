
<form method="GET" class="mb-4">
    <input type="hidden" name="report" value="product-wise-daily">
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">From Date</label>
            <input type="date" name="from_date" class="form-control" 
                   value="{{ request('from_date', now()->format('Y-m-d')) }}" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">To Date</label>
            <input type="date" name="to_date" class="form-control" 
                   value="{{ request('to_date', now()->format('Y-m-d')) }}" required>
        </div>
        <div class="col-md-2">
            <label class="form-label d-block">&nbsp;</label>
            <button type="submit" class="btn btn-primary">Generate Report</button>
        </div>
        <div class="col-md-2">
            <label class="form-label d-block">&nbsp;</label>
            <button type="button" class="btn btn-secondary" onclick="window.print()">Print Report</button>
        </div>
    </div>
</form>

<div id="printArea">
    <div class="report-header">
        <h3 class="">Product-wise Production Report</h3>
        <h4 class="">Period: {{ \Carbon\Carbon::parse($data['from_date'])->format('d/m/Y') }} 
            to {{ \Carbon\Carbon::parse($data['to_date'])->format('d/m/Y') }}</h4>
    </div>

    <!-- Section-wise Summary Table -->
    <div class="mb-4">
        <h4 class="mb-3">Section-wise Production Summary</h4>
        <table class="table table-bordered table-sm">
            <thead>
                <tr>
                    <th>Section</th>
                    <th>Slot 1</th>
                    <th>Slot 2</th>
                    <th>Slot 3</th>
                    <th>Slot 4</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $subcategoryTotals = [];
                    foreach($data['products'] as $product) {
                        $subcategory = $product['subcategory'] ?? 'Uncategorized';
                        
                        if (!isset($subcategoryTotals[$subcategory])) {
                            $subcategoryTotals[$subcategory] = [
                                'subcategory' => $subcategory,
                                'slot1' => 0,
                                'slot2' => 0,
                                'slot3' => 0,
                                'slot4' => 0,
                                'total' => 0
                            ];
                        }
                        
                        $subcategoryTotals[$subcategory]['slot1'] += $product['slot1'];
                        $subcategoryTotals[$subcategory]['slot2'] += $product['slot2'];
                        $subcategoryTotals[$subcategory]['slot3'] += $product['slot3'];
                        $subcategoryTotals[$subcategory]['slot4'] += $product['slot4'];
                        $subcategoryTotals[$subcategory]['total'] += $product['total'];
                    }
                    
                    // Sort by subcategory name
                    ksort($subcategoryTotals);
                @endphp

                @foreach($subcategoryTotals as $totals)
                    <tr>
                        <td>{{ $totals['subcategory'] }}</td>
                        <td class="text-center">{{ $totals['slot1'] }}</td>
                        <td class="text-center">{{ $totals['slot2'] }}</td>
                        <td class="text-center">{{ $totals['slot3'] }}</td>
                        <td class="text-center">{{ $totals['slot4'] }}</td>
                        <td class="fw-bold text-center">{{ $totals['total'] }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="fw-bold">
                    <td class="text-end">Grand Total</td>
                    @foreach($data['slotTotals'] as $total)
                        <td class="text-center">{{ $total }}</td>
                    @endforeach
                    <td class="text-center">{{ $data['grandTotal'] }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Product-wise Table -->
    <div class="mt-4">
        <h4 class="mb-3">Product-wise Production Details</h4>
        <table class="table table-bordered table-sm">
            <thead>
                <tr>
                    <th>Product Code</th>
                    <th>Product Name</th>
                    <th>Section</th>
                    <th>Slot 1</th>
                    <th>Slot 2</th>
                    <th>Slot 3</th>
                    <th>Slot 4</th>
                    <th>Total</th>
                    <th>Customer Distribution</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['products'] as $product)
                    <tr>
                        <td>{{ $product['product_code'] }}</td>
                        <td>{{ $product['product_name'] }}</td>
                        <td>{{ $product['subcategory'] }}</td>
                        <td class="text-center">{{ $product['slot1'] }}</td>
                        <td class="text-center">{{ $product['slot2'] }}</td>
                        <td class="text-center">{{ $product['slot3'] }}</td>
                        <td class="text-center">{{ $product['slot4'] }}</td>
                        <td class="fw-bold text-center">{{ $product['total'] }}</td>
                        <td>
                            @foreach($product['customers'] as $customer => $count)
                                <span class="badge bg-secondary">{{ $customer }}: {{ $count }}</span>
                            @endforeach
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="fw-bold">
                    <td colspan="3" class="text-end">Total</td>
                    @foreach($data['slotTotals'] as $total)
                        <td class="text-center">{{ $total }}</td>
                    @endforeach
                    <td class="text-center">{{ $data['grandTotal'] }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<style>
@media print {
    @page {
        size: landscape;
        margin: 1cm;
    }
    
    body {
        margin: 0;
        padding: 0;
    }
    
    .d-print-none {
        display: none !important;
    }
    
    #printArea {
        width: 100%;
    }
    
    .report-header {
        margin-bottom: 20px;
    }
    
    .report-header h3 {
        font-size: 18px;
        margin: 0;
    }
    
    .report-header h4 {
        font-size: 14px;
        margin: 5px 0 15px;
    }
    
    .table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
    }
    
    .table th {
        background-color: #f8f9fa !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .table td, .table th {
        padding: 4px;
        border: 1px solid #000;
    }
    
    .badge {
        border: 1px solid #666;
        padding: 2px 5px;
        margin: 2px;
        font-size: 9px;
        white-space: nowrap;
    }
    
    .text-center {
        text-align: center;
    }
    
    .text-end {
        text-align: right;
    }
    
    .fw-bold {
        font-weight: bold;
    }
}
</style>