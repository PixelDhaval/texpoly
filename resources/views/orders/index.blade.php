@extends('labels.layout')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2>Orders</h2>
        <a href="{{ route('orders.create') }}" class="btn btn-primary">Create Order</a>
    </div>
    <div class="card-body">
        <form action="{{ route('orders.index') }}" method="GET" class="mb-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search by order no or customer" value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Order No</th>
                        <th>Customer</th>
                        <th>Order Date</th>
                        <th>Target Date</th>
                        <th>Status</th>
                        <th>Container No</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                    <tr>
                        <td>{{ $order->order_no }}</td>
                        <td>{{ $order->customer->name }}</td>
                        <td>{{ $order->order_date }}</td>
                        <td>{{ $order->target_date }}</td>
                        <td>{{ $order->status == 'delivered' ? 'Completed' : ucfirst($order->status) }}</td>
                        <td>{{ $order->container_no }}</td>
                        <td>
                            <a href="{{ route('orders.show', $order) }}" class="btn btn-primary btn-sm">View/Edit</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="mt-3">
            {{ $orders->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection
