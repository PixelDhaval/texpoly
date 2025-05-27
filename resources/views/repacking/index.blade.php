@extends('labels.layout')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
    .select2-container {
        width: 100% !important;
    }

    .source-details,
    .target-details {
        display: none;
    }
</style>
@endpush

@section('content')
<div class="card">
    <div class="card-header">
        <h2>Repacking</h2>
    </div>
    <div class="card-body">
        <form id="repackingForm">
            @csrf
            <!-- Source Section -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label">Bale No (Optional)</label>
                    <input type="text" id="bale_no" class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Source Customer</label>
                    <select id="source_customer" class="form-select">
                        <option value="">Select Customer</option>
                        @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Source Product</label>
                    <select id="source_packinglist" class="form-select" disabled>
                        <option value="">Select Product</option>
                    </select>
                </div>
            </div>

            <!-- Source Details -->
            <div class="source-details card mb-4">
                <div class="card-body" id="sourceDetails"></div>
            </div>

            <!-- Target Section -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label">Target Customer</label>
                    <select id="target_customer" class="form-select">
                        <option value="">Select Customer</option>
                        @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Target Product</label>
                    <select id="target_packinglist" class="form-select" disabled>
                        <option value="">Select Product</option>
                    </select>
                </div>
            </div>

            <!-- Target Details -->
            <div class="target-details card mb-4">
                <div class="card-body" id="targetDetails"></div>
            </div>

            <!-- QC and Finalist -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label">QC</label>
                    <select id="qc" class="form-select" required>
                        <option value="">Select QC</option>
                        @foreach($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Finalist</label>
                    <select id="finalist" class="form-select" required>
                        <option value="">Select Finalist</option>
                        @foreach($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" id="submitBtn" disabled>Print</button>
        </form>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">
        <h2>Today's Repacking Bales</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Bale No</th>
                        <th>Source Product</th>
                        <th>Source Customer</th>
                        <th>Target Product</th>
                        <th>Target Customer</th>
                        <th>QC</th>
                        <th>Finalist</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($todayBales as $bale)
                    <tr>
                        <td>{{ $bale->bale_no }}</td>
                        <td>{{ $bale->refPackinglist->product->name }}</td>
                        <td>{{ $bale->refPackinglist->customer->name }}</td>
                        <td>{{ $bale->packinglist->product->name }}</td>
                        <td>{{ $bale->packinglist->customer->name }}</td>
                        <td>{{ $bale->qcEmployee->name }}</td>
                        <td>{{ $bale->finalistEmployee->name }}</td>
                        <td>{{ $bale->created_at->format('H:i:s') }}</td>
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
                <button type="button" class="btn btn-primary" id="modalPrintBtn">Print</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const token = document.querySelector('meta[name="csrf-token"]').content;
        let currentBale = null;

        // Initialize Select2
        $('.form-select').select2();

        // Handle bale number input
        $('#bale_no').on('change', async function() {
            if (!this.value) return;

            try {
                const response = await fetch(`/repacking/bale-details?bale_no=${this.value}`);
                const data = await response.json();

                if (response.ok) {
                    currentBale = data;
                    $('#source_customer').val(data.packinglist.customer_id).trigger('change');
                    updateSourceDetails(data.packinglist);
                }
            } catch (error) {
                alert('Error fetching bale details');
            }
        });

        // Handle source customer selection
        $('#source_customer').on('change', async function() {
            const customerId = this.value;
            if (!customerId) return;

            try {
                const response = await fetch(`/repacking/packinglists?customer_id=${customerId}&type=source`);
                const data = await response.json();

                let options = '<option value="">Select Product</option>';
                data.packing_lists.forEach(item => {
                    options += `<option value="${item.id}" data-info='${JSON.stringify(item)}'>
                    ${item.product.name} - ${item.label_name}
                </option>`;
                });

                $('#source_packinglist').html(options).prop('disabled', false);
            } catch (error) {
                alert('Error fetching products');
            }
        });

        // Handle source packinglist selection
        $('#source_packinglist').on('change', function() {
            const info = $(this).find(':selected').data('info');
            if (info) updateSourceDetails(info);
        });

        // Handle target customer selection
        $('#target_customer').on('change', async function() {
            const customerId = this.value;
            if (!customerId) return;

            try {
                const response = await fetch(`/repacking/packinglists?customer_id=${customerId}&type=target`);
                const data = await response.json();

                let options = '<option value="">Select Product</option>';
                data.packing_lists.forEach(item => {
                    const escapedInfo = JSON.stringify(item).replace(/'/g, '&apos;').replace(/"/g, '&quot;');
                    options += `<option value="${item.id}" 
                data-user="${data.user.replace(/"/g, '&quot;')}" 
                data-info='${escapedInfo}' 
                data-is-bale-no="${item.customer.is_bale_no}" 
                data-is-printed-by="${item.customer.is_printed_by}" 
                data-is-qr="${item.customer.is_qr}" 
                data-label-code="${item.customer.label.label_code.replace(/"/g, '&quot;')}" 
                data-label-name="${item.label_name.replace(/"/g, '&quot;')}" 
                data-packing="${item.quantity} ${item.unit}" 
                data-is-bold="${item.is_bold}">
                ${item.product.name} - ${item.label_name}
            </option>`;
                });

                $('#target_packinglist').html(options).prop('disabled', false);
            } catch (error) {
                alert('Error fetching products');
            }
        });

        // Handle target packinglist selection
        $('#target_packinglist').on('change', function() {
            var labelCode = $(this).find(':selected').data('label-code');
            const is_bale_no = $(this).find(':selected').data('is-bale-no');
            const is_printed_by = $(this).find(':selected').data('is-printed-by');
            const is_qr = $(this).find(':selected').data('is-qr');
            const user = $(this).find(':selected').data('user');

            const info = $(this).find(':selected').data('info');

            if (labelCode) {
                labelCode = labelCode.replaceAll('[[ label-name ]]', info.label_name);
                labelCode = labelCode.replaceAll('[[ packing ]]', info.quantity + ' ' + info.unit);
                labelCode = labelCode.replaceAll('[[ bold ]]', info.is_bold ? '<strong>' : '');
                labelCode = labelCode.replaceAll('[[ /bold ]]', info.is_bold ? '</strong>' : '');
                labelCode = labelCode.replaceAll('[[ baleid ]]', is_bale_no == "true" || is_bale_no == true ? '[[ baleid ]]' : '');
                labelCode = labelCode.replaceAll('[[ printed-by ]]', is_printed_by == "true" || is_printed_by == true ? 'printed by: ' + user : '');
                labelCode = labelCode.replaceAll('[[ qr-1 ]]', is_qr == "true" || is_qr == true ? '<div id="qrcode-1">[[ qr ]]</div>' : '');
                labelCode = labelCode.replaceAll('[[ qr-2 ]]', is_qr == "true" || is_qr == true ? '<div id="qrcode-2">[[ qr ]]</div>' : '');
            }
            if (info) updateTargetDetails(info);
            $('#submitBtn').prop('disabled', false);
            $('#submitBtn').data('label-code', labelCode);
        });

        function updateSourceDetails(info) {
            $('.source-details').show();
            $('#sourceDetails').html(`
            <h5>Source Details</h5>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Product:</strong> ${info.product.name}</p>
                    <p><strong>Label:</strong> ${info.label_name}<br>${info.quantity} ${info.unit}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Stock:</strong> ${info.stock}</p>
                    <p><strong>Customer Qty:</strong> ${info.customer_qty}</p>
                </div>
            </div>
        `);
        }

        function updateTargetDetails(info) {
            $('.target-details').show();
            $('#targetDetails').html(`
            <h5>Target Details</h5>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Product:</strong> ${info.product.name}</p>
                    <p><strong>Label:</strong> ${info.label_name}<br>${info.quantity} ${info.unit}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Stock:</strong> ${info.stock}</p>
                    <p><strong>Customer Qty:</strong> ${info.customer_qty}</p>
                </div>
            </div>
        `);
        }

        // Handle form submission
        $('#repackingForm').on('submit', async function(e) {
            e.preventDefault();

            const formData = {
                packinglist_id: $('#target_packinglist').val(),
                ref_packinglist_id: $('#source_packinglist').val(),
                ref_bale_id: currentBale?.id,
                qc: $('#qc').val(),
                finalist: $('#finalist').val()
            };

            if (!formData.packinglist_id || !formData.ref_packinglist_id || !formData.qc || !formData.finalist) {
                alert('Please fill all required fields');
                return;
            }

            try {
                const response = await fetch('/repacking/create-bale', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                if (result.success) {
                    alert(`Bale created successfully: ${result.bale.bale_no}`);
                    window.location.reload();
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                alert('Error creating bale: ' + error.message);
            }
        });

        // Handle Print Button Click
        $('#submitBtn').on('click', function(e) {
            e.preventDefault();

            const labelCode = $(this).data('label-code');
            // if (!labelCode) {
            //     alert('Please select a valid product.');
            //     return;
            // }

            // Show the print modal and load the label code
            const preview = document.getElementById('preview');
            preview.innerHTML = labelCode;

            const modalPrintBtn = document.getElementById('modalPrintBtn');
            modalPrintBtn.dataset.labelCode = labelCode; // Pass label code to modal print button
            $('#printModal').modal('show');
        });

        // Handle Modal Print Button Click
        $('#modalPrintBtn').on('click', async function() {
            const labelCode = this.dataset.labelCode;
            const qc = $('#qc').val();
            const finalist = $('#finalist').val();
            const packinglistId = $('#target_packinglist').val();
            const refPackinglistId = $('#source_packinglist').val();

            if (!qc || !finalist || !packinglistId || !refPackinglistId) {
                alert('Please fill all required fields.');
                return;
            }

            try {
                const response = await fetch('/repacking/create-bale', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify({
                        packinglist_id: packinglistId,
                        ref_packinglist_id: refPackinglistId,
                        qc: qc,
                        finalist: finalist,
                    }),
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    const printPreview = document.getElementById('preview');
                    let labelContent = printPreview.innerHTML.replace(/\[\[ baleid \]\]/g, result.bale.bale_no);

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
                                window.onload = function () {
                                    if (document.getElementById('qrcode-1')) {
                                        document.getElementById('qrcode-1').textContent = '';
                                        new QRCode(document.getElementById('qrcode-1'), {
                                            text: "${result.qrUrl}",
                                            width: 100,
                                            height: 100,
                                            colorDark: "#000000",
                                            colorLight: "#ffffff",
                                            correctLevel: QRCode.CorrectLevel.L,
                                        });
                                        document.getElementById('qrcode-2').textContent = '';
                                        new QRCode(document.getElementById('qrcode-2'), {
                                            text: "${result.qrUrl}",
                                            width: 100,
                                            height: 100,
                                            colorDark: "#000000",
                                            colorLight: "#ffffff",
                                            correctLevel: QRCode.CorrectLevel.L,
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
                    window.location.reload();
                } else {
                    throw new Error(result.message || 'Failed to create bale');
                }
            } catch (error) {
                alert('Error creating bale: ' + error.message);
            }
        });
    });
</script>
@endpush
@endsection