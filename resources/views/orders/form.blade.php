@extends('labels.layout')
@section('content')
<div class="card">
    <div class="card-header">
        <h2>Create Order</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('orders.store') }}">
            @csrf
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Customer</label>
                    <select name="customer_id" class="form-select" required>
                        <option value="">Select Customer</option>
                        @if(isset($customer))
                        <option value="{{ $customer->id }}" selected>{{ $customer->name }}</option>
                        @else
                        @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                        @endif
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="production">Production</option>
                        <option value="draft">Draft</option>
                        <option value="delivered">Completed</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Target Date</label>
                    <input type="date" name="target_date" class="form-control" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Container No</label>
                    <input type="text" name="container_no" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">SGS Seal No</label>
                    <input type="text" name="sgs_seal_no" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Line Seal No</label>
                    <input type="text" name="line_seal_no" class="form-control">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Note</label>
                    <textarea name="note" class="form-control" rows="3"></textarea>
                </div>
            </div>

            <div>
                <button type="submit" class="btn btn-primary">Create Order</button>
                <a href="{{ route('orders.index') }}" class="btn btn-secondary">Back</a>
            </div>
        </form>
    </div>
</div>
@endsection