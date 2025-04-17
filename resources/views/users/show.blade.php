@extends('labels.layout')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">User Permissions - {{ $user->name }}</h4>
        <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm">Back to Users</a>
    </div>
    
    <div class="card-body">
        <!-- Current Permissions -->
        <div class="row mb-4">
            <div class="col-md-12">
                <h5 class="mb-3">Current Permissions</h5>
                
                @forelse($userPermissions->groupBy('group') as $group => $permissions)
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">{{ ucfirst($group) }}</h6>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                @foreach($permissions as $permission)
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        {{ $permission->display_name }}
                                        <form action="{{ route('users.permissions.remove', [$user, $permission]) }}" 
                                              method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="alert alert-info">No permissions assigned</div>
                @endforelse
            </div>
        </div>

        <!-- Add Permissions -->
        <div class="row">
            <div class="col-md-6">
                <h5 class="mb-3">Add Permissions</h5>
                
                <form action="{{ route('users.permissions.add', $user) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <select name="permission_id[]" class="form-select" multiple size="10">
                            @foreach($availablePermissions->groupBy('group') as $group => $permissions)
                                <optgroup label="{{ ucfirst($group) }}">
                                    @foreach($permissions as $permission)
                                        <option value="{{ $permission->id }}">
                                            {{ $permission->display_name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <div class="form-text">Hold Ctrl/Cmd to select multiple permissions</div>
                    </div>

                    <button type="submit" class="btn btn-primary">Add Selected Permissions</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
