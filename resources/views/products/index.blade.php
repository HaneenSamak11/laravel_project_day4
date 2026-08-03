@extends('layouts.app')
@section('title', 'Products')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Products</h1>
    @if(auth()->user()->isAdmin())
        <a class="btn btn-primary" href="{{ route('products.create') }}">Add product</a>
    @endif
</div>
<form class="row g-2 mb-3">
    <div class="col-md-10"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search products"></div>
    <div class="col-md-2"><button class="btn btn-outline-primary w-100">Search</button></div>
</form>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>ID</th><th>Name</th><th>Price</th><th>Quantity</th><th></th></tr></thead>
            <tbody>
            @forelse($products as $product)
                <tr>
                    <td>{{ $product->id }}</td>
                    <td>{{ $product->name }}</td>
                    <td>{{ number_format((float) $product->price, 2) }}</td>
                    <td>{{ $product->quantity }}</td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="{{ route('products.show', $product) }}">View</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center py-4">No products found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $products->links() }}</div>
@endsection
