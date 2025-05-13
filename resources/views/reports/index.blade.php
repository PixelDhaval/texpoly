@extends('labels.layout')

@push('styles')
<style>
@media print {
    /* Hide non-printable elements */
    .no-print, .no-print * {
        display: none !important;
    }
    
    /* Compact table styles */
    .table {
        font-size: 11px;
        margin-bottom: 0.5rem;
    }
    
    .table td, .table th {
        padding: 0.15rem 0.25rem;
    }
    
    /* Adjust page margins */
    @page {
        margin: 1cm;
        size: portrait;
    }
    
    /* Remove backgrounds and borders for better printing */
    .table-bordered td, .table-bordered th {
        border-width: 1px !important;
    }
    
    body {
        margin: 0;
        padding: 0;
    }
    
    h3 {
        font-size: 14px;
        margin: 0.5rem 0;
    }
    
    .badge {
        padding: 0.2rem 0.4rem;
        font-size: 10px;
    }
    
    small {
        font-size: 9px;
    }
    
    /* Force background colors in printing */
    .bg-light {
        background-color: #f8f9fa !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>
@endpush

@section('content')
<div class="card">
    <div class="card-header">
        <h2>Reports</h2>
    </div>
    <div class="card-body">
        <form method="GET" class="mb-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Select Report</label>
                    <select name="report" class="form-select" onchange="this.form.submit()">
                        <option value="">Choose Report</option>
                        
                        @if(auth()->user()->can('view-reports') || auth()->user()->can('daily-production-report'))
                        <option value="daily-production" {{ request('report') == 'daily-production' ? 'selected' : '' }}>
                            Daily Production Report
                        </option>
                        @endif
                        
                        @if(auth()->user()->can('view-reports') || auth()->user()->can('customer-stock-report'))
                        <option value="customer-stock" {{ request('report') == 'customer-stock' ? 'selected' : '' }}>
                            Customer Stock Report
                        </option>
                        @endif
                        
                        @if(auth()->user()->can('view-reports') || auth()->user()->can('total-stock-report'))
                        <option value="total-stock" {{ request('report') == 'total-stock' ? 'selected' : '' }}>
                            Total Stock Report
                        </option>
                        @endif
                        
                        @if(auth()->user()->can('view-reports') || auth()->user()->can('grade-wise-report'))
                        <option value="grade-wise" {{ request('report') == 'grade-wise' ? 'selected' : '' }}>
                            Grade-wise Report
                        </option>
                        @endif
                        
                        @if(auth()->user()->can('view-reports') || auth()->user()->can('product-wise-daily-report'))
                        <option value="product-wise-daily" {{ request('report') == 'product-wise-daily' ? 'selected' : '' }}>
                            Product-wise Daily Production
                        </option>
                        @endif
                    </select>
                </div>
                @if(request('report') == 'daily-production')
                    <div class="col-md-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" class="form-control" 
                               value="{{ request('date', now()->format('Y-m-d')) }}"
                               onchange="this.form.submit()">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label d-block">&nbsp;</label>
                        <button type="button" class="btn btn-secondary" onclick="window.print()">
                            Print Report
                        </button>
                    </div>
                @endif
                @if(request('report') == 'customer-stock')
                    <div class="col-md-4">
                        <label class="form-label">Customer</label>
                        <select name="customer" class="form-select" onchange="this.form.submit()">
                            <option value="">Select Customer</option>
                            @foreach($data['customers'] ?? [] as $customer)
                                <option value="{{ $customer->id }}" {{ request('customer') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label d-block">&nbsp;</label>
                        <button type="button" class="btn btn-secondary" onclick="window.print()">
                            Print Report
                        </button>
                    </div>
                @endif
                @if(request('report') == 'product-wise-daily')
                    <div class="col-md-3">
                        <label class="form-label">Date</label>
                        <input type="date" 
                               name="date" 
                               class="form-control" 
                               value="{{ request('date', now()->format('Y-m-d')) }}"
                               onchange="this.form.submit()">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label d-block">&nbsp;</label>
                        <button type="button" class="btn btn-secondary" onclick="window.print()">
                            Print Report
                        </button>
                    </div>
                @endif
            </div>
        </form>

        <div id="reportContent">
            @if($reportType)
                @include("reports._{$reportType}")
            @else
                <div class="text-center text-muted">
                    Please select a report from the dropdown above
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
