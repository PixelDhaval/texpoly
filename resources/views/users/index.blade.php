@extends('labels.layout')

@section('content')
<div class="card">
    <div class="card-header">
        <h4 class="mb-0">Users Management</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Permissions</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->permissions_count }}</td>
                        <td class="text-end">
                            <a href="{{ route('users.show', $user) }}" 
                               class="btn btn-sm btn-primary">
                                Manage Permissions
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
