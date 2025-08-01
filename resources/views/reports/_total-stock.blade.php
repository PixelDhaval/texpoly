<div class="d-flex justify-content-between align-items-center mb-3">
    <form method="GET" class="row row-cols-lg-auto g-2 align-items-center">
        
    <input type="hidden" name="report" value="total-stock">
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
            <a href="{{ route('reports.index', ['report' => 'total-stock']) }}" class="btn btn-sm btn-secondary">Reset</a>
        </div>
    </form>
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