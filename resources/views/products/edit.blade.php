@extends('layouts.app')
@section('title', 'Edit Product')
@section('content')
<div class="card shadow-sm"><div class="card-body p-4">
    <h1 class="h3 mb-4">Edit Product</h1>
    <form method="POST" action="{{ route('products.update', $product) }}">
        @method('PUT')
        @include('products._form')
    </form>
</div></div>
@endsection
