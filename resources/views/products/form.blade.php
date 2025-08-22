@extends('labels.layout')
@section('content')
<div class="card">
    <div class="card-header">
        <h2>{{ isset($product) ? 'Edit Product' : 'Create Product' }}</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ isset($product) ? route('products.update', $product->id) : route('products.store') }}">
            @csrf
            @if(isset($product))
                @method('PUT')
            @endif

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" 
                           value="{{ old('name', $product->name ?? '') }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-2">
                    <label for="short_code" class="form-label">Short Code</label>
                    <input type="text" class="form-control @error('short_code') is-invalid @enderror" 
                           id="short_code" name="short_code" value="{{ old('short_code', $product->short_code ?? '') }}">
                    @error('short_code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-2">
                    <label for="type" class="form-label">Type</label>
                    <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                        <option value="">Select Type</option>
                        <option value="bale" {{ old('type', $product->type ?? '') == 'bale' ? 'selected' : '' }}>Bale</option>
                        <option value="jumbo" {{ old('type', $product->type ?? '') == 'jumbo' ? 'selected' : '' }}>Jumbo</option>
                    </select>
                    @error('type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label for="label_name" class="form-label">Label Name</label>
                    <input type="text" class="form-control" id="label_name" name="label_name" 
                           value="{{ old('label_name', $product->label_name ?? '') }}">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="category_id" class="form-label">Category</label>
                    <select class="form-select @error('category_id') is-invalid @enderror" id="category_id" name="category_id" required>
                        <option value="">Select Category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" 
                                {{ old('category_id', $product->category_id ?? '') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4">
                    <label for="subcategory_id" class="form-label">Section</label>
                    <select class="form-select" id="subcategory_id" name="subcategory_id">
                        <option value="">Select Section</option>
                        @foreach($subcategories as $subcategory)
                            <option value="{{ $subcategory->id }}" 
                                {{ old('subcategory_id', $product->subcategory_id ?? '') == $subcategory->id ? 'selected' : '' }}>
                                {{ $subcategory->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="grade" class="form-label">Grade</label>
                    <select class="form-select" id="grade" name="grade">
                        <option value="">Select Grade</option>
                        @foreach(['A', 'B', 'C', 'W', 'A + B', 'B + C', 'Premium A'] as $grade)
                            <option value="{{ $grade }}" {{ old('grade', $product->grade ?? '') == $grade ? 'selected' : '' }}>
                                {{ $grade }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $product->description ?? '') }}</textarea>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="price" class="form-label">Price</label>
                    <input type="number" class="form-control @error('price') is-invalid @enderror" id="price" name="price" 
                           value="{{ old('price', $product->price ?? '') }}" step="0.01" required>
                </div>

                <div class="col-md-3">
                    <label for="quantity" class="form-label">Quantity</label>
                    <input type="number" class="form-control @error('quantity') is-invalid @enderror" id="quantity" name="quantity" 
                           value="{{ old('quantity', $product->quantity ?? '') }}" required>
                </div>
                <div class="col-md-3">
                    <label for="unit" class="form-label">Unit</label>
                    <select class="form-select" id="unit" name="unit">
                        <option value="">Select Unit</option>
                        @foreach(['KGS', 'LBS', 'PCS'] as $unit)
                            <option value="{{ $unit }}" {{ old('unit', $product->unit ?? '') == $unit ? 'selected' : '' }}>
                                {{ $unit }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="weight" class="form-label">Weight (in KGS)</label>
                    <input type="number" class="form-control @error('weight') is-invalid @enderror" id="weight" name="weight" 
                           value="{{ old('weight', $product->weight ?? '') }}" required>
                </div>
            </div>

            <div class="row mb-3">
                

                

                
            </div>

            <div class="row">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <a href="{{ route('products.index') }}" class="btn btn-secondary">Back</a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const nameInput = document.getElementById('name');
    const labelNameInput = document.getElementById('label_name');
    const shortCodeInput = document.getElementById('short_code');

    nameInput.addEventListener('input', function() {
        labelNameInput.value = this.value;
    });

    shortCodeInput.addEventListener('blur', async function() {
        if (this.value) {
            const response = await fetch(`/products/check-shortcode?code=${this.value}&id={{ $product->id ?? '' }}`);
            const data = await response.json();
            if (!data.available) {
                alert('This short code is already in use');
                this.value = '';
            }
        }
    });
});
</script>
@endsection
