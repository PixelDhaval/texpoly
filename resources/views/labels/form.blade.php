@extends('labels.layout')
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h2>{{ isset($label) ? 'Edit Label' : 'Create Label' }}</h2>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ isset($label) ? route('labels.update', $label->id) : route('labels.store') }}">
                        @csrf
                        @if(isset($label))
                            @method('PUT')
                        @endif

                        <div class="form-group mb-3">
                            <label for="name">Name:</label>
                            <input type="text" class="form-control" name="name" value="{{ isset($label) ? $label->name : old('name') }}" required>
                        </div>

                        <div class="form-group mb-3">
                            <label for="label_code">Label Code:</label>
                            <textarea class="form-control" name="label_code" id="label_code">{{ isset($label) ? $label->label_code : old('label_code') }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Submit</button>
                        <a href="{{ route('labels.index') }}" class="btn btn-secondary">Back</a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    </script>
@endsection
