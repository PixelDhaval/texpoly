<form method="GET" class="mb-4">
    <input type="hidden" name="report" value="grade-wise">
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label">Date</label>
            <input type="date" name="date" class="form-control" 
                   value="{{ request('date', now()->format('Y-m-d')) }}" required>
        </div>
        <div class="col-md-2">
            <label class="form-label d-block">&nbsp;</label>
            <button type="submit" class="btn btn-primary">Generate Report</button>
        </div>
    </div>
</form>

@if(isset($data['gradeData']))
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Total Production by Grade</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Grade</th>
                                    <th>Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data['gradeData'] as $grade => $count)
                                <tr>
                                    <td>{{ $grade ?: 'No Grade' }}</td>
                                    <td>{{ $count }}</td>
                                </tr>
                                @endforeach
                                <tr class="fw-bold">
                                    <td>Total</td>
                                    <td>{{ $data['gradeData']->sum() }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Customer-wise Grade Distribution</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            @foreach($data['allGrades'] as $grade)
                                <th>{{ $grade }}</th>
                            @endforeach
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['customerGradeData'] as $customerId => $customer)
                            <tr>
                                <td>{{ $customer['name'] }}</td>
                                @foreach($data['allGrades'] as $grade)
                                    <td class="text-center">{{ $customer['grades'][$grade] ?? 0 }}</td>
                                @endforeach
                                <td class="fw-bold text-center">{{ $customer['total'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
