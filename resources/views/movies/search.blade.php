@extends('layouts.app')

{{-- SEO Meta Tags --}}
@php
    $seoTitle = "Search Results for \"{$query}\" - 123 Movies Pro";
    $seoDescription = "Search results for {$query} - Watch free HD movies and TV shows on 123 Movies Pro. Stream online with no signup required.";
    $seoKeywords = "123 movies pro, {$query}, search results, free streaming, watch online, HD movies";
@endphp

@section('seo_title', $seoTitle)
@section('seo_description', $seoDescription)
@section('seo_keywords', $seoKeywords)
@section('canonical_url', route('movies.search', ['query' => $query]))

@section('content')
<div class="container mt-4">
    <div class="search-header">
        <h1>Movie Search Results for "{{ $query }}" - 123 Movies Pro</h1>
        <form action="{{ route('movies.search') }}" method="get" class="mt-3" role="search" aria-label="Movie search">
            <div class="input-group">
                <input type="text" name="query" class="form-control search-input" value="{{ $query }}" placeholder="Search for movies..." aria-label="Search query">
                <button class="btn btn-primary search-btn" type="submit">Search</button>
            </div>
        </form>
    </div>

    @if(count($results) > 0)
        <section aria-label="Search results">
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                @foreach($results as $movie)
                    <div class="col">
                        <article class="movie-card h-100">
                            @php
                                // Ensure we have a valid movie ID
                                $movieId = isset($movie['id']) ? $movie['id'] : '';
                                // Make sure it's not empty
                                if (empty($movieId)) {
                                    continue;
                                }
                            @endphp
                            <a href="{{ route('movies.show', ['id' => $movieId]) }}" class="text-decoration-none">
                                @php
                                    $posterUrl = '';
                                    if(!empty($movie['poster_path']) && strpos($movie['poster_path'], 'http') === 0) {
                                        // OMDB full URL
                                        $posterUrl = $movie['poster_path'];
                                    } elseif(!empty($movie['poster_path'])) {
                                        // TMDB path
                                        $posterUrl = 'https://image.tmdb.org/t/p/w500' . $movie['poster_path'];
                                    } else {
                                        $posterUrl = '';
                                    }
                                @endphp
                                
                                @if(!empty($posterUrl))
                                    <img src="{{ $posterUrl }}" class="card-img-top" alt="{{ $movie['title'] }} poster - Watch on 123 Movies Pro" loading="lazy">
                                @else
                                    <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" aria-hidden="true">
                                        <span class="text-light"><i class="bi bi-film" style="font-size: 3rem;"></i></span>
                                    </div>
                                @endif
                                <div class="card-body">
                                    <h3 class="movie-title" title="{{ $movie['title'] }}">{{ $movie['title'] }}</h3>
                                    @if(isset($movie['release_date']) && !empty($movie['release_date']))
                                        <p class="movie-year">{{ substr($movie['release_date'], 0, 4) }}</p>
                                    @endif
                                    <p class="movie-rating">
                                        <i class="bi bi-star-fill me-1" aria-hidden="true"></i> 
                                        <span>{{ isset($movie['vote_average']) ? number_format($movie['vote_average'], 1) : 'N/A' }}/10</span>
                                    </p>
                                    <div class="movie-hover-info">
                                        <div class="hover-buttons">
                                            <a href="{{ route('movies.show', ['id' => $movieId]) }}" class="btn btn-sm btn-danger">
                                                <i class="bi bi-info-circle me-1" aria-hidden="true"></i> Details
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </article>
                    </div>
                @endforeach
            </div>
        </section>

        @if($total_pages > 1)
            <div class="d-flex justify-content-center mt-5">
                <nav aria-label="Search results pages">
                    <ul class="pagination">
                        @if($current_page > 1)
                            <li class="page-item">
                                <a class="page-link" href="{{ route('movies.search', ['query' => $query, 'page' => $current_page - 1]) }}" rel="prev" aria-label="Previous page">
                                    <i class="bi bi-chevron-left" aria-hidden="true"></i>
                                    <span class="visually-hidden">Previous page</span>
                                </a>
                            </li>
                        @endif
                        
                        @php
                            $startPage = max(1, $current_page - 2);
                            $endPage = min($startPage + 4, $total_pages);
                            if ($endPage - $startPage < 4) {
                                $startPage = max(1, $endPage - 4);
                            }
                        @endphp
                        
                        @for($i = $startPage; $i <= $endPage; $i++)
                            <li class="page-item {{ $i == $current_page ? 'active' : '' }}">
                                <a class="page-link" href="{{ route('movies.search', ['query' => $query, 'page' => $i]) }}" aria-label="Page {{ $i }}" {{ $i == $current_page ? 'aria-current="page"' : '' }}>{{ $i }}</a>
                            </li>
                        @endfor
                        
                        @if($current_page < $total_pages)
                            <li class="page-item">
                                <a class="page-link" href="{{ route('movies.search', ['query' => $query, 'page' => $current_page + 1]) }}" rel="next" aria-label="Next page">
                                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                                    <span class="visually-hidden">Next page</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </nav>
            </div>
        @endif
    @else
        <div class="alert" style="background-color: rgba(22, 22, 22, 0.7); color: white; border: 1px solid rgba(255, 255, 255, 0.1);" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-circle me-3" style="font-size: 2rem;" aria-hidden="true"></i>
                <div>
                    <h2 class="alert-heading h4">No Results Found</h2>
                    <p class="mb-0">We couldn't find any movies matching "{{ $query }}". Please try different keywords.</p>
                </div>
            </div>
        </div>
    @endif
</div>

@push('styles')
<style>
    .search-header h1 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 1.2rem;
    }
    
    .movie-card {
        position: relative;
        overflow: hidden;
    }
    
    .movie-year {
        color: var(--netflix-light-gray);
        font-size: 0.9rem;
        margin-top: -0.5rem;
        margin-bottom: 0.5rem;
    }
    
    .movie-hover-info {
        position: absolute;
        bottom: -50px;
        left: 0;
        right: 0;
        background: linear-gradient(to top, rgba(0,0,0,0.9), transparent);
        padding: 15px;
        transition: all 0.3s ease;
        opacity: 0;
    }
    
    .movie-card:hover .movie-hover-info {
        bottom: 0;
        opacity: 1;
    }
    
    .hover-buttons {
        display: flex;
        justify-content: center;
    }
    
    .hover-buttons .btn-danger {
        background-color: var(--netflix-red);
        border: none;
    }
    
    .hover-buttons .btn-danger:hover {
        background-color: #f40612;
    }
    
    /* Pagination */
    .pagination {
        margin-top: 2rem;
    }
    
    .page-link {
        background-color: var(--netflix-light-dark);
        border-color: #333;
        color: var(--netflix-text);
        padding: 0.5rem 0.8rem;
    }
    
    .page-item.active .page-link {
        background-color: var(--netflix-red);
        border-color: var(--netflix-red);
    }
    
    .page-link:hover {
        background-color: rgba(229, 9, 20, 0.7);
        color: white;
    }
    
    /* Make movie titles use h2 but maintain h5 styling */
    h2.movie-title {
        font-weight: 600;
        font-size: 1.1rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: white;
        margin: 0.5rem 0 0.25rem 0;
    }
	
	h3.movie-title {
        font-weight: 600;
        font-size: 1.1rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: white;
        margin-top: 0.5rem;
        margin-bottom: 0.25rem;
    }
    
    /* Make page main h1 more distinct */
    .search-header h1 {
        font-size: 2.2rem;
        font-weight: 700;
        margin-bottom: 1.2rem;
        color: white;
    }
    
    /* Visually hidden class for screen readers */
    .visually-hidden {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }
</style>
@endpush
@endsection