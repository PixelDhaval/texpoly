@extends('labels.layout')
@section('content')
<div class="card mb-3">
    <div class="card-header">
        <h2>Order Details - {{ $order->order_no }}</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('orders.update', $order) }}">
            @csrf
            @method('PUT')
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">Customer</label>
                    <input type="text" class="form-control" value="{{ $order->customer->name }}" readonly>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" id="orderStatus" required>
                        @foreach(['production', 'draft', 'delivered'] as $status)
                            <option value="{{ $status }}" {{ $order->status == $status ? 'selected' : '' }}>
                                {{ $status == 'delivered' ? 'Completed' : ucfirst($status) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-3 order-date-field" style="{{ $order->status !== 'delivered' ? 'display: none;' : '' }}">
                    <label class="form-label">Order Complete Date</label>
                    <input type="date" name="order_date" class="form-control" 
                           value="{{ $order->order_date }}"     
                           id="orderDate"
                           {{ $order->status === 'delivered' ? 'required' : '' }}>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Target Date</label>
                    <input type="date" name="target_date" class="form-control" 
                           value="{{ $order->target_date }}" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Container No</label>
                    <input type="text" name="container_no" class="form-control" 
                           value="{{ $order->container_no }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">SGS Seal No</label>
                    <input type="text" name="sgs_seal_no" class="form-control" 
                           value="{{ $order->sgs_seal_no }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Line Seal No</label>
                    <input type="text" name="line_seal_no" class="form-control" 
                           value="{{ $order->line_seal_no }}">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Note</label>
                    <textarea name="note" class="form-control" rows="3">{{ $order->note }}</textarea>
                </div>
            </div>

            <div>
                <button type="submit" class="btn btn-primary">Update Order</button>
                <a href="{{ route('orders.index') }}" class="btn btn-secondary">Back</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Order Items</h2>
    </div>
    <div class="card-body">
        <form id="orderItemsForm" action="{{ route('orderlists.bulk-update') }}" method="POST">
            @csrf
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Product</th>
                            <th>Label Name</th>
                            <th>Customer Qty</th>
                            <th>Stock</th>
                            <th>Dispatch Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orderlists as $item)
                        <tr>
                            <td>{{ $item->packinglist->product->short_code }}</td>
                            <td>{{ $item->packinglist->product->name }}</td>
                            <td>{{ $item->packinglist->label_name }}</td>
                            <td>{{ $item->packinglist->customer_qty }}</td>
                            <td>{{ $item->packinglist->stock }}</td>
                            <td>
                                <input type="hidden" name="orderlist[{{ $loop->index }}][id]" value="{{ $item->id }}">
                                <input type="number" class="form-control form-control-sm dispatch-qty" 
                                    name="orderlist[{{ $loop->index }}][dispatch_qty]"
                                    value="{{ $item->dispatch_qty }}"
                                    min="0" 
                                    max="{{ $item->packinglist->stock + $item->dispatch_qty }}">
                            </td>
                        </tr>
                        @endforeach
                        <tr class="table-info">
                            <td colspan="5" class="text-end fw-bold">Total Dispatch Quantity:</td>
                            <td id="totalDispatchQty" class="fw-bold">0</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Update Quantities</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function updateTotal() {
    let total = 0;
    $('.dispatch-qty').each(function() {
        total += parseInt($(this).val()) || 0;
    });
    $('#totalDispatchQty').text(total);
}

$(document).ready(function() {
    updateTotal();
    $('.dispatch-qty').on('change', updateTotal);

    $('#orderStatus').on('change', function() {
        const isDelivered = $(this).val() === 'delivered';
        const orderDateField = $('.order-date-field');
        const orderDateInput = $('#orderDate');

        if (isDelivered) {
            orderDateField.show();
            orderDateInput.prop('required', true);
            // Set today's date if empty
        } else {
            orderDateField.hide();
            orderDateInput.prop('required', false);
        }
    });
});
</script>
@endpush
@endsection
