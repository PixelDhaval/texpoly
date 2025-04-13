@extends('labels.layout')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2>Packing List - {{ $customer->name }}</h2>
        <div>
            <button type="button" class="btn btn-primary" onclick="document.getElementById('packinglistForm').submit()">Save All Changes</button>
            <a href="{{ route('packinglists.index') }}" class="btn btn-secondary">Back to List</a>
        </div>
    </div>
    <div class="card-body">
        <!-- Filter Section -->
        <div class="row mb-4">
            <div class="col-md-8">
                <form id="filterForm" class="row g-3">
                    <div class="col-md-5">
                        <input type="text" class="form-control" name="search"
                            placeholder="Search by product name/code" value="{{ request('search') }}">
                    </div>
                    <div class="col-md-5">
                        <input type="text" class="form-control" name="label_name"
                            placeholder="Search by label name" value="{{ request('label_name') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table Section -->
        <form id="packinglistForm" action="{{ route('packinglists.bulk-update') }}" method="POST">
            @csrf
            <div class="table-responsive">
                <table class="table table-bordered" id="packinglistTable">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th width="20%">Product</th>
                            <th width="20%">Label Name</th>
                            <th>Customer Qty</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>Weight</th>
                            <th>Price</th>
                            <th>Bold</th>
                            <th>Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($packinglists as $item)
                        <tr data-id="{{ $item->id }}">
                            <td>{{ $item->product->short_code}}</td>
                            <td>{{ $item->product->name}}</td>
                            <td class="editable" >
                                <input type="hidden" name="packinglists[{{ $loop->index }}][id]" value="{{ $item->id}}">
                                <input type="text" class="form-control form-control-sm" name="packinglists[{{ $loop->index }}][label_name]"
                                    value="{{ $item->label_name}}">
                            </td>
                            <td class="editable" >
                                <input type="number" class="form-control form-control-sm" name="packinglists[{{ $loop->index }}][customer_qty]"
                                    value="{{ $item->customer_qty}}">
                            </td>
                            <td class="editable">
                                <input type="number" class="form-control form-control-sm" name="packinglists[{{ $loop->index }}][quantity]"
                                    value="{{ $item->quantity}}">
                            </td>
                            <td class="editable">
                                <select name="packinglists[{{ $loop->index }}][unit]" class="form-select form-select-sm" data-id="unit">
                                    <option value="">Select Unit</option>
                                    @foreach(['KGS', 'LBS', 'PCS'] as $unit)
                                    <option value="{{ $unit }}" {{ $item->unit == $unit ? 'selected' : '' }}>
                                        {{ $unit }}
                                    </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="editable">
                                <input type="number" class="form-control form-control-sm" name="packinglists[{{ $loop->index }}][weight]"
                                    value="{{ $item->weight}}">
                            </td>
                            <td class="editable">
                                <input type="number" class="form-control form-control-sm" name="packinglists[{{ $loop->index }}][price]"
                                    value="{{ $item->price}}">
                            </td>
                            <td class="editable">
                                <input type="hidden" name="packinglists[{{ $loop->index }}][is_bold]" value="0">
                                <input type="checkbox" class="form-check-input" name="packinglists[{{ $loop->index }}][is_bold]"
                                    {{ $item->is_bold ? 'checked' : '' }}>
                            </td>
                            <td class="editable">
                                <input type="number" class="form-control form-control-sm" name="packinglists[{{ $loop->index }}][stock]"
                                    value="{{ $item->stock}}">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const token = document.querySelector('meta[name="csrf-token"]').content;

    // Handle form submission
    

    // Handle filter form submission
    const filterForm = document.getElementById('filterForm');
    filterForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(filterForm);
        const params = new URLSearchParams(formData);
        window.location.href = `${window.location.pathname}?${params.toString()}`;
    });

    document.getElementById('saveAll').addEventListener('click',() => {
        document.getElementById('packinglistForm').submit()
    })
});
</script>
@endpush
@endsection