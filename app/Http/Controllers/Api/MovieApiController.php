<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Services\TmdbService;

class MovieApiController extends Controller
{
    /**
     * The OMDB service
     */
    protected $movieService;
    
    /**
     * Constructor to initialize API service
     */
    public function __construct(TmdbService $movieService)
    {
        $this->movieService = $movieService;
    }

    /**
     * Get popular movies
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function popular()
    {
        // Cache for 12 hours (43200 seconds)
        $movies = Cache::remember('api_popular_movies', 43200, function () {
            $response = $this->movieService->getPopularMovies();
            return $response['results'] ?? [];
        });
        
        return response()->json($movies);
    }

    /**
     * Get top rated movies
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function topRated()
    {
        // Cache for 12 hours (43200 seconds)
        $movies = Cache::remember('api_top_rated_movies', 43200, function () {
            $response = $this->movieService->getTopRatedMovies();
            return $response['results'] ?? [];
        });
        
        return response()->json($movies);
    }
    
    /**
     * Search for movies
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $query = $request->input('query');
        $page = $request->input('page', 1);
        
        if (empty($query)) {
            return response()->json(['error' => 'Query parameter is required'], 400);
        }
        
        // Cache for 6 hours (21600 seconds)
        $results = Cache::remember('api_search_' . md5($query . '_' . $page), 21600, function () use ($query, $page) {
            $response = $this->movieService->searchMovies($query, $page);
            return $response['results'] ?? [];
        });
        
        return response()->json($results);
    }
}