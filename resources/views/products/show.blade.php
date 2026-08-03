@extends('layouts.app')
@section('title', $product->name)
@section('content')
<div class="card shadow-sm"><div class="card-body p-4">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h1>{{ $product->name }}</h1>
            <p class="text-muted">Product #{{ $product->id }}</p>
        </div>
        @if(auth()->user()->isAdmin())
            <div class="d-flex gap-2">
                <a class="btn btn-primary" href="{{ route('products.edit', $product) }}">Edit</a>
                <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Delete this product?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger">Delete</button>
                </form>
            </div>
        @endif
    </div>
    <hr>
    <p>{{ $product->description ?: 'No description.' }}</p>
    <dl class="row mb-0">
        <dt class="col-sm-2">Price</dt><dd class="col-sm-10">{{ number_format((float) $product->price, 2) }}</dd>
        <dt class="col-sm-2">Quantity</dt><dd class="col-sm-10">{{ $product->quantity }}</dd>
    </dl>
</div></div>
@endsection
