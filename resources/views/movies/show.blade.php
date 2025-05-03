@extends('layouts.app')

{{-- Add basic error checking --}}
@php
    // Check if we have a valid movie object
    $hasValidMovie = isset($movie) && !empty($movie) && isset($movie['title']);
    
    // Set default values for SEO & essential props
    $movieTitle = $hasValidMovie ? $movie['title'] : 'Movie Not Found';
    $movieYear = $hasValidMovie && isset($movie['release_date']) ? substr($movie['release_date'], 0, 4) : '';
    $moviePoster = $hasValidMovie && isset($movie['poster_path']) ? $movie['poster_path'] : null;
    $movieOverview = $hasValidMovie && isset($movie['overview']) ? $movie['overview'] : 'No description available';
    
    // SEO metadata
    $seoTitle = "{$movieTitle} ({$movieYear}) - Watch Online on 123 Movies Pro";
    $seoDescription = $movieOverview;
    $seoKeywords = "watch {$movieTitle}, {$movieTitle} online, {$movieTitle} free, {$movieTitle} movie, stream {$movieTitle}";
@endphp

{{-- SEO Meta Tags --}}
@section('seo_title', $seoTitle)
@section('seo_description', $seoDescription)
@section('seo_keywords', $seoKeywords)
@section('canonical_url', route('movies.show', ['id' => $movie['id'] ?? 'not-found']))

@section('content')
<div class="container mt-4">
    {{-- Show error message if invalid movie --}}
    @if(!$hasValidMovie)
        <div class="alert alert-danger">
            <h2>Movie Not Found</h2>
            <p>We couldn't load the movie details. Please try again or search for a different movie.</p>
            <a href="{{ route('movies.index') }}" class="btn btn-primary mt-3">Return to Home</a>
        </div>
    @else
    {{-- Continue with existing template for valid movies --}}

    {{-- Original content starts here --}}
@if(!isset($movie['title']))
    @php abort(404) @endphp
@endif
{{-- SEO Meta Tags: Dynamic for movie details with movie title first --}}
@php
    $movieTitle = $movie['title'] ?? 'Movie';
    $releaseYear = isset($movie['release_date']) ? ' (' . substr($movie['release_date'], 0, 4) . ')' : '';
    
    // Title format: "Movie Title (Year)" - site name at the end
    $seoTitle = $movieTitle . $releaseYear . ' -  Watch Movie For Free ';
    
    // Description focusing on the movie first
    $overview = isset($movie['overview']) ? str_replace('"', "'", mb_substr($movie['overview'], 0, 155)) . '...' : '';
    $seoDescription = "{$movieTitle}{$releaseYear}: {$overview} Watch free on 123 Movies Pro.";
    
    $genres = [];
    if(isset($movie['genres'])) {
        foreach($movie['genres'] as $genre) {
            if(is_array($genre)) {
                $genres[] = $genre['name'];
            } else {
                $genres[] = $genre;
            }
        }
    }
    
    // Keywords with movie title first
    $keywords = "{$movieTitle}, " . implode(', ', $genres) . ", 123 movies pro, watch free, stream online, HD quality";
    
    // Handle the poster path for OMDB or TMDB
    $posterImg = '';
    if(isset($movie['poster_path']) && strpos($movie['poster_path'], 'http') === 0) {
        // OMDB full URL
        $posterImg = $movie['poster_path'];
    } elseif(isset($movie['poster_path'])) {
        // TMDB path
        $posterImg = 'https://image.tmdb.org/t/p/w500' . $movie['poster_path'];
    } elseif(isset($movie['Poster']) && $movie['Poster'] !== 'N/A') {
        // OMDB Poster field
        $posterImg = $movie['Poster'];
    } else {
        // Default image
        $posterImg = asset(config('seo.default_og_image'));
    }
    
    // Use the poster as backdrop if no backdrop is available
    $backdropImg = $posterImg;
    if(!empty($movie['backdrop_path'])) {
        $backdropImg = 'https://image.tmdb.org/t/p/original' . $movie['backdrop_path'];
    }
    
    // Rating values for schema markup
    $ratingValue = number_format($movie['vote_average'] ?? $movie['imdbRating'] ?? 0, 1);
    $ratingCount = $movie['vote_count'] ?? (isset($movie['imdbVotes']) ? str_replace(',', '', $movie['imdbVotes']) : 0);
@endphp

@section('seo_title', $seoTitle)
@section('seo_description', $seoDescription)
@section('seo_keywords', $keywords)
@section('og_type', 'video.movie')
@section('og_image', $posterImg)
@section('twitter_image', $posterImg)

{{-- Structured data for movie with enhanced AggregateRating --}}
@section('structured_data')
<script type="application/ld+json">
{!! json_encode(\App\Helpers\SeoHelper::movieSchema($movie), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>

{{-- Additionally adding review schema if reviews exist --}}
@if(isset($movie['reviews']) && count($movie['reviews']) > 0)
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Movie",
  "name": "{{ $movie['title'] ?? '' }}",
  "review": [
    @foreach($movie['reviews'] as $index => $review)
    {
      "@type": "Review",
      "reviewBody": "{{ str_replace('"', "'", $review['content']) ?? '' }}",
      "author": {
        "@type": "Person",
        "name": "{{ $review['author'] ?? 'Anonymous Reviewer' }}"
      },
      "reviewRating": {
        "@type": "Rating",
        "ratingValue": "{{ $review['rating'] ?? $movie['vote_average'] ?? $movie['imdbRating'] ?? 5 }}",
        "bestRating": "10",
        "worstRating": "0"
      },
      "datePublished": "{{ $review['created_at'] ?? date('Y-m-d') }}"
    }@if($index < count($movie['reviews']) - 1),@endif
    @endforeach
  ]
}
</script>
@endif
@endsection

{{-- Remove the redundant @section('content') directive and replace with a comment --}}
{{-- Original content from the second template section continues here --}}
<title>{{ $movieTitle }}{{ $releaseYear }}</title>

{{-- Breadcrumbs navigation --}}
<div class="container">
    <nav aria-label="breadcrumb" class="mt-2">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('movies.index') }}">Home</a></li>
            @if(isset($movie['genres']) && count($movie['genres']) > 0)
                <li class="breadcrumb-item">
                    <a href="{{ route('movies.search', ['query' => is_array($movie['genres'][0]) ? $movie['genres'][0]['name'] : $movie['genres'][0] ]) }}">
                        {{ is_array($movie['genres'][0]) ? $movie['genres'][0]['name'] : $movie['genres'][0] }}
                    </a>
                </li>
            @endif
            <li class="breadcrumb-item active" aria-current="page">{{ $movie['title'] }}</li>
        </ol>
    </nav>
</div>

<!-- Hero Section with Netflix-style Backdrop using the Poster -->
<div class="movie-hero" style="background-image: url('{{ $backdropImg }}');" aria-labelledby="movie-title">
    <div class="backdrop-overlay" aria-hidden="true"></div>
    <div class="container hero-content">
        <div class="row">
            <div class="col-md-8">
                <h1 id="movie-title" class="movie-hero-title">{{ $movie['title'] }}</h1>
                
                <div class="movie-meta mb-3">
                    @if(isset($movie['release_date']) && !empty($movie['release_date']))
                        <span class="movie-year">{{ \Carbon\Carbon::parse($movie['release_date'])->format('Y') }}</span>
                    @elseif(isset($movie['Year']) && !empty($movie['Year']))
                        <span class="movie-year">{{ $movie['Year'] }}</span>
                    @endif
                    
                    @if(isset($movie['runtime']) && $movie['runtime'] > 0)
                        <span class="movie-runtime">{{ floor($movie['runtime'] / 60) }}h {{ $movie['runtime'] % 60 }}m</span>
                    @elseif(isset($movie['Runtime']) && $movie['Runtime'] !== 'N/A')
                        <span class="movie-runtime">{{ $movie['Runtime'] }}</span>
                    @endif
                    
                    <span class="movie-rating-badge">
                        <i class="bi bi-star-fill me-1" aria-hidden="true"></i> <span>{{ $ratingValue }}</span>
                    </span>
                </div>
                
                @if(isset($movie['tagline']) && !empty($movie['tagline']))
                    <p class="movie-tagline">{{ $movie['tagline'] }}</p>
                @endif
                
                <div class="movie-overview-hero mb-4">
                    @if(isset($movie['overview']) && !empty($movie['overview']))
                        {{ $movie['overview'] }}
                    @elseif(isset($movie['Plot']) && $movie['Plot'] !== 'N/A')
                        {{ $movie['Plot'] }}
                    @else
                        No description available.
                    @endif
                </div>
                
                <div class="movie-credits-hero mb-4">
                    @if(!empty($movie['directors']))
                        <div class="credit-row">
                            <span class="credit-title">Director{{ count($movie['directors']) > 1 ? 's' : '' }}:</span>
                            <span class="credit-people">{{ implode(', ', $movie['directors']) }}</span>
                        </div>
                    @elseif(isset($movie['Director']) && $movie['Director'] !== 'N/A')
                        <div class="credit-row">
                            <span class="credit-title">Director{{ strpos($movie['Director'], ',') !== false ? 's' : '' }}:</span>
                            <span class="credit-people">{{ $movie['Director'] }}</span>
                        </div>
                    @endif
                    
                    @if(!empty($movie['top_cast']))
                        <div class="credit-row">
                            <span class="credit-title">Starring:</span>
                            <span class="credit-people">{{ implode(', ', $movie['top_cast']) }}</span>
                        </div>
                    @elseif(isset($movie['Actors']) && $movie['Actors'] !== 'N/A')
                        <div class="credit-row">
                            <span class="credit-title">Starring:</span>
                            <span class="credit-people">{{ $movie['Actors'] }}</span>
                        </div>
                    @endif
                </div>
                
                
                <div class="hero-buttons">
                @if(config('site.show_watch_button', true))
                    <button id="playTrailerBtn" class="btn btn-danger btn-lg me-2" aria-label="Watch {{ $movie['title'] }}">
                        <i class="bi bi-play-fill me-2" aria-hidden="true"></i> Watch Now
                    </button>
                @endif
                    
                    <a href="#" class="btn btn-outline-light btn-lg" aria-label="Add {{ $movie['title'] }} to my list">
                        <i class="bi bi-plus-lg me-2" aria-hidden="true"></i> Add to List
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Movie Details Section -->
<div class="container movie-details">
    <div class="row">
        <!-- Left Column - Poster and Ratings -->
        <div class="col-md-4 mb-4">
            <div class="detail-poster-container">
                <!-- Display poster image -->
                <img src="{{ $posterImg }}" class="img-fluid movie-poster" alt="{{ $movie['title'] }} movie poster" loading="lazy">
                
                <!-- Aggregate rating display -->
                <div class="rating-container mt-3">
                    <div class="aggregate-rating" itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">
                        <meta itemprop="worstRating" content="0">
                        <meta itemprop="bestRating" content="10">
                        <meta itemprop="ratingValue" content="{{ $ratingValue }}">
                        <meta itemprop="ratingCount" content="{{ $ratingCount }}">
                        <meta itemprop="reviewCount" content="{{ $ratingCount }}">
                        
                        <div class="rating-display text-center">
                            <div class="rating-stars" aria-label="Rating: {{ $ratingValue }} out of 10">
                                @for ($i = 1; $i <= 5; $i++)
                                    @if ($i <= round($ratingValue / 2))
                                        <i class="bi bi-star-fill text-warning" aria-hidden="true"></i>
                                    @elseif ($i - 0.5 <= $ratingValue / 2)
                                        <i class="bi bi-star-half text-warning" aria-hidden="true"></i>
                                    @else
                                        <i class="bi bi-star text-warning" aria-hidden="true"></i>
                                    @endif
                                @endfor
                            </div>
                            <div class="rating-value fs-4 fw-bold">
                                {{ $ratingValue }}/10
                            </div>
                            <div class="rating-count text-white">
                                Based on {{ number_format($ratingCount) }} ratings
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- OMDB Ratings Section -->
                @if(isset($movie['ratings']) && is_array($movie['ratings']) && count($movie['ratings']) > 0)
                <div class="mt-4 ratings-section">
                    <h2 class="mb-3">Ratings</h2>
                    @foreach($movie['ratings'] as $rating)
                        <div class="rating-card p-3 text-center mb-3">
                            <h3 class="mb-2">{{ $rating['Source'] }}</h3>
                            <div class="rating-value">{{ $rating['Value'] }}</div>
                        </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        <!-- Right Column - Details and Info -->
        <div class="col-md-8">
            <!-- Genre badges -->
            <h2 class="section-title" id="genres-heading">Genres</h2>
            <div class="movie-genres mb-4" aria-labelledby="genres-heading">
                @if(isset($movie['genres']) && count($movie['genres']) > 0)
                    @foreach($movie['genres'] as $genre)
                        <a href="{{ route('movies.search', ['query' => is_array($genre) ? $genre['name'] : $genre]) }}" class="genre-badge">
                            {{ is_array($genre) ? $genre['name'] : $genre }}
                        </a>
                    @endforeach
                @elseif(isset($movie['Genre']) && $movie['Genre'] !== 'N/A')
                    @foreach(explode(', ', $movie['Genre']) as $genre)
                        <a href="{{ route('movies.search', ['query' => $genre]) }}" class="genre-badge">{{ $genre }}</a>
                    @endforeach
                @endif
            </div>
            
            <!-- Additional Writer info -->
            @if(isset($movie['Writer']) && $movie['Writer'] !== 'N/A')
                <h2 class="section-title" id="writers-heading">Writers & Screenplay</h2>
                <div class="info-section mb-4" aria-labelledby="writers-heading">
                    <p class="mb-0">{{ $movie['Writer'] }}</p>
                </div>
            @endif
            
            <!-- Additional Movie Info Sections -->
            <h2 class="section-title" id="details-heading">{{ $movie['title'] }} Production Details</h2>
            <div class="info-section mb-4" aria-labelledby="details-heading">
                <div class="row">
                    @if(isset($movie['Production']) && $movie['Production'] !== 'N/A')
                    <div class="col-md-6 mb-3">
                        <div class="info-item">
    <div class="info-label">Production</div>
    <p class="mb-0">{{ $movie['Production'] }}</p>
</div>
                    </div>
                    @endif
                    
                    @if(isset($movie['Country']) && $movie['Country'] !== 'N/A')
                    <div class="col-md-6 mb-3">
                        <div class="info-item">
                            <div class="info-label">Country</div>
                            <p class="mb-0">{{ $movie['Country'] }}</p>
                        </div>
                    </div>
                    @endif
                    
                    @if(isset($movie['Language']) && $movie['Language'] !== 'N/A')
                    <div class="col-md-6 mb-3">
                        <div class="info-item">
							<div class="info-label">Language</div>
                            <p class="mb-0">{{ $movie['Language'] }}</p>
                        </div>
                    </div>
                    @endif
                    
                    @if(isset($movie['BoxOffice']) && $movie['BoxOffice'] !== 'N/A')
                    <div class="col-md-6 mb-3">
                        <div class="info-item">
                            <div class="info-label">Box Office</div>
                            <p class="mb-0">{{ $movie['BoxOffice'] }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            
            @if(isset($movie['Awards']) && $movie['Awards'] !== 'N/A')
            <h2 class="section-title" id="awards-heading">{{ $movie['title'] }} Awards & Recognition</h2>
            <div class="info-section mb-4" aria-labelledby="awards-heading">
                <p class="mb-0">{{ $movie['Awards'] }}</p>
            </div>
            @endif
            
            <!-- Social Sharing Buttons -->
            <h2 class="section-title" id="share-heading">Share {{ $movie['title'] }} with Friends</h2>
            <div class="social-share mb-4" aria-labelledby="share-heading">
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" class="btn btn-outline-primary me-2" aria-label="Share on Facebook">
                    <i class="bi bi-facebook" aria-hidden="true"></i> Facebook
                </a>
                <a href="https://twitter.com/intent/tweet?text=Watch {{ urlencode($movie['title']) }}&url={{ urlencode(url()->current()) }}" target="_blank" class="btn btn-outline-info me-2" aria-label="Share on Twitter">
                    <i class="bi bi-twitter" aria-hidden="true"></i> Twitter
                </a>
                <a href="https://wa.me/?text=Watch {{ urlencode($movie['title']) }}: {{ urlencode(url()->current()) }}" target="_blank" class="btn btn-outline-success" aria-label="Share on WhatsApp">
                    <i class="bi bi-whatsapp" aria-hidden="true"></i> WhatsApp
                </a>
            </div>
        </div>
    </div>
    
    <!-- User reviews section if available -->
    @if(isset($movie['reviews']) && count($movie['reviews']) > 0)
    <div class="row mt-5">
        <div class="col-12">
            <h2 class="section-title-large mb-4" id="reviews-heading">User Reviews</h2>
            
            <div class="reviews-container" aria-labelledby="reviews-heading">
                @foreach($movie['reviews'] as $review)
                <div class="review-card mb-4" itemprop="review" itemscope itemtype="https://schema.org/Review">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <span itemprop="author" itemscope itemtype="https://schema.org/Person">
                                    <span itemprop="name" class="fw-bold">{{ $review['author'] ?? 'Anonymous' }}</span>
                                </span>
                                <meta itemprop="datePublished" content="{{ $review['created_at'] ?? date('Y-m-d') }}">
                                <span class="text-muted ms-2">{{ isset($review['created_at']) ? \Carbon\Carbon::parse($review['created_at'])->format('M d, Y') : date('M d, Y') }}</span>
                            </div>
                            <div class="review-rating" itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating">
                                <meta itemprop="worstRating" content="0">
                                <meta itemprop="bestRating" content="10">
                                <meta itemprop="ratingValue" content="{{ $review['rating'] ?? $movie['vote_average'] ?? $movie['imdbRating'] ?? 5 }}">
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-star-fill me-1" aria-hidden="true"></i>
                                    {{ number_format($review['rating'] ?? $movie['vote_average'] ?? $movie['imdbRating'] ?? 5, 1) }}/10
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <p itemprop="reviewBody" class="mb-0">{{ $review['content'] ?? 'No content available.' }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Trailer Modal -->
<div class="modal fade" id="trailerModal" tabindex="-1" aria-labelledby="trailerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
<h2 class="modal-title" id="trailerModalLabel">{{ $movie['title'] }} - Official Trailer</h2>
				<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="ratio ratio-16x9">
                    <iframe id="trailerIframe" src="about:blank" allowfullscreen title="{{ $movie['title'] }} trailer"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Offer Modal -->
<div class="modal fade" id="offerModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="offerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content offer-modal-content">
            <div class="modal-header offer-modal-header">
                <h2 class="modal-title" id="offerModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2 text-warning" aria-hidden="true"></i>
                    {{ config('site.offer_title', 'Security Warning!') }}
                </h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="display: none;"></button>
            </div>
            <div class="modal-body p-4">
                <p class="offer-text">{{ config('site.offer_text', 'Your connection is not secure. Streaming content without protection puts your data at risk.') }}</p>
                <div class="d-grid gap-2 mt-4">
                    <a href="{{ config('site.offer_url', '#') }}" class="btn btn-warning btn-lg offer-cta-button" target="_blank">
                        {{ config('site.offer_button_text', 'Protect My Privacy Now') }}
                    </a>
                    <button type="button" id="skipOfferButton" class="btn btn-outline-light btn-lg mt-2" disabled aria-label="Skip offer">
                        {{ config('site.offer_skip_text', 'Skip (Available in %s seconds)') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

@push('styles')
<style>
/* Offer Modal Styling */
    .offer-modal-content {
        background-color: #1a1a1a;
        border: 1px solid rgba(255, 193, 7, 0.3);
        box-shadow: 0 0 20px rgba(255, 193, 7, 0.2);
    }
    
    .offer-modal-header {
        background-color: #111;
        border-bottom: 1px solid rgba(255, 193, 7, 0.2);
    }
    
    .offer-text {
        font-size: 1.1rem;
        line-height: 1.6;
        color: #e5e5e5;
    }
    
    .offer-cta-button {
        background: linear-gradient(to right, #ffc107, #ff9800);
        border: none;
        color: #000;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
        padding: 12px 24px;
    }
    
    .offer-cta-button:hover {
        background: linear-gradient(to right, #ff9800, #ffc107);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255, 152, 0, 0.3);
        color: #000;
    }
    
    #skipOfferButton {
        border: 1px solid rgba(255, 255, 255, 0.3);
        transition: all 0.3s ease;
    }
    
    #skipOfferButton:not([disabled]):hover {
        background-color: rgba(255, 255, 255, 0.1);
    }
    
    #skipOfferButton[disabled] {
        opacity: 0.6;
        cursor: not-allowed;
    }
    /* Hero Section - Netflix Style */
    .movie-hero {
        position: relative;
        min-height: 80vh;
        background-size: cover;
        background-position: center;
        margin-top: -24px;
        color: white;
        display: flex;
        align-items: center;
    }
    
    .backdrop-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(to right, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.7) 30%, rgba(0,0,0,0.4) 60%, rgba(0,0,0,0.2) 100%),
                    linear-gradient(to top, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.6) 20%, rgba(0,0,0,0.4) 40%, rgba(0,0,0,0) 60%);
        z-index: 1;
    }
    
    .hero-content {
        position: relative;
        z-index: 2;
        padding: 3rem 0;
    }
    
    .movie-hero-title {
        font-size: 3.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
    }
    
    .movie-overview-hero {
        font-size: 1.1rem;
        max-width: 700px;
        line-height: 1.6;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.8);
    }
    
    .movie-tagline {
        font-size: 1.4rem;
        font-style: italic;
        margin-bottom: 1.5rem;
        color: #e5e5e5;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.8);
    }
    
    .hero-buttons .btn {
        padding: 0.75rem 1.5rem;
        font-weight: 600;
    }
    
    .hero-buttons .btn-danger {
        background-color: var(--netflix-red);
        border: none;
    }
    
    .movie-credits-hero .credit-row {
        margin-bottom: 0.5rem;
    }
	
	.info-label {
    color: #aaa;
    font-size: 0.9rem;
    margin-bottom: 0.3rem;
    font-weight: 600;
}
    
    .movie-credits-hero .credit-title {
        color: #ccc;
        margin-right: 0.5rem;
    }
    
    .movie-credits-hero .credit-people {
        color: white;
        font-weight: 500;
    }
    
    /* Movie meta (year, runtime, rating) */
    .movie-meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .movie-year, .movie-runtime {
        color: #e5e5e5;
    }
    
    .movie-rating-badge {
        background-color: var(--netflix-red);
        color: white;
        padding: 0.3rem 0.7rem;
        border-radius: 4px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
    }
    
    /* Movie Details Section */
    .movie-details {
        padding: 3rem 0;
    }
    
    .detail-poster-container {
        margin-top: 1rem;
    }
    
    .movie-poster {
        border-radius: 8px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.4);
        width: 100%;
        height: auto;
        object-fit: cover;
    }
    
    /* Section titles */
    .section-title {
        font-size: 1.3rem;
        font-weight: 600;
        margin-bottom: 1rem;
        color: white;
        border-left: 4px solid var(--netflix-red);
        padding-left: 0.8rem;
    }
	
    
    .section-title-large {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        color: white;
        border-left: 5px solid var(--netflix-red);
        padding-left: 1rem;
    }
    
    /* Rating display */
    .rating-container {
        background-color: var(--netflix-light-dark);
        border-radius: 8px;
        padding: 1rem;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }
    
    .rating-stars {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
    }
    
    .rating-value {
        color: #FFC107;
    }
    
    .rating-count {
        font-size: 0.9rem;
    }
    
    /* Genre badges */
    .movie-genres {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 2rem;
    }
    
    .genre-badge {
        background-color: rgba(255, 255, 255, 0.1);
        color: #e5e5e5;
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
        font-size: 0.9rem;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    
    .genre-badge:hover {
        background-color: var(--netflix-red);
        color: white;
    }
    
    /* Info sections */
    .info-section {
        background-color: var(--netflix-light-dark);
        border-radius: 8px;
        padding: 1.2rem;
        margin-bottom: 2rem;
    }
    
    .info-item h3 {
        color: #aaa;
        font-size: 0.9rem;
        margin-bottom: 0.3rem;
    }
	
	h2.section-title {
        font-size: 1.3rem;
        font-weight: 600;
        margin-bottom: 1rem;
        color: white;
        border-left: 4px solid var(--netflix-red);
        padding-left: 0.8rem;
    }
    
    /* Ensure offer modal title is properly styled */
    #offerModalLabel {
        font-size: 1.4rem;
        font-weight: 700;
    }
    
    .info-item p {
        color: #e5e5e5;
        font-size: 1rem;
    }
    
    /* OMDB Ratings Section */
    .ratings-section h2 {
        color: #e5e5e5;
        font-weight: 600;
        font-size: 1.25rem;
    }
    
    .rating-card {
        background-color: var(--netflix-light-dark);
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .rating-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }
    
    .rating-card h3 {
        color: #aaa;
        font-size: 0.9rem;
    }
    
    .rating-card .rating-value {
        font-size: 1.3rem;
        font-weight: 700;
    }
    
    /* Social sharing buttons */
    .social-share a {
        transition: all 0.3s ease;
    }
    
    .social-share a:hover {
        transform: translateY(-3px);
    }
    
    /* User review section */
    .review-card .card {
        background-color: var(--netflix-light-dark);
        border: none;
    }
    
    .review-card .card-header {
        background-color: rgba(0, 0, 0, 0.2);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    /* Breadcrumbs */
    .breadcrumb {
        padding: 0.75rem 0;
        margin-bottom: 0;
        background-color: transparent;
        z-index: 100;
        position: relative;
    }
    
    .breadcrumb-item a {
        color: var(--netflix-light-gray);
        text-decoration: none;
        transition: color 0.3s ease;
    }
    
    .breadcrumb-item a:hover {
        color: var(--netflix-red);
    }
    
    .breadcrumb-item.active {
        color: white;
    }
    
    /* Modal */
    #trailerModal .modal-content {
        background-color: #000;
        border: none;
    }
    
    #trailerModal .modal-header {
        border-bottom: 1px solid #333;
        background-color: #111;
        color: white;
    }
    
    /* Modal titles */
    .modal-title {
        font-size: 1.25rem;
        font-weight: 600;
    }
    
    /* Responsive Adjustments */
    @media (max-width: 767px) {
        .movie-hero {
            min-height: 100vh;
        }
        
        .movie-hero-title {
            font-size: 2.5rem;
        }
        
        .movie-tagline {
            font-size: 1.2rem;
        }
        
        .movie-overview-hero {
            font-size: 1rem;
        }
        
        .backdrop-overlay {
            background: linear-gradient(to right, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.8) 100%),
                         linear-gradient(to top, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.5) 50%, rgba(0,0,0,0.3) 100%);
        }
        
        .hero-buttons {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            width: 100%;
        }
        
        .hero-buttons .btn {
            width: 100%;
        }
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log("DOM loaded - Initializing modals");
        
        // DOM Elements
        const playTrailerBtn = document.getElementById('playTrailerBtn');
        const trailerIframe = document.getElementById('trailerIframe');
        const trailerModalEl = document.getElementById('trailerModal');
        const offerModalEl = document.getElementById('offerModal');
        const skipOfferButton = document.getElementById('skipOfferButton');
        
        console.log("Play button exists:", !!playTrailerBtn);
        console.log("Offer modal exists:", !!offerModalEl);
        console.log("Trailer modal exists:", !!trailerModalEl);
        console.log("Skip button exists:", !!skipOfferButton);
        
        // Initialize modals with specific options
        const trailerModal = new bootstrap.Modal(trailerModalEl, {
            backdrop: 'static' // Prevent closing when clicking outside
        });
        
        const offerModal = new bootstrap.Modal(offerModalEl, {
            backdrop: 'static', // Prevent closing when clicking outside
            keyboard: false // Prevent closing with Esc key
        });
        
        // Close button should be disabled until timer expires
        const closeBtn = offerModalEl.querySelector('.btn-close');
        closeBtn.style.display = 'none'; // Hide close button
        
        let skipTimeout = {{ config('site.offer_skip_timeout', 10) }};
        let skipTimer;
        
        const updateSkipButtonText = function(seconds) {
            skipOfferButton.textContent = "{{ config('site.offer_skip_text', 'Skip (Available in %s seconds)') }}".replace('%s', seconds);
            // Also update aria-label for accessibility
            skipOfferButton.setAttribute('aria-label', `Skip offer (available in ${seconds} seconds)`);
        };
        
        const enableSkipButton = function() {
            console.log("Enabling skip button");
            skipOfferButton.disabled = false;
            skipOfferButton.textContent = "Skip";
            skipOfferButton.setAttribute('aria-label', 'Skip offer');
            closeBtn.style.display = ''; // Show close button when timer expires
        };
        
        const startSkipTimer = function() {
            console.log("Starting skip timer");
            // Reset timer state
            skipTimeout = {{ config('site.offer_skip_timeout', 10) }};
            skipOfferButton.disabled = true;
            
            // Update initial text
            updateSkipButtonText(skipTimeout);
            
            // Clear any existing timer
            if (skipTimer) {
                clearInterval(skipTimer);
            }
            
            // Start new timer
            skipTimer = setInterval(function() {
                skipTimeout--;
                console.log("Timer tick:", skipTimeout);
                updateSkipButtonText(skipTimeout);
                
                if (skipTimeout <= 0) {
                    clearInterval(skipTimer);
                    enableSkipButton();
                }
            }, 1000);
        };
        
        const playTrailer = function() {
            console.log("Playing trailer");
            // For OMDB API, we'll use the IMDb ID to find trailers
            const imdbId = '{{ $movie['id'] }}';
            // Set the iframe src with the IMDb ID
            trailerIframe.src = `https://autoembed.co/movie/imdb/${imdbId}`;
            trailerModal.show();
            // Announce for screen readers that trailer is playing
            const announcement = document.createElement('div');
            announcement.setAttribute('aria-live', 'polite');
            announcement.classList.add('visually-hidden');
            announcement.textContent = 'Trailer is now playing';
            document.body.appendChild(announcement);
            setTimeout(() => announcement.remove(), 3000);
        };
        
        // Only the skip button can close the offer modal
        skipOfferButton.addEventListener('click', function() {
            console.log("Skip button clicked, disabled:", skipOfferButton.disabled);
            if (!skipOfferButton.disabled) {
                console.log("Hiding offer modal");
                offerModal.hide();
                
                // Wait for modal transition to complete before showing trailer
                console.log("Will show trailer after delay");
                setTimeout(() => {
                    playTrailer();
                }, 500);
            }
        });
        
        // Handle the play button click
        playTrailerBtn.addEventListener('click', function(e) {
            console.log("Watch button clicked");
            e.preventDefault();
            
            // Check if we should show the offer popup
            if ({{ config('site.show_offer_popup', 'true') ? 'true' : 'false' }}) {
                console.log("Showing offer popup");
                // Reset skip button state before showing modal
                skipOfferButton.disabled = true;
                closeBtn.style.display = 'none';
                
                // Show the offer modal
                offerModal.show();
                
                // Start the countdown
                startSkipTimer();
            } else {
                // If offer popup is disabled, show trailer directly
                console.log("Offer popup disabled, showing trailer directly");
                playTrailer();
            }
        });
        
        // When offer modal is hidden, reset timers
        offerModalEl.addEventListener('hidden.bs.modal', function() {
            console.log("Offer modal closed, clearing timer");
            clearInterval(skipTimer);
        });
        
        // When trailer modal is hidden, stop the video
        trailerModalEl.addEventListener('hidden.bs.modal', function() {
            console.log("Trailer modal closed, clearing iframe");
            trailerIframe.src = 'about:blank';
        });
        
        console.log("All event listeners attached");
    });
</script>
@endpush
@endif
@endsection