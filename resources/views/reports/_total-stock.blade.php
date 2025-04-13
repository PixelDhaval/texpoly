<div class="d-flex justify-content-between align-items-center mb-3">
    
    <a href="{{ request()->fullUrlWithQuery(['download' => 'excel']) }}" 
       class="btn btn-success btn-sm">
        Download Excel
    </a>
</div>

<div class="table-responsive" id="printArea">
    <h3 class="mb-3">Total Stock Report - {{ now()->format('d/m/Y h:i A') }}</h3>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th rowspan="2">Product Code</th>
                <th rowspan="2">Product Details</th>
                <th rowspan="">Total</th>
                @foreach($data['customers'] as $customer)
                <th class="text-center">{{ $customer->name }}</th>
                @endforeach
            </tr>
            <tr>
                <td class="text-center fw-bold">{{ $data['grandTotal'] }}</td>

                @foreach($data['customers'] as $customer)
                <td class="text-center fw-bold">{{ $data['customerTotals'][$customer->id] }}</td>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($data['rows'] as $row)
            <tr>
                <td>{{ $row['product']['code'] }}</td>
                <td>
                    <strong>{{ $row['product']['name'] }}</strong><br>
                    <small class="text-muted">{{ $row['product']['category'] }}</small>
                </td>
                <td class="text-center fw-bold">{{ $row['total'] }}</td>
                @foreach($data['customers'] as $customer)
                <td class="text-center">{{ $row['stocks'][$customer->id] }}</td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="fw-bold">
                <td colspan="2">Total</td>
                <td class="text-center">{{ $data['grandTotal'] }}</td>
                @foreach($data['customers'] as $customer)
                <td class="text-center">{{ $data['customerTotals'][$customer->id] }}</td>
                @endforeach

            </tr>
        </tfoot>
    </table>
</div>