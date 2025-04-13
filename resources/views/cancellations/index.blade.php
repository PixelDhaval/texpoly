@extends('labels.layout')

@section('content')
<div class="card">
    <div class="card-header">
        <h2>Cancel Labels List</h2>
    </div>
    <div class="card-body">
        <form method="GET" class="mb-4">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Date</label>
                    <input type="date" name="date" class="form-control" value="{{ $date }}" 
                           onchange="this.form.submit()">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Bale No</label>
                    <input type="text" name="bale_no" class="form-control" 
                           value="{{ request('bale_no') }}" placeholder="Search bale no...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Product</label>
                    <input type="text" name="product" class="form-control" 
                           value="{{ request('product') }}" placeholder="Search product...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Customer</label>
                    <select name="customer" class="form-select">
                        <option value="">All Customers</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ request('customer') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}
                            </option>
                        @endforeach
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
                    <label class="form-label">Sub Category</label>
                    <select name="subcategory" class="form-select">
                        <option value="">All Sub Categories</option>
                        @foreach($subcategories as $category)
                            <option value="{{ $category->id }}" {{ request('subcategory') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        @foreach(['production', 'repacking', 'inward', 'outward', 'cutting'] as $type)
                            <option value="{{ $type }}" {{ request('type') == $type ? 'selected' : '' }}>
                                {{ ucfirst($type) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label d-block">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr class="bg-light">
                        <th colspan="5" class="text-end pe-4">
                            Total Cancelled: <span class="badge bg-danger">{{ $bales->count() }}</span>
                        </th>
                    </tr>
                    <tr>
                        <th>Bale No / Time</th>
                        <th>Product Details</th>
                        <th>Customer</th>
                        <th>QC/Finalist</th>
                        <th>Ref. Bale</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bales as $bale)
                    <tr>
                        <td>
                        @php
                                $typeColors = [
                                    'production' => 'primary',
                                    'repacking' => 'success',
                                    'inward' => 'info',
                                    'outward' => 'warning',
                                    'cutting' => 'danger'
                                ];
                            @endphp
                            <span class="badge bg-{{ $typeColors[$bale->type] }}">
                                {{ ucfirst($bale->type) }}
                            </span><br>
                            {{ $bale->bale_no }}<br>
                            <small class="text-muted">{{ $bale->created_at->format('h:i A') }}</small>
                        </td>
                        <td>
                            <strong>{{ $bale->packinglist->product->short_code }}</strong> - 
                            {{ $bale->packinglist->product->name }}<br>
                            <small>{{ $bale->packinglist->label_name }}</small><br>
                            <small class="text-muted">
                                {{ $bale->packinglist->product->category->name }} / 
                                {{ $bale->packinglist->product->subcategory->name ?? 'N/A' }}
                            </small>
                        </td>
                        <td>{{ $bale->packinglist->customer->name }}</td>
                        <td>
                            QC: {{ $bale->qcEmployee->name }}<br>
                            Finalist: {{ $bale->finalistEmployee->name }}
                        </td>
                        <td>
                            @if($bale->ref_bale_id)
                                {{ $bale->refBale->bale_no }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
