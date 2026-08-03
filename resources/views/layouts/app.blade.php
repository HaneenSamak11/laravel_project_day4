<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Laravel Products')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f6f8fb; }
        .chat-box { height: 55vh; overflow-y: auto; background: white; }
        .chat-message { max-width: 78%; white-space: pre-wrap; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="{{ route('dashboard') }}">Products Project</a>
        @auth
            <div class="navbar-nav me-auto">
                <a class="nav-link" href="{{ route('dashboard') }}">Dashboard</a>
                <a class="nav-link" href="{{ route('products.index') }}">Products</a>
                <a class="nav-link" href="{{ route('chatbot.index') }}">Data Chatbot</a>
            </div>
            <div class="d-flex align-items-center gap-3 text-white">
                <span>{{ auth()->user()->name }} ({{ auth()->user()->role }})</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-outline-light btn-sm">Logout</button>
                </form>
            </div>
        @endauth
    </div>
</nav>

<main class="container py-4">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @yield('content')
</main>
</body>
</html>
