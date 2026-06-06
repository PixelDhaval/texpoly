<div class="d-print-none mb-3">
    <button type="button" class="btn btn-secondary" onclick="window.print()">Print Report</button>
</div>

<div id="printArea">
    <div class="report-header mb-4">
        <h3 class="mb-1">QC & Finalist Report</h3>
        <p class="mb-0">
            Period: {{ \Carbon\Carbon::parse($data['from_date'])->format('d/m/Y') }}
            to {{ \Carbon\Carbon::parse($data['to_date'])->format('d/m/Y') }}
        </p>
    </div>

    <div class="mb-5">
        <h4 class="mb-3">QC Report</h4>
        <table class="table table-bordered table-sm">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slot 1</th>
                    <th>Slot 2</th>
                    <th>Slot 3</th>
                    <th>Slot 4</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data['qcReport']['rows'] as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td class="text-center">{{ $row['slot1'] }}</td>
                        <td class="text-center">{{ $row['slot2'] }}</td>
                        <td class="text-center">{{ $row['slot3'] }}</td>
                        <td class="text-center">{{ $row['slot4'] }}</td>
                        <td class="fw-bold text-center">{{ $row['total'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">No QC records found for the selected date range</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="fw-bold">
                    <td class="text-end">Total</td>
                    @foreach($data['qcReport']['slotTotals'] as $total)
                        <td class="text-center">{{ $total }}</td>
                    @endforeach
                    <td class="text-center">{{ $data['qcReport']['grandTotal'] }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div>
        <h4 class="mb-3">Finalist Report</h4>
        <table class="table table-bordered table-sm">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slot 1</th>
                    <th>Slot 2</th>
                    <th>Slot 3</th>
                    <th>Slot 4</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data['finalistReport']['rows'] as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td class="text-center">{{ $row['slot1'] }}</td>
                        <td class="text-center">{{ $row['slot2'] }}</td>
                        <td class="text-center">{{ $row['slot3'] }}</td>
                        <td class="text-center">{{ $row['slot4'] }}</td>
                        <td class="fw-bold text-center">{{ $row['total'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">No finalist records found for the selected date range</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="fw-bold">
                    <td class="text-end">Total</td>
                    @foreach($data['finalistReport']['slotTotals'] as $total)
                        <td class="text-center">{{ $total }}</td>
                    @endforeach
                    <td class="text-center">{{ $data['finalistReport']['grandTotal'] }}</td>
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

    body * {
        visibility: hidden;
    }

    #printArea, #printArea * {
        visibility: visible;
    }

    #printArea {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }

    .d-print-none {
        display: none !important;
    }

    .table {
        font-size: 11px;
    }

    .table td,
    .table th {
        padding: 0.25rem 0.35rem;
    }

    .report-header h3 {
        font-size: 18px;
        margin: 0;
    }

    .report-header p,
    .report-header h4 {
        margin: 0;
    }
}
</style>