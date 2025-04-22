@extends('labels.layout')

@section('content')
<div class="card">
    <div class="card-header">
        <h2>Transfer Bales</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('bales.transfer.store') }}" id="transferForm">
            @csrf
            <div class="row mb-3">
                <!-- From Section -->
                <div class="col-md-6">
                    <h4>From</h4>
                    <div class="mb-3">
                        <label class="form-label">Customer</label>
                        <select class="form-select" id="fromCustomer" required>
                            <option value="">Select Customer</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ old('fromCustomer') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Product</label>
                        <select class="form-select" name="from_packinglist" id="fromPackinglist" required>
                            <option value="">Select Product</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Available Stock</label>
                        <input type="text" class="form-control" id="fromStock" readonly>
                    </div>
                </div>

                <!-- To Section -->
                <div class="col-md-6">
                    <h4>To</h4>
                    <div class="mb-3">
                        <label class="form-label">Customer</label>
                        <select class="form-select" id="toCustomer" required>
                            <option value="">Select Customer</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ old('toCustomer') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Product</label>
                        <select class="form-select" name="to_packinglist" id="toPackinglist" required>
                            <option value="">Select Product</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Quantity to Transfer</label>
                    <input type="number" name="quantity" class="form-control" required min="1" id="quantity" value="{{ old('quantity') }}">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Transfer Bales</button>
        </form>
    </div>
</div>

@push('scripts')

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize Select2 with templateResult to show stock
    $('#fromCustomer, #toCustomer').select2();
    $('#fromPackinglist, #toPackinglist').select2({
        placeholder: 'Select Product',
        templateResult: function(data) {
            return data.text;
        },
        templateSelection: function(data, container) {
            // Update stock when an option is selected
            if (data.element) {
                const stock = $(data.element).data('stock');
                if (stock !== undefined) {
                    $('#fromStock').val(stock);
                    $('#quantity').attr('max', stock);
                }
            }
            return data.text;
        }
    });

    // Preload data if there are old values
    const oldFromCustomer = "{{ old('fromCustomer') }}";
    const oldToCustomer = "{{ old('toCustomer') }}";
    const oldFromPackinglist = "{{ old('from_packinglist') }}";
    const oldToPackinglist = "{{ old('to_packinglist') }}";

    if (oldFromCustomer) {
        $('#fromCustomer').val(oldFromCustomer).trigger('change');
        // Load packinglists and set selected value after AJAX completes
        loadPackinglists(oldFromCustomer, $('#fromPackinglist'), 'from').then(() => {
            if (oldFromPackinglist) {
                $('#fromPackinglist').val(oldFromPackinglist).trigger('change');
            }
        });
    }

    if (oldToCustomer) {
        $('#toCustomer').val(oldToCustomer).trigger('change');
        // Load packinglists and set selected value after AJAX completes
        loadPackinglists(oldToCustomer, $('#toPackinglist'), 'to').then(() => {
            if (oldToPackinglist) {
                $('#toPackinglist').val(oldToPackinglist).trigger('change');
            }
        });
    }
    
    // Modify loadPackinglists to include type parameter
    function loadPackinglists(customerId, targetSelect, type = 'from') {
        if (!customerId) return Promise.resolve();
        
        return new Promise((resolve) => {
            $.ajax({
                url: '{{ route("bales.packinglists") }}',
                data: { 
                    customer_id: customerId,
                    type: type
                },
                success: function(data) {
                    targetSelect.empty().append('<option value="">Select Product</option>');
                    data.forEach(function(item) {
                        const option = new Option(item.text, item.id, false, false);
                        $(option).data('stock', item.stock);
                        targetSelect.append(option);
                    });
                    targetSelect.trigger('change');
                    resolve();
                }
            });
        });
    }

    $('#fromCustomer').change(function() {
        loadPackinglists($(this).val(), $('#fromPackinglist'), 'from');
    });

    $('#toCustomer').change(function() {
        loadPackinglists($(this).val(), $('#toPackinglist'), 'to').then(() => {
            // After loading products, if from product is selected, try to select matching product
            const fromProduct = $('#fromPackinglist').find(':selected').text();
            if (fromProduct) {
                const toPackinglist = $('#toPackinglist');
                toPackinglist.find('option').each(function() {
                    if ($(this).text().split(' (')[0] === fromProduct.split(' (')[0]) {
                        toPackinglist.val($(this).val()).trigger('change');
                        return false;
                    }
                });
            }
        });
    });

    $('#fromPackinglist').change(function() {
        const selectedProduct = $(this).find(':selected').text();
        const stock = $(this).find(':selected').data('stock') || 0;
        $('#fromStock').val(stock);
        $('#quantity').attr('max', stock);

        // If "to" customer is selected, find and select matching product
        if ($('#toCustomer').val()) {
            const toPackinglist = $('#toPackinglist');
            toPackinglist.find('option').each(function() {
                if ($(this).text().split(' (')[0] === selectedProduct.split(' (')[0]) {
                    toPackinglist.val($(this).val()).trigger('change');
                    return false;
                }
            });
        }
    });

    $('#transferForm').submit(function(e) {
        const qty = parseInt($('#quantity').val());
        const stock = parseInt($('#fromStock').val());
        
        if (qty > stock) {
            e.preventDefault();
            alert('Transfer quantity cannot exceed available stock');
        }
    });
});
</script>
@endpush
@endsection
