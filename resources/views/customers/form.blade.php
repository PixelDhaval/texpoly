@extends('labels.layout')
@section('content')
    <div class="card">
        <div class="card-header">
            <h2>{{ isset($customer) ? 'Edit Customer' : 'Create Customer' }}</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ isset($customer) ? route('customers.update', $customer->id) : route('customers.store') }}">
                @csrf
                @if(isset($customer))
                    @method('PUT')
                @endif

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" 
                            value="{{ old('name', $customer->name ?? '') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" 
                            value="{{ old('email', $customer->email ?? '') }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" 
                            value="{{ old('phone', $customer->phone ?? '') }}">
                    </div>

                    <div class="col-md-6">
                        <label for="country" class="form-label">Country</label>
                        <input type="text" class="form-control" id="country" name="country" 
                            value="{{ old('country', $customer->country ?? '') }}">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="label_id" class="form-label">Label</label>
                        <select class="form-select" id="label_id" name="label_id">
                            <option value="">Select Label</option>
                            @foreach($labels as $label)
                                <option value="{{ $label->id }}" 
                                    {{ (old('label_id', $customer->label_id ?? '') == $label->id) ? 'selected' : '' }}>
                                    {{ $label->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="short_code" class="form-label">Short Code</label>
                        <input type="text" class="form-control" id="short_code" name="short_code" 
                            value="{{ old('short_code', $customer->short_code ?? '') }}">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="form-check">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" 
                                {{ old('is_active', $customer->is_active ?? 1) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Is Active</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input type="hidden" name="is_qr" value="0">
                            <input type="checkbox" class="form-check-input" id="is_qr" name="is_qr" value="1" 
                                {{ old('is_qr', $customer->is_qr ?? 1) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_qr">Show QR</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input type="hidden" name="is_bale_no" value="0">
                            <input type="checkbox" class="form-check-input" id="is_bale_no" name="is_bale_no" value="1" 
                                {{ old('is_bale_no', $customer->is_bale_no ?? 1) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_bale_no">Show Bale No</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input type="hidden" name="is_printed_by" value="0">
                            <input type="checkbox" class="form-check-input" id="is_printed_by" name="is_printed_by" value="1" 
                                {{ old('is_printed_by', $customer->is_printed_by ?? 1) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_printed_by">Show Printed By</label>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Save</button>
                        <a href="{{ route('customers.index') }}" class="btn btn-secondary">Back</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
