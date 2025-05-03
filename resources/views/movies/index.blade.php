@extends('layouts.app')

{{-- SEO Meta Tags --}}
@section('seo_title', config('seo.default_title'))
@section('seo_description', config('seo.default_description'))
@section('seo_keywords', config('seo.default_keywords'))
@section('og_type', 'website')
@section('canonical_url', 'https://123moviespro.cc/')

{{-- Structured Data for Homepage --}}
@section('structured_data')
<script type="application/ld+json">
{!! json_encode(\App\Helpers\SeoHelper::homeSchema(), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>

{{-- FAQ Schema --}}
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "Is 123 Movies Pro really a free streaming site?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Yes—free movies and TV shows, no signup, no downloads, no registration required. Just watch."
      }
    },
    {
      "@type": "Question",
      "name": "What's the best thing about 123 Movies Pro?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Free streaming without downloads, no signup to watch movies or shows, offline downloads for later, and TV device support—all free."
      }
    },
    {
      "@type": "Question",
      "name": "Who's competing with 123 Movies Pro for the streaming crown?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Hulu, Netflix, Amazon Prime Video, Crackle, and YouTube Movies are the main competitors."
      }
    },
    {
      "@type": "Question",
      "name": "How do I watch movies and shows on 123 Movies Pro?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Two ways: stream online or download for offline viewing whenever you want."
      }
    },
    {
      "@type": "Question",
      "name": "What's the payment deal with 123 Movies Pro?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "There's no payment. It's all free—no cost, no cards, nothing."
      }
    },
    {
      "@type": "Question",
      "name": "What devices work with 123 Movies Pro?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Runs on everything—computers, phones, tablets, TVs, you name it."
      }
    },
    {
      "@type": "Question",
      "name": "Where can I access 123 Movies Pro?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Anywhere with internet. Stream from any corner of the planet."
      }
    }
  ]
}
</script>
@endsection

@section('content')
<div class="search-container">
    <div class="container text-center">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <h1>123 Movies Pro - Stream Your Favorite Movies Free</h1>
                <p class="search-subtitle">Find anything you want to watch in HD. No signup, no fees, just pure entertainment. Ready to search?</p>
                
                <form action="{{ route('movies.search') }}" method="get" class="search-box mx-auto" role="search" aria-label="Movie search">
                    <div class="input-group mb-3">
                        <input type="text" name="query" class="form-control search-input" placeholder="Search for movies or TV shows..." required aria-label="Search query">
                        <button class="btn btn-primary search-btn" type="submit">Search</button>
                    </div>
                </form>

                @if(session('error'))
                <div class="alert alert-danger mt-3" role="alert">
                    {{ session('error') }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Top Rated Movies Section -->
<section aria-labelledby="top-rated-heading">
    <div class="container mt-5">
        <h2 id="top-rated-heading" class="section-title">Top Rated Movies on 123 Movies Pro</h2>
        
        @foreach($movieRows as $row)
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4 mb-5">
                @foreach($row as $movie)
                <div class="col">
                    <article class="movie-card h-100">
                        <a href="{{ route('movies.show', ['id' => $movie['id']]) }}" class="text-decoration-none">
                            @php
                                // Handle different poster formats (OMDB vs TMDB)
                                $posterUrl = '';
                                if(!empty($movie['poster_path']) && strpos($movie['poster_path'], 'http') === 0) {
                                    // OMDB full URL
                                    $posterUrl = $movie['poster_path'];
                                } elseif(!empty($movie['poster_path'])) {
                                    // TMDB path
                                    $posterUrl = 'https://image.tmdb.org/t/p/w500' . $movie['poster_path'];
                                } elseif(!empty($movie['Poster']) && $movie['Poster'] !== 'N/A') {
                                    // OMDB Poster field
                                    $posterUrl = $movie['Poster'];
                                } else {
                                    // Default empty
                                    $posterUrl = '';
                                }
                            @endphp
                            
                            @if(!empty($posterUrl))
                                <img src="{{ $posterUrl }}" class="card-img-top" alt="{{ $movie['title'] }} poster - Watch on 123 Movies Pro" loading="lazy">
                            @else
                                <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center">
                                    <span class="text-light"><i class="bi bi-film" style="font-size: 3rem;"></i></span>
                                </div>
                            @endif
                            <div class="card-body">
                                <h3 class="movie-title" title="{{ $movie['title'] }}">{{ $movie['title'] }}</h3>
                                @if(isset($movie['release_date']) && !empty($movie['release_date']))
                                    <p class="movie-year">{{ substr($movie['release_date'], 0, 4) }}</p>
                                @elseif(isset($movie['Year']) && !empty($movie['Year']))
                                    <p class="movie-year">{{ $movie['Year'] }}</p>
                                @endif
                                @if(count($movie['directors'] ?? []) > 0)
                                    <p class="movie-directors" title="Directors: {{ implode(', ', $movie['directors']) }}">
                                        <i class="bi bi-camera-reels me-1"></i> {{ implode(', ', $movie['directors']) }}
                                    </p>
                                @elseif(isset($movie['Director']) && $movie['Director'] !== 'N/A')
                                    <p class="movie-directors" title="Director: {{ $movie['Director'] }}">
                                        <i class="bi bi-camera-reels me-1"></i> {{ $movie['Director'] }}
                                    </p>
                                @else
                                    <p class="movie-directors">
                                        <i class="bi bi-camera-reels me-1"></i> Unknown director
                                    </p>
                                @endif
                                <p class="movie-rating">
                                    <i class="bi bi-star-fill me-1"></i> 
                                    {{ isset($movie['vote_average']) ? number_format($movie['vote_average'], 1) : 
                                       (isset($movie['imdbRating']) ? number_format($movie['imdbRating'], 1) : 'N/A') 
                                    }}/10
                                </p>
                            </div>
                        </a>
                    </article>
                </div>
                @endforeach
            </div>
        @endforeach
    </div>
</section>

<!-- Popular Genres Section -->
<section aria-labelledby="genres-heading">
    <div class="container popular-genres mt-5 mb-5">
        <h2 id="genres-heading" class="section-title">Popular Genres on 123 Movies Pro</h2>
        <div class="row">
            <div class="col-6 col-md-4 col-lg-2 mb-3">
                <a href="{{ route('movies.search', ['query' => 'action']) }}" class="genre-link">
                    <div class="genre-card">Action</div>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2 mb-3">
                <a href="{{ route('movies.search', ['query' => 'comedy']) }}" class="genre-link">
                    <div class="genre-card">Comedy</div>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2 mb-3">
                <a href="{{ route('movies.search', ['query' => 'drama']) }}" class="genre-link">
                    <div class="genre-card">Drama</div>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2 mb-3">
                <a href="{{ route('movies.search', ['query' => 'horror']) }}" class="genre-link">
                    <div class="genre-card">Horror</div>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2 mb-3">
                <a href="{{ route('movies.search', ['query' => 'sci-fi']) }}" class="genre-link">
                    <div class="genre-card">Sci-Fi</div>
                </a>
            </div>
            <div class="col-6 col-md-4 col-lg-2 mb-3">
                <a href="{{ route('movies.search', ['query' => 'thriller']) }}" class="genre-link">
                    <div class="genre-card">Thriller</div>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- About Section -->
<section aria-labelledby="about-heading">
    <div class="container about-section mt-5 mb-5">
        <div class="about-content p-4">
            <h2 id="about-heading" class="about-title text-center mb-4">123 Movies Pro - Stream Movies and TV Shows in HD for Free Online</h2>
            
            <p>123 Movies Pro is a no-cost platform delivering online streams of movies and TV shows. You can dive into free movie streaming without needing to download a damn thing. It's packed with popular TV series like Friends, Modern Family, Breaking Bad, and The Simpsons—all free, no signup bullshit required. Watch any of these movies or shows without creating an account.</p>
            
            <p>Search for your favorite free TV show or movie by name on this site, hit play, and kick back. Shows like The Simpsons, Friends, and Modern Family are right there, streaming online with zero registration hassles. <strong>No Signup Needed</strong></p>
            
            <p>123 Movies Pro lets you watch movies and TV shows online for free without any account nonsense. No registration, no problem—just hunt down your film and stream it. <strong>Watch Anytime Online</strong></p>
            
            <p>You can stream these free movies online or download them to watch whenever the hell you want. 123 Movies Pro hooks you up with both free streaming and free downloads for movies and TV episodes. <strong>Bring 123 Movies Pro to Your TV</strong></p>
            
            <p>Grab the 123 Movies Pro app from the Roku, Chromecast, or AppleTV app store—search "123 Movies Pro"—and beam your free movie streaming straight to your TV's home screen. <strong>Ad-Free Experience</strong></p>
            
            <p>This site cuts the crap—no ads interrupting your free movies or TV shows. Enjoy uninterrupted streaming, no annoying pop-ups. <strong>Fast as F**k Streaming</strong></p>
            
            <p>123 Movies Pro's free movie and TV streaming is lightning-quick. No buffering delays—just smooth, instant playback for free movies and shows. <strong>Top-Notch Quality</strong></p>
            
            <p>Every free movie and TV show on this streaming site comes in crisp, high-quality visuals. Sharp pictures, no blurry garbage.</p>
            
            <p>123 Movies Pro ranks among the best free streaming platforms out there, loaded with movies and TV shows you can watch or download without signing up. It's fast, free, and skips the ad bullshit. <strong>Extra Streaming Options</strong></p>
            
            <p>Beyond movies, 123 Movies Pro streams free TV shows online too. No downloads needed—just jump in and watch, all quick and cost-free. <strong>Catch Your Favorite Flicks and Shows Free on 123 Movies Pro</strong></p>
            
            <p>If you're after a free movie, TV show, or download site, 123 Movies Pro is the shit. Stream or grab free content without any pre-download hassle. <strong>No Cash Required</strong></p>
            
            <p>This free streaming service doesn't ask for a dime. Watch movies and TV shows without spending jack.</p>
            
            <h3 id="rivals-heading" class="mt-4 mb-3">123 Movies Pro's Top Rivals</h3>
            <p>Here's who's competing with 123 Movies Pro in the free online streaming game:</p>
            <ul>
                <li>Hulu</li>
                <li>Netflix</li>
                <li>Amazon Prime Video</li>
                <li>Crackle</li>
                <li>YouTube Movies</li>
            </ul>
            
            <h3 id="faqs-heading" class="mt-4 mb-3">FAQs</h3>
            <div class="faq-item mb-3">
                <p><strong>Q: Is 123 Movies Pro really a free streaming site?</strong><br>
                A: Damn right—free movies and TV shows, no signup, no downloads, no registration crap. Just watch.</p>
            </div>
            
            <div class="faq-item mb-3">
                <p><strong>Q: What's the best thing about 123 Movies Pro?</strong><br>
                A: Free streaming without downloads, no signup to watch movies or shows, offline downloads for later, and TV device support—all free as hell.</p>
            </div>
            
            <div class="faq-item mb-3">
                <p><strong>Q: Who's fighting 123 Movies Pro for the streaming crown?</strong><br>
                A: Hulu, Netflix, Amazon Prime Video, Crackle, and YouTube Movies are the big dogs in the ring.</p>
            </div>
            
            <div class="faq-item mb-3">
                <p><strong>Q: How do I watch movies and shows on 123 Movies Pro?</strong><br>
                A: Two ways: stream online or download for offline whenever you damn well please.</p>
            </div>
            
            <div class="faq-item mb-3">
                <p><strong>Q: What's the payment deal with 123 Movies Pro?</strong><br>
                A: There's no payment. It's all free—no cash, no cards, nothing.</p>
            </div>
            
            <div class="faq-item mb-3">
                <p><strong>Q: What devices work with 123 Movies Pro?</strong><br>
                A: Runs on everything—computers, phones, tablets, TVs, you name it.</p>
            </div>
            
            <div class="faq-item mb-3">
                <p><strong>Q: Where can I access 123 Movies Pro?</strong><br>
                A: Anywhere with internet. Stream from any corner of the planet.</p>
            </div>
            
            <p class="mt-4 text-center">What's the holdup? Hit up 123 Movies Pro now, stream your favorite movies and shows for free—no downloads, no registration. You won't regret this.</p>
        </div>
    </div>
</section>

@push('styles')
<style>
    .section-title {
        color: #fff;
        font-weight: 700;
        font-size: 1.8rem;
        margin-bottom: 1.5rem;
    }
    
    .movie-year {
        color: var(--netflix-light-gray);
        font-size: 0.9rem;
        margin-top: -0.5rem;
        margin-bottom: 0.5rem;
    }
    
    .movie-card {
        position: relative;
        overflow: hidden;
    }
    
    .movie-card::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0) 30%);
        opacity: 0;
        transition: opacity 0.3s ease;
        pointer-events: none;
    }
    
    .movie-card:hover::after {
        opacity: 1;
    }
    
    /* Movie titles in cards should use h3 but maintain h5 styling */
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
    
    /* About section styles */
    .about-section {
        max-width: 1000px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .about-content {
        background-color: var(--netflix-light-dark);
        border-radius: 8px;
        color: #e5e5e5;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    }
    
    .about-title {
        color: #fff;
        font-weight: 700;
    }
    
    .about-section p {
        line-height: 1.7;
        margin-bottom: 1.2rem;
        font-size: 1.05rem;
    }
    
    .about-section strong {
        color: #fff;
        font-weight: 600;
    }
    
    .about-section h3 {
        color: #fff;
        font-weight: 600;
        font-size: 1.3rem;
    }
    
    .about-section ul {
        list-style-type: disc;
        padding-left: 2rem;
        margin-bottom: 1.5rem;
    }
    
    .about-section ul li {
        margin-bottom: 0.5rem;
    }
    
    .faq-item {
        border-left: 3px solid var(--netflix-red);
        padding-left: 1rem;
    }
    
    .faq-item strong {
        color: #fff;
    }
    
    /* Genre cards */
    .genre-card {
        background-color: var(--netflix-light-dark);
        color: white;
        text-align: center;
        padding: 1rem;
        border-radius: 6px;
        transition: all 0.3s ease;
        font-weight: 500;
    }

    .genre-link {
        text-decoration: none;
    }

    .genre-card:hover {
        background-color: var(--netflix-red);
        transform: translateY(-5px);
    }
</style>
@endpush
@endsection