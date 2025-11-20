<!-- filepath: e:\herd\texpoly\resources\views\products\customer-balance.blade.php -->
@extends('labels.layout')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2>Party-wise Balance Report</h2>
        @if(isset($balances) && $balances->count() > 0)
        <a href="{{ route('products.customer-balance.export', request()->query()) }}" 
           class="btn btn-success">
            <i class="bi bi-file-earmark-excel"></i> Export to Excel
        </a>
        @endif
    </div>
    <div class="card-body">
        <form id="balanceForm" method="GET" class="mb-4">
            <div class="row g-3">
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
                    <label class="form-label">Search Party</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search by party name"
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
                <div class="col-md-2">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Section</label>
                    <select name="subcategory" class="form-select">
                        <option value="">All Sections</option>
                        @foreach($subcategories as $subcategory)
                            <option value="{{ $subcategory->id }}" {{ request('subcategory') == $subcategory->id ? 'selected' : '' }}>
                                {{ $subcategory->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="bale" {{ request('type') == 'bale' ? 'selected' : '' }}>Bale</option>
                        <option value="jumbo" {{ request('type') == 'jumbo' ? 'selected' : '' }}>Jumbo</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="{{ route('products.customer-balance') }}" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>

        @if(isset($balances) && $balances->count() > 0)
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Party Name</th>
                        <th>Opening Balance</th>
                        <th>Production</th>
                        <th>Repacking In</th>
                        <th>Repacking Out</th>
                        <th>Transfer In</th>
                        <th>Transfer Out</th>
                        <th>Inward</th>
                        <th>Outward</th>
                        <th>Cutting</th>
                        <th>Dispatch</th>
                        <th>Closing Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $grandOpeningBalance = 0;
                        $grandClosingBalance = 0;
                        $grandProduction = 0;
                        $grandRepackingIn = 0;
                        $grandRepackingOut = 0;
                        $grandTransferIn = 0;
                        $grandTransferOut = 0;
                        $grandInward = 0;
                        $grandOutward = 0;
                        $grandCutting = 0;
                        $grandDispatch = 0;
                    @endphp
                    @foreach($balances as $customer)
                    @php
                        $grandOpeningBalance += $customer->opening_balance;
                        $grandClosingBalance += $customer->closing_balance;
                        $grandProduction += $customer->production;
                        $grandRepackingIn += $customer->repacking_in;
                        $grandRepackingOut += $customer->repacking_out;
                        $grandTransferIn += $customer->transfer_in;
                        $grandTransferOut += $customer->transfer_out;
                        $grandInward += $customer->inward;
                        $grandOutward += $customer->outward;
                        $grandCutting += $customer->cutting;
                        $grandDispatch += $customer->dispatch;
                    @endphp
                    <tr class="customer-row" style="cursor: pointer"
                        data-customer-id="{{ $customer->id }}"
                        data-from="{{ request('from_date') }}"
                        data-to="{{ request('to_date') }}">
                        <td>{{ $customer->name }}</td>
                        <td>{{ $customer->opening_balance }}</td>
                        <td>{{ $customer->production }}</td>
                        <td>{{ $customer->repacking_in }}</td>
                        <td>{{ $customer->repacking_out }}</td>
                        <td>{{ $customer->transfer_in }}</td>
                        <td>{{ $customer->transfer_out }}</td>
                        <td>{{ $customer->inward }}</td>
                        <td>{{ $customer->outward }}</td>
                        <td>{{ $customer->cutting }}</td>
                        <td>{{ $customer->dispatch }}</td>
                        <td>{{ $customer->closing_balance }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="table-dark fw-bold">
                        <td>Grand Total</td>
                        <td>{{ $grandOpeningBalance }}</td>
                        <td>{{ $grandProduction }}</td>
                        <td>{{ $grandRepackingIn }}</td>
                        <td>{{ $grandRepackingOut }}</td>
                        <td>{{ $grandTransferIn }}</td>
                        <td>{{ $grandTransferOut }}</td>
                        <td>{{ $grandInward }}</td>
                        <td>{{ $grandOutward }}</td>
                        <td>{{ $grandCutting }}</td>
                        <td>{{ $grandDispatch }}</td>
                        <td>{{ $grandClosingBalance }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="mt-4">
            {{ $balances->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.customer-row').forEach(row => {
        row.addEventListener('click', function() {
            const url = new URL("{{ route('products.history') }}");
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