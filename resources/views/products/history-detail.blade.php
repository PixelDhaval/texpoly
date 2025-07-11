@extends('labels.layout')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <h4 class="mb-0 me-3">{{ $product->name }} ({{ $product->short_code }})</h4>
            <select id="customerSelect" class="form-select" style="width: 200px">
                <option value="">All Customers</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                        {{ $customer->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <a href="{{ url()->previous() }}" class="btn btn-secondary btn-sm">Back</a>
    </div>
    <div class="card-body">
        <!-- Summary Section -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-title">Opening Balance</h6>
                        <h3 class="mb-0">{{ $openingBalance }}</h3>
                        <small class="text-muted">As of {{ $fromDate->format('d M Y') }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-title">Current Stock</h6>
                        <h3 class="mb-0">{{ $currentStock }}</h3>
                        <small class="text-muted">Real-time stock</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-title">Total Movements</h6>
                        <h3 class="mb-0">{{ 
                            $movements['production'] + 
                            $movements['repacking_in'] + 
                            $movements['repacking_out'] + 
                            $movements['inward'] + 
                            $movements['outward'] + 
                            $movements['cutting'] + 
                            $movements['transfer_in'] + 
                            $movements['transfer_out'] + 
                            $movements['dispatch']
                        }}</h3>
                        <small class="text-muted">During period</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-title">Closing Balance</h6>
                        <h3 class="mb-0">{{ $closingBalance }}</h3>
                        <small class="text-muted">As of {{ $toDate->format('d M Y') }}</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs Section -->
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#production">
                    Production <span class="badge bg-primary">{{ $movements['production'] }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#repacking">
                    Repacking <span class="badge bg-primary">{{ $movements['repacking_in'] + $movements['repacking_out'] }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#inward">
                    Inward <span class="badge bg-primary">{{ $movements['inward'] }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#outward">
                    Outward <span class="badge bg-primary">{{ $movements['outward'] }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#cutting">
                    Cutting <span class="badge bg-primary">{{ $movements['cutting'] }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#transfer">
                    Transfer <span class="badge bg-primary">{{ $movements['transfer_in'] + $movements['transfer_out'] }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#dispatch">
                    Dispatch <span class="badge bg-primary">{{ $movements['dispatch'] }}</span>
                </a>
            </li>
        </ul>

        <div class="tab-content mt-3">
            <!-- Production Tab -->
            <div class="tab-pane fade show active" id="production">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Customer</th>
                                <th>Bale No</th>
                                <th>QC</th>
                                <th>Finalist</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($productionBales as $bale)
                            <tr>
                                <td>{{ $bale->created_at->format('Y-m-d') }}</td>
                                <td>{{ $bale->created_at->format('H:i:s') }}</td>
                                <td><strong>{{ $bale->packinglist->customer->name }}</strong></td>
                                <td>{{ $bale->bale_no }}</td>
                                <td>{{ $bale->qcEmployee->name ?? 'None' }}</td>
                                <td>{{ $bale->finalistEmployee->name ?? 'None' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center">No production records found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Repacking Tab -->
            <div class="tab-pane fade" id="repacking">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Bale No</th>
                                <th>From</th>
                                <th>To</th>
                                <th>QC</th>
                                <th>Finalist</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($repackingBales as $bale)
                            <tr>
                                <td>{{ $bale->created_at->format('Y-m-d') }}</td>
                                <td>{{ $bale->created_at->format('H:i:s') }}</td>
                                <td>{{ $bale->bale_no }}</td>
                                <td><strong>{{ $bale->refPackinglist->customer->name }} - {{ $bale->refPackinglist->product->name }}</strong></td>
                                <td><strong>{{ $bale->packinglist->customer->name }} - {{ $bale->packinglist->product->name }}</strong></td>
                                <td>{{ $bale->qcEmployee->name ?? 'None' }}</td>
                                <td>{{ $bale->finalistEmployee->name ?? 'None' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center">No repacking records found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Inward Tab -->
            <div class="tab-pane fade" id="inward">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Customer</th>
                                <th>Bale No</th>
                                <th>Plant</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($inwardBales as $bale)
                            <tr>
                                <td>{{ $bale->created_at->format('Y-m-d') }}</td>
                                <td>{{ $bale->created_at->format('H:i:s') }}</td>
                                <td><strong>{{ $bale->packinglist->customer->name }}</strong></td>
                                <td>{{ $bale->bale_no }}</td>
                                <td>{{ $bale->plant->name ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center">No inward records found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Outward Tab -->
            <div class="tab-pane fade" id="outward">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Customer</th>
                                <th>Bale No</th>
                                <th>Plant</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($outwardBales as $bale)
                            <tr>
                                <td>{{ $bale->created_at->format('Y-m-d') }}</td>
                                <td>{{ $bale->created_at->format('H:i:s') }}</td>
                                <td><strong>{{ $bale->packinglist->customer->name }}</strong></td>
                                <td>{{ $bale->bale_no }}</td>
                                <td>{{ $bale->plant->name ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center">No outward records found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Cutting Tab -->
            <div class="tab-pane fade" id="cutting">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Customer</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cuttingBales as $bale)
                            <tr>
                                <td>{{ $bale->created_at->format('Y-m-d') }}</td>
                                <td>{{ $bale->created_at->format('H:i:s') }}</td>
                                <td>{{ $bale->packinglist->customer->name }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center">No cutting records found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Transfer Tab -->
            <div class="tab-pane fade" id="transfer">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Bale No</th>
                                <th>From</th>
                                <th>To</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(array_merge($transferInBales->toArray(), $transferOutBales->toArray()) as $bale)
                            <tr>
                                <td>{{ Carbon\Carbon::parse($bale['created_at'])->format('Y-m-d') }}</td>
                                <td>{{ Carbon\Carbon::parse($bale['created_at'])->format('H:i:s') }}</td>
                                <td>{{ $bale['bale_no'] }}</td>
                                <td>{{ $bale['ref_packinglist']['customer']['name'] ?? '-' }}</td>
                                <td>{{ $bale['packinglist']['customer']['name'] ?? '-' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center">No transfer records found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Dispatch Tab -->
            <div class="tab-pane fade" id="dispatch">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Order No</th>
                                <th>Customer</th>
                                <th>Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dispatchBales as $bale)
                            <tr>
                                <td>{{ Carbon\Carbon::parse($bale->created_at)->format('Y-m-d') }}</td>
                                <td>{{ $bale->bale_no }}</td>
                                <td>{{ $bale->customer_name ?? '-' }}</td>
                                <td>{{ $bale->dispatch_qty }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center">No dispatch records found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .card .bg-light {
        transition: all 0.3s ease;
    }
    .card .bg-light:hover {
        background-color: #f8f9fa !important;
        transform: translateY(-2px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .nav-tabs .badge {
        margin-left: 5px;
    }
    .table-responsive {
        max-height: 500px;
        overflow-y: auto;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle customer selection change
    document.getElementById('customerSelect').addEventListener('change', function() {
        const url = new URL(window.location.href);
        if (this.value) {
            url.searchParams.set('customer_id', this.value);
        } else {
            url.searchParams.delete('customer_id');
        }
        window.location.href = url.toString();
    });

    // Initialize tooltips if needed
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
});
</script>
@endpush
@endsection