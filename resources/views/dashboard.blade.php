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
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Activities</h5>
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
