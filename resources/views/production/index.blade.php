@extends('labels.layout')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container {
        width: 100% !important;
    }
</style>
@endpush

@section('content')
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h2>Production Label Printing</h2>
            <div class="text-end">
                <div class="h5 mb-0">
                    Today's Production: <span class="badge bg-primary">{{ $todayCount }}</span>
                    <span class="ms-2">Cancel: <span class="badge bg-danger">{{ $cancelCount }}</span></span>
                    <span class="ms-2">Repack: <span class="badge bg-success">{{ $repackCount }}</span></span>
                </div>
                <small class="text-muted">
                    Yesterday: Production: {{ $yesterdayCount }} | Repack: {{ $yesterdayRepackCount }}
                </small>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-4">
                <label class="form-label">Product</label>
                <select id="product_select" class="form-select">
                    <option value="">Select Product</option>
                    @foreach($products as $product)
                    <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                        {{ $product->short_code }} - {{ $product->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">QC</label>
                <select id="qc_select" class="form-select">
                    <option value="">Select QC</option>
                    @foreach($employees as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Finalist</label>
                <select id="finalist_select" class="form-select">
                    <option value="">Select Finalist</option>
                    @foreach($employees as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered" id="orderlistTable">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Orders</th>
                        <th>Label Name</th>
                        <th>Customer Qty</th>
                        <th>Stock / Pending</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($customerOrders as $data)
                    @php
                    $totalCustomerQty = $data['orders']->sum('packinglist.customer_qty');
                    $currentStock = $data['orders']->first()->packinglist->stock;
                    $pendingQty = max(0, $totalCustomerQty - $currentStock);

                    // Find the appropriate order for printing based on stock
                    $printableOrder = null;
                    $runningTotal = 0;
                    foreach($data['orders'] as $order) {
                    $runningTotal += $order->packinglist->customer_qty;
                    if ($runningTotal > $currentStock) {
                    $printableOrder = $order;
                    break;
                    }
                    }
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $data['customer']->name }}</strong><br>
                            <small>{{ $data['customer']->country }}</small>
                        </td>
                        <td>
                            @foreach($data['orders'] as $order)
                            <div class="mb-1">
                                <strong>{{ $order->order->order_no }}</strong><span class="fst-italic fw-lighter"> Qty: {{ $order->packinglist->customer_qty }}</span><br>
                                <small>Target: {{ $order->order->target_date }}</small>
                                <small class="d-block"></small>
                            </div>
                            @endforeach
                        </td>
                        <td>{{ $data['orders']->first()->packinglist->label_name }} <br> {{ $data['orders']->first()->packinglist->quantity }} {{ $data['orders']->first()->packinglist->unit }}

                        </td>
                        <td>{{ $totalCustomerQty }}</td>
                        <td>
                            {{ $currentStock }}
                            @if($pendingQty > 0)
                            <br><span class="text-danger fw-bold">{{ $pendingQty }} pending</span>
                            @endif
                        </td>
                        <td>
                            @if($printableOrder)
                            @php
                            $labelCode = $data['orders']->first()->packinglist->customer->label->label_code;
                            $labelCode = str_replace('[[ label-name ]]', $data['orders']->first()->packinglist->label_name, $labelCode);
                            $packing = $data['orders']->first()->packinglist->quantity . ' ' . $data['orders']->first()->packinglist->unit;
                            $labelCode = str_replace('[[ packing ]]', $packing, $labelCode);
                            if($data['orders']->first()->packinglist->is_bold){
                            $labelCode = str_replace('[[ bold ]]', '<strong>', $labelCode);
                                $labelCode = str_replace('[[ /bold ]]', '</strong>', $labelCode);
                            } else {
                            $labelCode = str_replace('[[ bold ]]', '', $labelCode);
                            $labelCode = str_replace('[[ /bold ]]', '', $labelCode);
                            }
                            if($data['orders']->first()->packinglist->customer->is_bale_no == 1){
                            $labelCode = str_replace('[[ baleid ]]', '[[ baleid ]]', $labelCode);
                            } else {
                            $labelCode = str_replace('[[ baleid ]]', '', $labelCode);
                            }
                            if($data['orders']->first()->packinglist->customer->is_printed_by == 1){
                            $labelCode = str_replace('[[ printed-by ]]', 'printed by: '. Auth::user()->name , $labelCode);
                            } else {
                            $labelCode = str_replace('[[ printed-by ]]', '', $labelCode);
                            }

                            if($data['orders']->first()->packinglist->customer->is_qr == 1){
                            $labelCode = str_replace('[[ qr-1 ]]', '<div id="qrcode-1">[[ qr ]]</div>', $labelCode);
                            $labelCode = str_replace('[[ qr-2 ]]', '<div id="qrcode-2">[[ qr ]]</div>', $labelCode);
                            } else {
                            $labelCode = str_replace('[[ qr-1 ]]', '', $labelCode);
                            $labelCode = str_replace('[[ qr-2 ]]', '', $labelCode);
                            }
                            @endphp

                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#printModal"
                                data-id="{{ $printableOrder->packinglist_id }}" data-code="{{ $labelCode }}">
                                Print
                            </button>
                            @else
                            @php
                            $labelCode = $data['orders']->first()->packinglist->customer->label->label_code;
                            $labelCode = str_replace('[[ label-name ]]', $data['orders']->first()->packinglist->label_name, $labelCode);
                            $packing = $data['orders']->first()->packinglist->quantity . ' ' . $data['orders']->first()->packinglist->unit;
                            $labelCode = str_replace('[[ packing ]]', $packing, $labelCode);
                            if($data['orders']->first()->packinglist->is_bold){
                            $labelCode = str_replace('[[ bold ]]', '<strong>', $labelCode);
                                $labelCode = str_replace('[[ /bold ]]', '</strong>', $labelCode);
                            } else {
                            $labelCode = str_replace('[[ bold ]]', '', $labelCode);
                            $labelCode = str_replace('[[ /bold ]]', '', $labelCode);
                            }
                            if($data['orders']->first()->packinglist->customer->is_bale_no == 1){
                            $labelCode = str_replace('[[ baleid ]]', '[[ baleid ]]', $labelCode);
                            } else {
                            $labelCode = str_replace('[[ baleid ]]', '', $labelCode);
                            }
                            if($data['orders']->first()->packinglist->customer->is_printed_by == 1){
                            $labelCode = str_replace('[[ printed-by ]]', 'printed by: '. Auth::user()->name , $labelCode);
                            } else {
                            $labelCode = str_replace('[[ printed-by ]]', '', $labelCode);
                            }

                            if($data['orders']->first()->packinglist->customer->is_qr == 1){
                            $labelCode = str_replace('[[ qr-1 ]]', '<div id="qrcode-1">[[ qr ]]</div>', $labelCode);
                            $labelCode = str_replace('[[ qr-2 ]]', '<div id="qrcode-2">[[ qr ]]</div>', $labelCode);
                            } else {
                            $labelCode = str_replace('[[ qr-1 ]]', '', $labelCode);
                            $labelCode = str_replace('[[ qr-2 ]]', '', $labelCode);
                            }
                            @endphp
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#printModal"
                                data-id="{{ $data['orders']->first()->packinglist_id }}" data-code="{{ $labelCode }}">
                                Print (Completed)
                            </button>
                            @endif
                        </td>
                        <td>

                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Print Modal -->
<div class="modal fade" id="printModal" tabindex="-1" aria-labelledby="printModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="printModalLabel">Print Label</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="preview"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="printBtn">Print</button>
            </div>
        </div>
    </div>
</div>


@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="/js/qrcode.js"></script>
<script>
    $(document).ready(function() {
        // Initialize Select2
        $('#product_select, #qc_select, #finalist_select').select2();

        // Handle product change
        $('#product_select').on('select2:select', function(e) {
            const productId = e.target.value;
            window.location.href = productId ?
                `/production?product_id=${productId}` :
                '/production';
        });


        // Handle print button click
        document.querySelector('#printBtn').addEventListener('click', async function(e) {
            const packinglistId = e.target.dataset.id;
            const qc = document.querySelector('#qc_select').value;
            const finalist = document.querySelector('#finalist_select').value;
            const token = document.querySelector('meta[name="csrf-token"]').content;

           

            try {
                const response = await fetch('/production/bales', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        packinglist_id: e.target.dataset.id,
                        qc: qc,
                        finalist: finalist
                    })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    const printPreview = document.getElementById('preview');
                    let labelContent = printPreview.innerHTML
                        .replace(/\[\[ baleid \]\]/g, result.bale.bale_no);
                    
                    const printWindow = window.open('', '_blank');
                    printWindow.document.write(`
                        <html>
                            <head>
                                <title>Print Label</title>
                                <script src="/js/qrcode.js"><\/script>
                            </head>
                            <body>
                                ${labelContent}
                                <script>
                                   window.onload = function() {
                                        if(document.getElementById('qrcode-1')) {
                                            document.getElementById('qrcode-1').textContent = '';
                                            new QRCode(document.getElementById('qrcode-1'), {
                                                text: "${result.qrUrl}",
                                                width: 100,
                                                height: 100,
                                                colorDark: "#000000",
                                                colorLight: "#ffffff",
                                                correctLevel: QRCode.CorrectLevel.L
                                            });
                                        }
                                        if(document.getElementById('qrcode-2')) {
                                            document.getElementById('qrcode-2').textContent = '';
                                            new QRCode(document.getElementById('qrcode-2'), {
                                                text: "${result.qrUrl}",
                                                width: 100,
                                                height: 100,
                                                colorDark: "#000000",
                                                colorLight: "#ffffff",
                                                correctLevel: QRCode.CorrectLevel.L
                                            });
                                        }
                                        setTimeout(() => {
                                            window.print();
                                            window.close();
                                        }, 500);
                                    };
                                <\/script>
                            </body>
                        </html>
                    `);
                    printWindow.document.close();
                    setTimeout(() => {
                        window.location.reload();
                    }, 500)
                } else {
                    throw new Error(result.message || 'Failed to create bale');
                }
            } catch (error) {
                alert(error.message || 'Error creating bale' + error);
            }
        });

        const printModal = document.getElementById('printModal');
        printModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget; // Button that triggered the modal
            const packinglistId = button.getAttribute('data-id'); // Extract info from data-* attributes
            const labelCode = button.getAttribute('data-code');
            const isCompleted = button.textContent.includes('(Completed)'); // Check if the button is for a completed order
            const qc = document.querySelector('#qc_select').value;
            const finalist = document.querySelector('#finalist_select').value;

            if (!qc || !finalist) {
                alert('Please select QC and Finalist');
                event.preventDefault()
                return;
            }
            if (isCompleted) {
                const confirmPrint = confirm('This order is marked as completed. Do you still want to print?');
                if (!confirmPrint) {
                    event.preventDefault(); // Prevent the modal from opening
                    return;
                }
            }

            // Update the modal's content.
            const preview = document.getElementById('preview');
            preview.innerHTML = `${labelCode}`; // Display the label code in the modal

            // Set up the print button
            const printBtn = document.getElementById('printBtn');
            printBtn.setAttribute('data-id', packinglistId); // Set the packinglist ID for the print button
        });
    });
</script>
@endpush
@endsection