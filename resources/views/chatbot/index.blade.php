@extends('layouts.app')
@section('title', 'Data Chatbot')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="mb-1">Data Chatbot</h1>
        <p class="text-muted mb-0">Ask questions about products, stock, prices, and permitted project data.</p>
    </div>
    <form method="POST" action="{{ route('chatbot.clear') }}" onsubmit="return confirm('Clear chat history?')">
        @csrf @method('DELETE')
        <button class="btn btn-outline-danger">Clear history</button>
    </form>
</div>
<div class="chat-box border rounded p-3 mb-3 shadow-sm" id="chatBox">
    @forelse($messages as $message)
        <div class="d-flex mb-3 {{ $message->role === 'user' ? 'justify-content-end' : 'justify-content-start' }}">
            <div class="chat-message rounded p-3 {{ $message->role === 'user' ? 'bg-primary text-white' : 'bg-light border' }}">{{ $message->content }}</div>
        </div>
    @empty
        <div class="text-center text-muted py-5">No messages yet. Ask: “What is the total inventory value?”</div>
    @endforelse
</div>
<form method="POST" action="{{ route('chatbot.ask') }}" class="d-flex gap-2">
    @csrf
    <input name="message" class="form-control form-control-lg" placeholder="Ask about project data..." maxlength="2000" required autofocus>
    <button class="btn btn-primary px-4">Send</button>
</form>
<script>const box = document.getElementById('chatBox'); box.scrollTop = box.scrollHeight;</script>
@endsection
