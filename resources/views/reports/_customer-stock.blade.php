<form method="GET" class="mb-4">
    <input type="hidden" name="report" value="customer-stock">
    <input type="hidden" name="customer" value="{{ request('customer') }}">
    
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Search Product/Label</label>
            <input type="text" name="search" class="form-control" 
                   value="{{ request('search') }}" placeholder="Search...">
        </div>
        <div class="col-md-3">
            <label class="form-label">Category</label>
            <select name="category" class="form-select">
                <option value="">All Categories</option>
                @foreach($data['categories'] as $category)
                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Section</label>
            <select name="subcategory" class="form-select">
                <option value="">All Sections</option>
                @foreach($data['subcategories'] as $subcategory)
                    <option value="{{ $subcategory->id }}" {{ request('subcategory') == $subcategory->id ? 'selected' : '' }}>
                        {{ $subcategory->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label d-block">&nbsp;</label>
            <button type="submit" class="btn btn-primary">Filter</button>
            @if($data['packinglists']->isNotEmpty())
                <a href="{{ request()->fullUrlWithQuery(['download' => 'excel']) }}" 
                   class="btn btn-success">Download Excel</a>
            @endif
        </div>
    </div>
</form>

<div class="table-responsive" id="printArea">
    @if($data['packinglists']->isNotEmpty())
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Stock Data - {{ $data['packinglists']->first()->customer->name }}</h3>
            <small class="text-muted">Generated: {{ now()->format('d/m/Y h:i A') }}</small>
        </div>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Product Code</th>
                    <th>Product Details</th>
                    <th>Label Name</th>
                    <th>Stock</th>
                    <th>Customer Qty</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['packinglists'] as $item)
                <tr>
                    <td>{{ $item->product->short_code }}</td>
                    <td>
                        <strong>{{ $item->product->name }}</strong><br>
                        <small class="text-muted">
                            {{ $item->product->category->name }} /
                            {{ $item->product->subcategory->name ?? 'N/A' }}
                        </small>
                    </td>
                    <td>{{ $item->label_name }}</td>
                    <td>{{ $item->stock }}</td>
                    <td>{{ $item->customer_qty }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="fw-bold">
                    <td colspan="3">Total</td>
                    <td>{{ $data['packinglists']->sum('stock') }}</td>
                    <td>{{ $data['packinglists']->sum('customer_qty') }}</td>
                </tr>
            </tfoot>
        </table>
    @else
        <div class="text-center text-muted">
            Please select a customer to view stock data
        </div>
    @endif
</div>

<style>
@media print {
    @page {
        margin: 1cm;
        size: portrait;
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

    /* Compact table styles */
    .table {
        font-size: 11px !important;
        margin-bottom: 0.5rem;
    }
    
    .table td, .table th {
        padding: 0.15rem 0.25rem !important;
        line-height: 1.2;
    }
    
    /* Reduce spacing */
    h3 {
        font-size: 14px;
        margin: 0.5rem 0;
    }
    
    small {
        font-size: 9px;
        line-height: 1;
    }
    
    strong {
        font-weight: 600;
    }
    
    /* Ensure borders print properly */
    .table-bordered td, 
    .table-bordered th {
        border: 1px solid #dee2e6 !important;
    }
    
    .text-muted {
        color: #666 !important;
    }
}
</style>
