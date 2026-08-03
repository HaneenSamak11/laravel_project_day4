@extends('layouts.app')
@section('title', 'Register')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 mb-4">Register</h1>
                <form method="POST" action="{{ route('register.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input name="name" value="{{ old('name') }}" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm password</label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                    <button class="btn btn-primary w-100">Create account</button>
                </form>
                <p class="mt-3 mb-0">Already registered? <a href="{{ route('login') }}">Login</a></p>
            </div>
        </div>
    </div>
</div>
@endsection
