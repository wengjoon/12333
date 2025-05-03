@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 min-h-screen flex flex-col items-center justify-center">
    <div class="netflix-overlay absolute inset-0 bg-gradient-to-b from-black/60 to-gray-900 z-0"></div>
    
    <div class="relative z-10 w-full max-w-4xl">
        <h1 class="text-5xl font-bold text-center mb-6">
            Welcome to <span class="text-red-600">MovieSearch</span>
        </h1>

        <p class="text-xl text-center text-gray-300 mb-8">
            Find information about your favorite movies
        </p>

        <form action="{{ route('search') }}" method="GET" class="flex max-w-xl mx-auto">
            <input
                type="text"
                name="query"
                placeholder="Search for a movie..."
                class="flex-1 py-3 px-4 bg-gray-800 border border-gray-700 text-white rounded-l-md focus:outline-none focus:ring-2 focus:ring-red-600"
            />
            <button
                type="submit"
                class="bg-red-600 hover:bg-red-700 text-white py-3 px-6 rounded-r-md font-bold uppercase transition duration-200"
            >
                Search
            </button>
        </form>
        
        <!-- Your info section can go here -->
        <div class="mt-12 p-6 bg-gray-800/80 rounded-lg">
            <h2 class="text-2xl font-bold mb-4 text-center">
                123 Movies - Stream Movies and TV Shows in HD for Free Online
            </h2>
            
            <!-- Rest of your text content... -->
        </div>
    </div>
</div>
@endsection