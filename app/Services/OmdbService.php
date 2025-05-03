<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OmdbService
{
    /**
     * Base OMDB API URL
     */
    protected $apiUrl;
    
    /**
     * API Key
     */
    protected $apiKey;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->apiUrl = env('OMDB_API_URL', 'http://www.omdbapi.com');
        $this->apiKey = env('OMDB_API_KEY');
    }
    
    /**
     * Search for movies by title
     */
    public function searchMovies($query, $page = 1)
    {
        $cacheKey = 'omdb_search_' . md5($query . '_' . $page);
        
        return Cache::remember($cacheKey, 3600, function () use ($query, $page) {
            $response = Http::get($this->apiUrl, [
                'apikey' => $this->apiKey,
                's' => $query,
                'page' => $page,
                'type' => 'movie',
            ]);
            
            if ($response->successful() && $response->json()['Response'] === 'True') {
                return [
                    'results' => $this->formatSearchResults($response->json()['Search']),
                    'total_results' => (int)$response->json()['totalResults'],
                    'total_pages' => ceil((int)$response->json()['totalResults'] / 10),
                    'page' => $page
                ];
            }
            
            return [
                'results' => [],
                'total_results' => 0,
                'total_pages' => 0,
                'page' => $page
            ];
        });
    }
    
    /**
     * Get movie details by ID
     */
    public function getMovieDetails($imdbId)
    {
        $cacheKey = 'omdb_movie_' . $imdbId;
        
        return Cache::remember($cacheKey, 86400, function () use ($imdbId) {
            $response = Http::get($this->apiUrl, [
                'apikey' => $this->apiKey,
                'i' => $imdbId,
                'plot' => 'full',
            ]);
            
            if ($response->successful() && $response->json()['Response'] === 'True') {
                return $this->formatMovieDetails($response->json());
            }
            
            return null;
        });
    }
    
    /**
     * Get top rated movies
     * Note: OMDB doesn't have direct endpoint for top rated movies,
     * so we'll search for popular movies instead
     */
    public function getTopRatedMovies($limit = 8)
    {
        $popularTitles = ['Inception', 'Interstellar', 'The Godfather', 'The Dark Knight', 'Pulp Fiction', 
                         'The Lord of the Rings', 'The Matrix', 'Goodfellas', 'Star Wars', 'The Shawshank Redemption'];
        
        $cacheKey = 'omdb_top_rated';
        
        return Cache::remember($cacheKey, 10080, function () use ($popularTitles, $limit) {
            $movies = [];
            
            // Get movies for each popular title
            foreach ($popularTitles as $title) {
                if (count($movies) >= $limit) break;
                
                $response = Http::get($this->apiUrl, [
                    'apikey' => $this->apiKey,
                    's' => $title,
                    'type' => 'movie',
                ]);
                
                if ($response->successful() && $response->json()['Response'] === 'True') {
                    $results = $response->json()['Search'];
                    
                    // Get the first result for each title
                    if (!empty($results)) {
                        $movie = $this->getMovieDetails($results[0]['imdbID']);
                        if ($movie) {
                            $movies[] = $movie;
                        }
                    }
                }
            }
            
            return $movies;
        });
    }
    
    /**
     * Format search results to be compatible with application
     */
    protected function formatSearchResults($results)
    {
        $formatted = [];
        
        foreach ($results as $movie) {
            $formatted[] = [
                'id' => $movie['imdbID'],
                'title' => $movie['Title'],
                'poster_path' => $movie['Poster'] === 'N/A' ? null : $movie['Poster'],
                'release_date' => $movie['Year'],
                'vote_average' => 0, // OMDB search doesn't include ratings
                'directors' => [], // Will be populated if needed
            ];
        }
        
        return $formatted;
    }
    
    /**
     * Format movie details to be compatible with application
     */
    protected function formatMovieDetails($movie)
    {
        $directors = explode(', ', $movie['Director']);
        $cast = explode(', ', $movie['Actors']);
        $genres = explode(', ', $movie['Genre']);
        
        $formattedGenres = [];
        foreach ($genres as $genre) {
            $formattedGenres[] = [
                'id' => md5($genre),
                'name' => $genre
            ];
        }
        
        // Extract rating value
        $rating = 0;
        foreach ($movie['Ratings'] as $ratingInfo) {
            if ($ratingInfo['Source'] === 'Internet Movie Database') {
                $rating = (float) str_replace('/10', '', $ratingInfo['Value']) * 10;
                break;
            }
        }
        
        return [
            'id' => $movie['imdbID'],
            'title' => $movie['Title'],
            'overview' => $movie['Plot'],
            'release_date' => $movie['Released'] !== 'N/A' ? $movie['Released'] : $movie['Year'],
            'poster_path' => $movie['Poster'] === 'N/A' ? null : $movie['Poster'],
            'backdrop_path' => null, // OMDB doesn't provide backdrop images
            'vote_average' => $rating / 10, // Convert to scale of 10
            'vote_count' => 0, // OMDB doesn't provide vote count
            'runtime' => $movie['Runtime'] !== 'N/A' ? (int) str_replace(' min', '', $movie['Runtime']) : 0,
            'genres' => $formattedGenres,
            'directors' => $directors,
            'top_cast' => array_slice($cast, 0, 5),
            'tagline' => $movie['Plot'] ? substr($movie['Plot'], 0, 100) . '...' : '',
            'imdb_id' => $movie['imdbID'],
        ];
    }
}