@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card bg-dark text-white">
                <div class="card-header bg-danger">Rate Limit Exceeded</div>
                <div class="card-body text-center">
                    <h3 class="card-title mb-4">Searching Too Quickly</h3>
                    <p class="card-text">{{ $message ?? 'You are making too many requests. Please slow down.' }}</p>
                    <div class="mt-4">
                        <a href="{{ route('movies.index') }}" class="btn btn-primary me-2">Home</a>
                        <button onclick="window.history.back()" class="btn btn-secondary">Go Back</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection