@extends('layouts.app')

@section('title', 'AI Assistant Limit Settings')

@section('content')

<div class="container">
    <h1>AI Assistant Limit Settings</h1>
    <form action="{{ route('ai-assistant.save-limits') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="daily_limit">Daily Limit (tokens)</label>
            <input type="number" name="daily_limit" id="daily_limit" class="form-control" value="{{ old('daily_limit', $settings['daily_limit'] ?? '') }}">
        </div>
        <div class="form-group">
            <label for="monthly_limit">Monthly Limit (tokens)</label>
            <input type="number" name="monthly_limit" id="monthly_limit" class="form-control" value="{{ old('monthly_limit', $settings['monthly_limit'] ?? '') }}">
        </div>
        <button type="submit" class="btn btn-primary">Save Limits</button>
    </form>
</div>

@endsection