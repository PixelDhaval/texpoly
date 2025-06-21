
@extends('labels.layout')
@section('content')
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2>Section-wise Production & Labour Report</h2>
        <form method="GET" class="d-flex align-items-center">
            <label class="me-2 mb-0">Date:</label>
            <input type="date" name="date" value="{{ $date }}" class="form-control form-control-sm me-2" style="width: 180px;">
            <button type="submit" class="btn btn-primary btn-sm">Show</button>
        </form>
    </div>
    <div class="card-body ">
            <table class="table table-bordered align-middle" style="width: fit-content;">
                <thead>
                    <tr>
                        <th>Section</th>
                        <th>Production Qty</th>
                        <th>Labour Count</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalQty = 0;
                        $totalLabour = 0;
                    @endphp
                    @foreach($sections as $section)
                        @php
                            $qty = $productionBySection[$section->id] ?? 0;
                            $labour = $labourBySection[$section->id] ?? 0;
                            $totalQty += $qty;
                            $totalLabour += $labour;
                        @endphp
                        <tr>
                            <td>{{ $section->name }}</td>
                            <td class="text-end">{{ $qty }}</td>
                            <td class="text-end">{{ $labour }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td>Total</td>
                        <td class="text-end">{{ $totalQty }}</td>
                        <td class="text-end">{{ $totalLabour }}</td>
                    </tr>
                </tfoot>
            </table>
        <div class="text-muted mt-2">
            Showing for date: <strong>{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</strong>
        </div>
    </div>
</div>
@endsection