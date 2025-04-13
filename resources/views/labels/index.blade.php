@extends('labels.layout')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2>Labels Management</h2>
                    <a class="btn btn-success" href="{{ route('labels.create') }}">Create New Label</a>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th>Name</th>
                            <th>Label Code</th>
                            <th width="280px">Action</th>
                        </tr>
                        @foreach ($labels as $label)
                        <tr>
                            <td>{{ $label->name }}</td>
                            <td>{!! $label->label_code !!}</td>
                            <td>
                                <a class="btn btn-sm btn-primary" href="{{ route('labels.edit', $label->id) }}">Edit</a>
                                <form action="{{ route('labels.destroy', $label->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection