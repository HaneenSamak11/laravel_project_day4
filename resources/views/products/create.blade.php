@extends('layouts.app')
@section('title', 'Create Product')
@section('content')
<div class="card shadow-sm"><div class="card-body p-4">
    <h1 class="h3 mb-4">Create Product</h1>
    <form method="POST" action="{{ route('products.store') }}">@include('products._form')</form>
</div></div>
@endsection
