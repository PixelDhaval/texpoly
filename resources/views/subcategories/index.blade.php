@extends('labels.layout')
@section('content')
<div class="row">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <h2>{{ isset($editSubcategory) ? 'Edit Section' : 'Create Section' }}</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ isset($editSubcategory) ? route('subcategories.update', $editSubcategory->id) : route('subcategories.store') }}">
                    @csrf
                    @if(isset($editSubcategory))
                        @method('PUT')
                    @endif

                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ isset($editSubcategory) ? $editSubcategory->name : old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary">{{ isset($editSubcategory) ? 'Update' : 'Create' }}</button>
                    @if(isset($editSubcategory))
                        <a href="{{ route('subcategories.index') }}" class="btn btn-secondary">Cancel</a>
                    @endif
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <h2>Sections List</h2>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subcategories as $subcategory)
                        <tr>
                            <td>{{ $subcategory->name }}</td>
                            <td>
                                <a href="{{ route('subcategories.edit', $subcategory->id) }}" class="btn btn-primary btn-sm">Edit</a>
                                <form action="{{ route('subcategories.destroy', $subcategory->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
