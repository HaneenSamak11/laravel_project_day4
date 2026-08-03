@csrf
<div class="mb-3">
    <label class="form-label">Name</label>
    <input name="name" class="form-control" value="{{ old('name', $product->name ?? '') }}" required>
</div>
<div class="mb-3">
    <label class="form-label">Description</label>
    <textarea name="description" class="form-control" rows="4">{{ old('description', $product->description ?? '') }}</textarea>
</div>
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Price</label>
        <input type="number" step="0.01" min="0" name="price" class="form-control" value="{{ old('price', $product->price ?? '') }}" required>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Quantity</label>
        <input type="number" min="0" name="quantity" class="form-control" value="{{ old('quantity', $product->quantity ?? 0) }}" required>
    </div>
</div>
<button class="btn btn-primary">Save</button>
<a class="btn btn-outline-secondary" href="{{ route('products.index') }}">Cancel</a>
