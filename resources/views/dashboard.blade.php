@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<h1 class="mb-4">Dashboard</h1>
<div class="row g-3">
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted">Products</div><div class="display-6">{{ $productCount }}</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted">Low stock</div><div class="display-6">{{ $lowStockCount }}</div></div></div></div>
    <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted">Inventory value</div><div class="h2">{{ number_format($inventoryValue, 2) }}</div></div></div></div>
    @if($userCount !== null)
        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body"><div class="text-muted">Users</div><div class="display-6">{{ $userCount }}</div></div></div></div>
    @endif
</div>
<div class="card shadow-sm mt-4">
    <div class="card-body">
        <h2 class="h4">Try the data chatbot</h2>
        <p class="mb-3">Examples: “How many products are out of stock?”, “What is the most expensive product?”, or “Show products below 1000.”</p>
        <a class="btn btn-primary" href="{{ route('chatbot.index') }}">Open chatbot</a>
    </div>
</div>
@endsection
