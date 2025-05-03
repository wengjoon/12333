@extends('layouts.app')

@section('seo_title', 'Server Error - 123 Movies Pro')
@section('seo_description', 'We encountered a server error. Please try again later and continue enjoying our free movies and TV shows.')

@section('content')
<div class="container error-container py-5">
    <div class="row">
        <div class="col-md-10 col-lg-8 mx-auto text-center mb-5">
            <i class="bi bi-exclamation-triangle text-warning error-icon"></i>
            <h1 class="display-1 fw-bold text-warning mb-4">500</h1>
            <h2 class="fw-light mb-4">Server Error</h2>
            <p class="lead">Oops! Something went wrong on our servers. We're working on fixing the issue.</p>
            <div class="mt-4">
                <a href="{{ route('movies.index') }}" class="btn btn-danger btn-lg px-4 me-2">
                    <i class="bi bi-house-door-fill me-2"></i> Back to Home
                </a>
                <button onclick="window.location.reload()" class="btn btn-outline-light btn-lg px-4">
                    <i class="bi bi-arrow-clockwise me-2"></i> Try Again
                </button>
            </div>
        </div>
    </div>
    
    <div class="row mt-5">
        <div class="col-12">
            <h3 class="text-center mb-4">While You Wait, Check Out These Movies</h3>
            <div class="row mt-4 g-4 top-rated-movies">
                {{-- Loading spinner while fetching movies --}}
                <div class="col-12 text-center loading-spinner">
                    <div class="spinner-border text-warning" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                
                {{-- Movies will be dynamically loaded here --}}
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .error-container {
        min-height: 70vh;
        padding-top: 3rem;
    }
    
    .error-icon {
        font-size: 5rem;
        margin-bottom: 1rem;
    }
    
    .movie-suggestion-card {
        background-color: var(--netflix-light-dark);
        border-radius: 4px;
        overflow: hidden;
        transition: all 0.3s ease;
        height: 100%;
    }
    
    .movie-suggestion-card:hover {
        transform: scale(1.05);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.4);
        z-index: 1;
    }
    
    .movie-suggestion-img {
        height: 280px;
        object-fit: cover;
    }
    
    .movie-suggestion-title {
        font-weight: 500;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .movie-suggestion-rating {
        color: #FFC107;
        font-weight: 600;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Fetch top rated movies from TMDB
        fetchTopRatedMovies();
    });
    
    async function fetchTopRatedMovies() {
        try {
            // Fetch top rated movies data
            const response = await fetch('/api/movies/top-rated');
            const data = await response.json();
            
            // If successful, render movies
            if (data && data.length > 0) {
                renderMovies(data);
            } else {
                showFallbackMovies();
            }
        } catch (error) {
            console.error('Error fetching movies:', error);
            showFallbackMovies();
        }
    }
    
    function renderMovies(movies) {
        const container = document.querySelector('.top-rated-movies');
        // Hide loading spinner
        document.querySelector('.loading-spinner').style.display = 'none';
        
        // Only use the first 4 movies
        const moviesToShow = movies.slice(0, 4);
        
        let html = '';
        moviesToShow.forEach(movie => {
            const posterUrl = movie.poster_path 
                ? `https://image.tmdb.org/t/p/w500${movie.poster_path}` 
                : '/images/no-poster.jpg';
            
            html += `
                <div class="col-6 col-md-3">
                    <a href="/movie/${movie.id}" class="text-decoration-none">
                        <div class="movie-suggestion-card">
                            <img src="${posterUrl}" class="w-100 movie-suggestion-img" alt="${movie.title}" loading="lazy">
                            <div class="p-3">
                                <h5 class="movie-suggestion-title">${movie.title}</h5>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">${movie.release_date ? movie.release_date.substr(0, 4) : ''}</span>
                                    <span class="movie-suggestion-rating">
                                        <i class="bi bi-star-fill me-1"></i>${movie.vote_average.toFixed(1)}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            `;
        });
        
        container.innerHTML = html;
    }
    
    function showFallbackMovies() {
        // Hide loading spinner
        document.querySelector('.loading-spinner').style.display = 'none';
        
        // Fallback movie data in case API fails
        const fallbackMovies = [
            {
                id: '278',
                title: 'The Shawshank Redemption',
                poster_path: '/q6y0Go1tsGEsmtFryDOJo3dEmqu.jpg',
                release_date: '1994-09-23',
                vote_average: 8.7
            },
            {
                id: '238',
                title: 'The Godfather',
                poster_path: '/3bhkrj58Vtu7enYsRolD1fZdja1.jpg',
                release_date: '1972-03-14',
                vote_average: 8.7
            },
            {
                id: '240',
                title: 'The Godfather Part II',
                poster_path: '/hek3koDUyRQk7FIhPXsa6mT2Zc3.jpg',
                release_date: '1974-12-20',
                vote_average: 8.6
            },
            {
                id: '424',
                title: "Schindler's List",
                poster_path: '/sF1U4EUQS8YHUYjNl3pMGNIQyr0.jpg',
                release_date: '1993-12-15',
                vote_average: 8.6
            }
        ];
        
        renderMovies(fallbackMovies);
    }
</script>
@endpush
@endsection