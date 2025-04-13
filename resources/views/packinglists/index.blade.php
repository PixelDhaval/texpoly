@extends('labels.layout')
@section('content')
<div class="card">
    <div class="card-header">
        <h2>Packing Lists</h2>
    </div>
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Customer Name</th>
                    <th>Email</th>
                    <th>Country</th>
                    <th>Total Items</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($customers as $customer)
                <tr>
                    <td>{{ $customer->name }}</td>
                    <td>{{ $customer->email }}</td>
                    <td>{{ $customer->country }}</td>
                    <td>{{ $customer->packinglists_count }}</td>
                    <td>
                        <a href="{{ route('packinglists.show', $customer->id) }}" 
                           class="btn btn-primary btn-sm">View Packing List</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
