<div class="d-print-none mb-3">
    <form method="GET" class="row row-cols-lg-auto g-2 align-items-center mb-2">
        <input type="hidden" name="report" value="daily-production">
        <div>
            <input type="date" name="date" class="form-control form-control-sm"
                value="{{ request('date', now()->format('Y-m-d')) }}">
        </div>
        <div>
            <input type="text" name="search" class="form-control form-control-sm"
                value="{{ request('search') }}" placeholder="Product name/code">
        </div>
        <div>
            <select name="category" class="form-select form-select-sm">
                <option value="">All Categories</option>
                @foreach($data['categories'] as $category)
                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <select name="subcategory" class="form-select form-select-sm">
                <option value="">All Sections</option>
                @foreach($data['subcategories'] as $subcategory)
                    <option value="{{ $subcategory->id }}" {{ request('subcategory') == $subcategory->id ? 'selected' : '' }}>
                        {{ $subcategory->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <select name="type" class="form-select form-select-sm">
                <option value="">All Types</option>
                @foreach(['bale', 'jumbo'] as $type)
                    <option value="{{ $type }}" {{ request('type') == $type ? 'selected' : '' }}>
                        {{ ucfirst($type) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-sm btn-primary">Filter</button>
            <a href="{{ route('reports.index', ['report' => 'daily-production']) }}" class="btn btn-sm btn-secondary">Reset</a>
        </div>
    </form>
    <button type="button" class="btn btn-secondary" onclick="window.print()">Print Report</button>
</div>

<div id="printArea">
    <div class="report-container">
        <div class="page-break-after">
            <!-- Party-wise Total Production -->
            <h3 class="mb-3">Party-wise Total Production - {{ \Carbon\Carbon::parse($data['date'])->format('d/m/Y') }}</h3>
            <table class="table table-bordered mb-5">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Total Production</th>
                    </tr>
                </thead>
                <tbody>
                    @php $grandTotal = 0; @endphp
                    @foreach($data['customerTotals'] as $customer => $total)
                        <tr>
                            <td>{{ $customer }}</td>
                            <td>{{ $total }}</td>
                        </tr>
                        @php $grandTotal += $total; @endphp
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td>Total</td>
                        <td>{{ $grandTotal }}</td>
                    </tr>
                </tfoot>
            </table>

            <!-- Repacking Details -->
            <h3 class="mb-3">Repacking Details</h3>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th colspan="2">From</th>
                        <th colspan="2">To</th>
                        <th rowspan="2">Qty</th>
                    </tr>
                    <tr>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Customer</th>
                        <th>Product</th>
                    </tr>
                </thead>
                <tbody>
                    @php $repackTotal = 0; @endphp
                    @foreach($data['repackingData'] as $bale)
                        <tr>
                            <td>{{ $bale->refPackinglist->customer->name }}</td>
                            <td>{{ $bale->refPackinglist->product->name }}</td>
                            <td>{{ $bale->packinglist->customer->name }}</td>
                            <td>{{ $bale->packinglist->product->name }}</td>
                            <td>1</td>
                        </tr>
                        @php $repackTotal++; @endphp
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="4">Total</td>
                        <td>{{ $repackTotal }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Customer-wise Production Details -->
        @foreach($data['customerProducts'] as $customer => $products)
            <h3 class="mb-3 mt-4">{{ $customer }}</h3>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Product Code</th>
                        <th>Label Name</th>
                        <th>Slot 1</th>
                        <th>Slot 2</th>
                        <th>Slot 3</th>
                        <th>Slot 4</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                        $slotTotals = [0, 0, 0, 0];
                        $customerTotal = 0;
                    @endphp
                    @foreach($products as $product)
                        <tr>
                            <td>{{ $product['product_code'] }}</td>
                            <td>{{ $product['product_name'] }} ({{$product['label_name'] }})</td>
                            @for($i = 1; $i <= 4; $i++)
                                @php $slotTotals[$i-1] += $product['slot'.$i]; @endphp
                                <td>{{ $product['slot'.$i] }}</td>
                            @endfor
                            <td>{{ $product['total'] }}</td>
                        </tr>
                        @php $customerTotal += $product['total']; @endphp
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="2">Total</td>
                        @foreach($slotTotals as $total)
                            <td>{{ $total }}</td>
                        @endforeach
                        <td>{{ $customerTotal }}</td>
                    </tr>
                </tfoot>
            </table>
        @endforeach
    </div>
</div>

<style>
@media print {
    @page { margin: 0.5cm; }
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
    .page-break-after {
        page-break-after: always;
    }
    .report-container {
        font-size: 12px;
    }
    .table td, .table th {
        padding: 0.25rem;
    }
    .d-print-none {
        display: none !important;
    }
}
</style>
