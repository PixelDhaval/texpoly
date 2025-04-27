@extends('labels.layout')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2>Products Management</h2>
        <a href="{{ route('products.create') }}" class="btn btn-success">Create Product</a>
    </div>
    <div class="card-body">
        <form action="{{ route('products.index') }}" method="GET" class="mb-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search by name or code" 
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-select">
                        <option value="">Select Category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="subcategory" class="form-select">
                        <option value="">Select Section</option>
                        @foreach($subcategories as $subcategory)
                            <option value="{{ $subcategory->id }}" {{ request('subcategory') == $subcategory->id ? 'selected' : '' }}>
                                {{ $subcategory->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Grade</th>
                        <th>Category</th>
                        <th>Section</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                    <tr>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->grade }}</td>
                        <td>{{ $product->category->name }}</td>
                        <td>{{ $product->subcategory?->name }}</td>
                        <td>{{ $product->price }}</td>
                        <td>{{ $product->quantity }}</td>
                        <td>
                            <a href="{{ route('products.edit', $product->id) }}" class="btn btn-primary btn-sm">Edit</a>
                            <button type="button" 
                                    class="btn btn-danger btn-sm delete-product" 
                                    data-id="{{ $product->id }}" 
                                    data-name="{{ $product->name }}">
                                Delete
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="mt-4">
            {{ $products->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

<div class="modal fade" id="mergeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Do you want to transfer this product's stock to another product?</p>
                <div class="mb-3">
                    <select id="mergeProductSelect" class="form-select">
                        <option value="">Select Product to Transfer Stock</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="deleteWithoutMerge">Delete Without Transfer</button>
                <button type="button" class="btn btn-primary" id="confirmMerge">Transfer and Delete</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container {
        width: 100% !important;
    }
    /* Fix for Select2 inside Bootstrap modal */
    .select2-dropdown {
        z-index: 1056;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mergeModal = new bootstrap.Modal(document.getElementById('mergeModal'));
    let currentProductId = null;
    
    // Initialize Select2
    $('#mergeProductSelect').select2({
        dropdownParent: $('#mergeModal'),
        placeholder: 'Select Product to Transfer Stock',
        width: '100%'
    });
    
    // Handle delete button click
    document.querySelectorAll('.delete-product').forEach(button => {
        button.addEventListener('click', function() {
            currentProductId = this.dataset.id;
            const currentProductName = this.dataset.name;
            
            // Clear and reload the product select options
            const mergeProductSelect = $('#mergeProductSelect');
            mergeProductSelect.empty().append('<option value="">Select Product to Transfer Stock</option>');
            
            // Add all products except the current one
            @foreach($allProducts as $product)
            if ('{{ $product->id }}' !== currentProductId) {
                const option = new Option(
                    '{{ $product->name }} ({{ $product->short_code }})', 
                    '{{ $product->id }}'
                );
                mergeProductSelect.append(option);
            }
            @endforeach
            
            // Update modal title to include product name
            document.querySelector('#mergeModal .modal-title').textContent = 
                `Delete Product: ${currentProductName}`;
            
            // Trigger Select2 to update
            mergeProductSelect.trigger('change');
            
            mergeModal.show();
        });
    });

    // Handle delete without merge
    document.getElementById('deleteWithoutMerge').addEventListener('click', function() {
        deleteProduct(currentProductId);
    });

    // Handle merge and delete
    document.getElementById('confirmMerge').addEventListener('click', function() {
        const targetProductId = document.getElementById('mergeProductSelect').value;
        if (!targetProductId) {
            alert('Please select a product to transfer stock to');
            return;
        }
        mergeAndDeleteProduct(currentProductId, targetProductId);
    });

    function deleteProduct(productId) {
        fetch(`/products/${productId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error deleting product');
            }
        });
    }

    function mergeAndDeleteProduct(sourceProductId, targetProductId) {
        fetch(`/products/${sourceProductId}/merge`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ target_product_id: targetProductId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error merging products');
            }
        });
    }
});
</script>
@endpush
