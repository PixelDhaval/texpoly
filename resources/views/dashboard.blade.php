@extends('labels.layout')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Today's Production Card -->
        <div class="col-xl-3 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Today's Production</h5>
                    <h2 class="mt-3 mb-3">{{ $todayProduction ?? 0 }}</h2>
                    <p class="card-text text-muted">
                        Bales produced today
                    </p>
                </div>
            </div>
        </div>

        <!-- Today's Repacking Card -->
        <div class="col-xl-3 col-sm-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Today's Repacking</h5>
                    <h2 class="mt-3 mb-3">{{ $todayRepacking ?? 0 }}</h2>
                    <p class="card-text text-muted">
                        Bales repacked today
                    </p>
                </div>
            </div>
        </div>
    </div>

    @can('orders')
    <div class="row">
        <div class="col-xl-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Latest Completed Orders</h5>
                    <a href="{{ route('orders.index') }}?status=delivered" class="btn btn-primary btn-sm">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order No</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Container</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($deliveredOrders as $order)
                                <tr>
                                    <td>
                                        <a href="{{ route('orders.show', $order) }}">{{ $order->order_no }}</a>
                                    </td>
                                    <td>{{ $order->customer->name }}</td>
                                    <td>{{ $order->order_date ? Carbon\Carbon::parse($order->order_date)->format('d/m/Y') : '-' }}</td>
                                    <td>{{ $order->container_no }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center">No completed orders</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Orders Card -->
        <div class="col-xl-6 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Upcoming Orders</h5>
                    <a href="{{ route('orders.index') }}?status=production" class="btn btn-primary btn-sm">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order No</th>
                                    <th>Customer</th>
                                    <th>Target Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($upcomingOrders as $order)
                                <tr>
                                    <td>
                                        <a href="{{ route('orders.show', $order) }}">{{ $order->order_no }}</a>
                                    </td>
                                    <td>{{ $order->customer->name }}</td>
                                    <td>{{ $order->target_date ? Carbon\Carbon::parse($order->target_date)->format('d/m/Y') : '-' }}</td>
                                    <td>
                                        <span class="badge bg-primary">{{ ucfirst($order->status) }}</span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center">No upcoming orders</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan

    <div class="row mt-4">
        <!-- Production Chart -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Production Overview</h5>
                </div>
                <div class="card-body">
                    <canvas id="productionChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recent Activities</h5>
                    <a href="{{ route('bales.index') }}" class="btn btn-primary btn-sm">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Bale No</th>
                                    <th>Type</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentActivities ?? [] as $activity)
                                <tr>
                                    <td>{{ $activity->bale_no }}</td>
                                    <td>{{ ucfirst($activity->type) }}</td>
                                    <td>{{ $activity->created_at->format('H:i') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center">No recent activities</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Replace the completed orders card content -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="card-title mb-0">Completed Orders</h5>
                <a href="{{ route('orders.index') }}?status=delivered" class="btn btn-primary btn-sm">View All</a>
            </div>
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" 
                       id="this-month-tab" 
                       data-bs-toggle="tab" 
                       href="#this-month" 
                       role="tab">This Month</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" 
                       id="last-month-tab" 
                       data-bs-toggle="tab" 
                       href="#last-month" 
                       role="tab">Last Month</a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <!-- This Month Tab -->
                <div class="tab-pane fade show active" id="this-month" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order No</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Container</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($thisMonthDelivered as $order)
                                <tr>
                                    <td>
                                        <a href="{{ route('orders.show', $order) }}">{{ $order->order_no }}</a>
                                    </td>
                                    <td>{{ $order->customer->name }}</td>
                                    <td>{{ $order->order_date ? Carbon\Carbon::parse($order->order_date)->format('d/m/Y') : '-' }}</td>
                                    <td>{{ $order->container_no }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center">No orders completed this month</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Last Month Tab -->
                <div class="tab-pane fade" id="last-month" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order No</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Container</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lastMonthDelivered as $order)
                                <tr>
                                    <td>
                                        <a href="{{ route('orders.show', $order) }}">{{ $order->order_no }}</a>
                                    </td>
                                    <td>{{ $order->customer->name }}</td>
                                    <td>{{ $order->order_date ? Carbon\Carbon::parse($order->order_date)->format('d/m/Y') : '-' }}</td>
                                    <td>{{ $order->container_no }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center">No orders completed last month</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('productionChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($chartLabels ?? []),
            datasets: [{
                label: 'Production',
                data: @json($chartData ?? []),
                borderColor: '#0d6efd',
                tension: 0.4,
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>
@endpush
@endsection
