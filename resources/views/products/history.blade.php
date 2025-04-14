@extends('labels.layout')

@section('content')
<div class="card">
    <div class="card-header">
        <h2>Product History</h2>
    </div>
    <div class="card-body">
        <form id="historyForm" method="GET" class="mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Customer</label>
                    <select name="customer_id" class="form-select" required>
                        <option value="">Select Customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" 
                                {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="date" name="from_date" class="form-control" required 
                           value="{{ request('from_date', now()->startOfMonth()->format('Y-m-d')) }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to_date" class="form-control" required
                           value="{{ request('to_date', now()->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search by name or code"
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Per Page</label>
                    <select name="per_page" class="form-select">
                        <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                        <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="{{ route('products.history') }}" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>

        @if(isset($products) && $products->count() > 0)
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Code</th>
                        <th>Opening Balance</th>
                        <th>Production</th>
                        <th>Repacking In</th>
                        <th>Repacking Out</th>
                        <th>Inward</th>
                        <th>Outward</th>
                        <th>Cutting</th>
                        <th>Closing Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                    <tr class="product-row" style="cursor: pointer" 
                        data-product-id="{{ $product->id }}"
                        data-customer-id="{{ request('customer_id') }}"
                        data-from="{{ request('from_date') }}"
                        data-to="{{ request('to_date') }}">
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->short_code }}</td>
                        <td>{{ $product->opening_balance }}</td>
                        <td>{{ $product->production_count }}</td>
                        <td>{{ $product->repacking_in }}</td>
                        <td>{{ $product->repacking_out }}</td>
                        <td>{{ $product->inward }}</td>
                        <td>{{ $product->outward }}</td>
                        <td>{{ $product->cutting }}</td>
                        <td>{{ $product->closing_balance }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $products->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Product Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detailContent"></div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update click handler for product details
    document.querySelectorAll('.product-row').forEach(row => {
        row.addEventListener('click', function() {
            const url = new URL("{{ route('products.history-details') }}");
            url.searchParams.append('product_id', this.dataset.productId);
            url.searchParams.append('customer_id', this.dataset.customerId);
            url.searchParams.append('from_date', this.dataset.from);
            url.searchParams.append('to_date', this.dataset.to);
            
            window.location.href = url.toString();
        });
    });
});
</script>
@endpush
@endsection