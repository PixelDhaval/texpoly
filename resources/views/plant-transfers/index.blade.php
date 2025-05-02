@extends('labels.layout')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container { width: 100% !important; }
</style>
@endpush

@section('content')
<div class="card mb-4">
    <div class="card-header">
        <h2>Plant Transfer</h2>
    </div>
    <div class="card-body">
        <form id="transferForm">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Customer</label>
                    <select id="customer_select" class="form-select" required>
                        <option value="">Select Customer</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Product</label>
                    <select id="packinglist_select" class="form-select" required disabled></select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Type</label>
                    <select id="type_select" class="form-select" required>
                        <option value="">Select Type</option>
                        <option value="inward">Inward</option>
                        <option value="outward">Outward</option>
                        <option value="cutting">Cutting</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Plant</label>
                    <select id="plant_select" class="form-select" required>
                        <option value="">Select Plant</option>
                        @foreach($plants as $plant)
                            <option value="{{ $plant->id }}">{{ $plant->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Quantity</label>
                    <input type="number" id="quantity_input" class="form-control" min="1" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Date</label>
                    <input type="datetime-local" id="date_input" class="form-control" 
                           value="{{ now()->format('Y-m-d\TH:i') }}" required>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Create Transfer</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Transfer Records</h2>
    </div>
    <div class="card-body">
        <form method="GET" class="mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="date" class="form-control" value="{{ request('date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Search Product/Label</label>
                    <input type="text" name="search" class="form-control" 
                           value="{{ request('search') }}" placeholder="Search...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Customer</label>
                    <select name="customer" class="form-select">
                        <option value="">All Customers</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" 
                                {{ request('customer') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Plant</label>
                    <select name="plant" class="form-select">
                        <option value="">All Plants</option>
                        @foreach($plants as $plant)
                            <option value="{{ $plant->id }}" 
                                {{ request('plant') == $plant->id ? 'selected' : '' }}>
                                {{ $plant->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr class="bg-light">
                        <th colspan="5" class="text-end pe-4">
                            Total Records: <span class="badge bg-secondary">{{ $bales->total() }}</span>
                        </th>
                    </tr>
                    <tr>
                        <th>Bale Info</th>
                        <th>Product Details</th>
                        <th>From Plant</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bales as $bale)
                    <tr>
                        <td>
                            <span class="badge bg-{{ $bale->type === 'inward' ? 'info' : ($bale->type === 'cutting' ? 'danger' : 'warning') }}"> 
                                {{ ucfirst($bale->type) }}
                            </span><br>
                            {{ $bale->bale_no }}<br>
                            <small class="text-muted">{{ $bale->created_at->format('Y-m-d h:i A') }}</small>
                        </td>
                        <td>
                            <strong>{{ $bale->packinglist->customer->name }}</strong>
                            ({{ $bale->packinglist->customer->short_code }})<br>
                            <strong>{{ $bale->packinglist->product->name }}</strong><br>
                            <small>{{ $bale->packinglist->label_name }}</small>
                        </td>
                        <td>{{ $bale->plant->name }}</td>
                        <td>
                            <form action="{{ route('bales.destroy', $bale) }}" method="POST" 
                                  class="d-inline" onsubmit="return confirm('Are you sure?')">
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

        <div class="mt-3">
            {{ $bales->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(async function() {
    $('#customer_select, #packinglist_select').select2();

    // Define form field IDs to store/restore
    const formFields = {
        customer: 'customer_select',
        type: 'type_select',
        plant: 'plant_select',
        date: 'date_input'
    };

    // Restore saved values
    async function restoreFormValues() {
        // Restore customer and trigger product load
        const lastCustomerId = localStorage.getItem('lastPlantTransferCustomerId');
        if (lastCustomerId) {
            $('#customer_select').val(lastCustomerId).trigger('change');
            try {
                const response = await fetch(`/plant-transfer/packinglists?customer_id=${lastCustomerId}`);
                const data = await response.json();
                
                $('#packinglist_select').prop('disabled', false)
                    .empty()
                    .append('<option value="">Select Product</option>');
                    
                data.forEach(item => {
                    $('#packinglist_select').append(new Option(item.text, item.id));
                });
            } catch (error) {
                console.error('Error loading products:', error);
            }
        }

        // Restore other form fields
        $('#type_select').val(localStorage.getItem('lastPlantTransferType') || '');
        $('#plant_select').val(localStorage.getItem('lastPlantTransferPlant') || '');
        $('#date_input').val(localStorage.getItem('lastPlantTransferDate') || '{{ now()->format("Y-m-d\TH:i") }}');
    }

    // Call restore function on page load
    await restoreFormValues();

    // Save form values when changed
    $('#customer_select').on('change', async function() {
        const customerId = this.value;
        if (customerId) {
            localStorage.setItem('lastPlantTransferCustomerId', customerId);
            // Load products as before
            try {
                const response = await fetch(`/plant-transfer/packinglists?customer_id=${customerId}`);
                const data = await response.json();
                
                $('#packinglist_select').prop('disabled', false)
                    .empty()
                    .append('<option value="">Select Product</option>');
                    
                data.forEach(item => {
                    $('#packinglist_select').append(new Option(item.text, item.id));
                });
            } catch (error) {
                alert('Error loading products');
            }
        } else {
            $('#packinglist_select').prop('disabled', true).empty();
        }
    });

    // Save other field values when changed
    $('#type_select').on('change', function() {
        localStorage.setItem('lastPlantTransferType', this.value);
    });

    $('#plant_select').on('change', function() {
        localStorage.setItem('lastPlantTransferPlant', this.value);
    });

    $('#date_input').on('change', function() {
        localStorage.setItem('lastPlantTransferDate', this.value);
    });

    // Form submission handler
    $('#transferForm').on('submit', async function(e) {
        e.preventDefault();
        
        const formData = {
            packinglist_id: $('#packinglist_select').val(),
            type: $('#type_select').val(),
            plant_id: $('#plant_select').val(),
            quantity: $('#quantity_input').val(),
            created_at: $('#date_input').val()
        };

        try {
            const response = await fetch('/plant-transfer', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            });

            const result = await response.json();
            
            if (result.success) {
                // Keep all stored values (don't clear them)
                alert('Transfer created successfully');
                window.location.reload();
            } else {
                throw new Error(result.message || 'Failed to create transfer');
            }
        } catch (error) {
            alert(error.message || 'Error creating transfer');
        }
    });
});
</script>
@endpush
@endsection
