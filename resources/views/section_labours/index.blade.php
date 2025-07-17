@extends('labels.layout')
@section('content')
<div class="row">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <h2>{{ isset($editSectionLabour) ? 'Edit Section Labour' : 'Create Section Labour' }}</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ isset($editSectionLabour) ? route('section-labours.update', $editSectionLabour->id) : route('section-labours.store') }}">
                    @csrf
                    @if(isset($editSectionLabour))
                        @method('PUT')
                    @endif

                    <div class="mb-3">
                        <label for="subcategory_id" class="form-label">Section</label>
                        <select class="form-select @error('subcategory_id') is-invalid @enderror" id="subcategory_id" name="subcategory_id" required>
                            <option value="">Select Section</option>
                            @foreach($subcategories as $subcategory)
                                <option value="{{ $subcategory->id }}"
                                    {{ (isset($editSectionLabour) && $editSectionLabour->subcategory_id == $subcategory->id) || old('subcategory_id') == $subcategory->id ? 'selected' : '' }}>
                                    {{ $subcategory->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('subcategory_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="labour_count" class="form-label">Labour Count</label>
                        <input type="number" class="form-control @error('labour_count') is-invalid @enderror"
                               id="labour_count" name="labour_count"
                               value="{{ isset($editSectionLabour) ? $editSectionLabour->labour_count : old('labour_count') }}" required min="0">
                        @error('labour_count')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" class="form-control @error('date') is-invalid @enderror"
                               id="date" name="date"
                               value="{{ isset($editSectionLabour) ? $editSectionLabour->date : old('date', now()->format('Y-m-d')) }}" required>
                        @error('date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary">{{ isset($editSectionLabour) ? 'Update' : 'Create' }}</button>
                    @if(isset($editSectionLabour))
                        <a href="{{ route('section-labours.index') }}" class="btn btn-secondary">Cancel</a>
                    @endif
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2>Section Labours List</h2>
                <form method="GET" class="row row-cols-lg-auto g-2 align-items-center">
                    <div>
                        <select name="subcategory_id" class="form-select form-select-sm">
                            <option value="">All Sections</option>
                            @foreach($subcategories as $subcategory)
                                <option value="{{ $subcategory->id }}" {{ request('subcategory_id') == $subcategory->id ? 'selected' : '' }}>
                                    {{ $subcategory->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <input type="date" name="from_date" class="form-control form-control-sm"
                            value="{{ request('from_date', now()->format('Y-m-d')) }}">
                    </div>
                    <div>
                        <input type="date" name="to_date" class="form-control form-control-sm"
                            value="{{ request('to_date', now()->format('Y-m-d')) }}">
                    </div>
                    <div>
                        <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                        <a href="{{ route('section-labours.index') }}" class="btn btn-sm btn-secondary">Reset</a>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Section</th>
                            <th>Labour Count</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sectionLabours as $sectionLabour)
                        <tr class="{{ isset($editSectionLabour) && $editSectionLabour->id == $sectionLabour->id ? 'table-warning' : '' }}">
                            <td>{{ \Carbon\Carbon::parse($sectionLabour->date)->format('d/m/Y') }}</td>
                            <td>{{ $sectionLabour->subcategory->name ?? '-' }}</td>
                            <td>{{ $sectionLabour->labour_count }}</td>
                            <td>
                                <a href="{{ route('section-labours.edit', $sectionLabour->id) }}" 
                                   class="btn btn-primary btn-sm {{ isset($editSectionLabour) && $editSectionLabour->id == $sectionLabour->id ? 'active' : '' }}">
                                   {{ isset($editSectionLabour) && $editSectionLabour->id == $sectionLabour->id ? 'Editing' : 'Edit' }}
                                </a>
                                <form action="{{ route('section-labours.destroy', $sectionLabour->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div>
                    {{ $sectionLabours->appends(request()->except('page'))->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
