@extends('labels.layout')

@section('content')
<div class="card">
    <div class="card-header">
        <h2>Bales List</h2>
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
                        @foreach(['production', 'repacking', 'inward', 'outward', 'cutting', 'transfer'] as $type)
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
                            Total Bales: <span class="badge bg-secondary">{{ $bales->count() }}</span>
                        </th>
                    </tr>
                    <tr>
                        <th>Bale No</th>
                        <th>Product Details</th>
                        <th>Customer</th>
                        <th>QC/Finalist</th>
                        <th>Actions</th>
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
                                'cutting' => 'danger',
                                'transfer' => 'secondary'
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
                            @if(in_array($bale->type, ['repacking', 'transfer']) && $bale->refPackinglist)
                            <hr class="my-1">
                            <small class="d-block {{ $bale->type === 'transfer' ? 'text-secondary' : 'text-success' }}">
                                <strong>From:</strong> {{ $bale->refPackinglist->product->short_code }} -
                                {{ $bale->refPackinglist->product->name }}<br>
                                {{ $bale->refPackinglist->label_name }} ({{ $bale->refPackinglist->customer->name }})
                            </small>
                            @endif
                        </td>
                        <td>{{ $bale->packinglist->customer->name }}
                            @if(in_array($bale->type, ['repacking', 'transfer']) && $bale->refPackinglist)
                            <hr class="my-1">
                            <small class="d-block {{ $bale->type === 'transfer' ? 'text-secondary' : 'text-success' }}">
                                <strong>From:</strong> {{ $bale->refPackinglist->customer->name }}
                            </small>
                            @endif
                        </td>
                        <td>
                            QC: {{ $bale->qcEmployee->name ?? "" }}<br>
                            Finalist: {{ $bale->finalistEmployee->name ?? "" }}

                        </td>
                        <td>
                            <form action="{{ route('bales.destroy', $bale) }}" method="POST"
                                onsubmit="return confirm('Are you sure?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection