<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\TmdbService;

class MovieController extends Controller
{
    /**
     * The OMDB API service
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
     * Display the search homepage with top rated movies
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Cache key for top rated movies
        $cacheKey = 'top_rated_movies';
        
        // Increase cache time for production
        $cacheTime = env('APP_ENV') === 'production' ? 10080 : 1440; // 1 week in production
        
        // Only refresh cache if it doesn't exist or if specifically requested
        $refresh = request()->has('refresh');
        
        if ($refresh && request()->query('refresh') === 'true') {
            Cache::forget($cacheKey);
            Log::info('MovieController: Manually refreshing top rated movies cache');
        }
        
        // Cache top rated movies 
        $topRatedMovies = Cache::remember($cacheKey, $cacheTime, function () {
            try {
                Log::info('MovieController: Refreshing top rated movies cache to pull from OMDB');
                $response = $this->movieService->getTopRatedMovies();
                
                if (!empty($response['results'])) {
                    // Log the response for debugging
                    Log::info('MovieController: Got ' . count($response['results']) . ' top rated movies from OMDB', [
                        'first_movie' => $response['results'][0]['title'] ?? 'None',
                        'has_poster' => isset($response['results'][0]['poster_path']) ? 'Yes' : 'No'
                    ]);
                    
                    return $response['results'];
                } else {
                    Log::warning('MovieController: No top rated movies returned from API');
                    return [];
                }
            } catch (\Exception $e) {
                Log::error('MovieController: Error fetching top rated movies', [
                    'error' => $e->getMessage()
                ]);
                return [];
            }
        });

        // Split movies into rows (2 movies per row for initial load)
        $movieRows = array_chunk($topRatedMovies, 2);
        
        return view('movies.index', [
            'movieRows' => $movieRows
        ]);
    }

    /**
     * Search for movies using the OMDB API
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function search(Request $request)
    {
        $query = trim($request->input('query', ''));
        $page = (int)$request->input('page', 1);

        if (empty($query)) {
            return redirect()->route('movies.index');
        }

        // Log search request
        Log::info('User search request', [
            'query' => $query,
            'page' => $page,
            'ip' => $request->ip()
        ]);

        // Use a cache key for the search with longer cache time
        $cacheKey = 'movie_search_' . md5($query . '_' . $page);
        
        // Clear cache if we're rechecking the same search
        if ($request->has('refresh')) {
            Log::info('Clearing search cache for refresh', ['query' => $query]);
            Cache::forget($cacheKey);
        }
        
        $results = Cache::remember($cacheKey, 86400, function () use ($query, $page) {
            try {
                $searchResults = $this->movieService->searchMovies($query, $page);
                
                // Log search results count
                Log::info('Search results returned', [
                    'query' => $query,
                    'count' => count($searchResults['results'] ?? [])
                ]);
                
                return [
                    'results' => $searchResults['results'] ?? [],
                    'total_pages' => $searchResults['total_pages'] ?? 1,
                    'current_page' => $searchResults['page'] ?? $page,
                    'query' => $query,
                ];
            } catch (\Exception $e) {
                Log::error('Error in search', [
                    'query' => $query,
                    'exception' => $e->getMessage()
                ]);
                
                return [
                    'results' => [],
                    'total_pages' => 1,
                    'current_page' => $page,
                    'query' => $query,
                    'error' => $e->getMessage()
                ];
            }
        });

        return view('movies.search', $results);
    }

    /**
     * Display the detailed information for a specific movie
     *
     * @param  string  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        // Check if ID is empty
        if (empty($id)) {
            \Log::error('Empty movie ID provided');
            abort(404, 'Movie ID is required');
        }
        
        // Log the incoming request
        \Log::info('Movie details requested', ['id' => $id]);
        
        $cacheKey = 'movie_details_' . $id;
        
        try {
            $movie = Cache::remember($cacheKey, 3600, function () use ($id) {
                // Get movie details from OMDB
                $details = $this->movieService->getMovieDetails($id);
                
                // Immediately check if we got a valid response with a title
                if (!isset($details['title']) || $details['title'] === 'Movie not found') {
                    \Log::error('Movie not found in API', ['id' => $id]);
                    throw new \Exception('Movie not found: ' . $id);
                }
                
                return $details;
            });
            
            // If movie is not empty but has minimal properties, it's an error case
            if (empty($movie) || $movie['title'] === 'Movie not found') {
                \Log::error('Movie data invalid', ['id' => $id, 'movie' => $movie]);
                abort(404, 'Movie not found');
            }
            
            return view('movies.show', ['movie' => $movie]);
        } catch (\Exception $e) {
            // Log the error
            \Log::error('Exception in movie details: ' . $id . ' - ' . $e->getMessage());
            
            // Force a 404 response
            abort(404, 'Movie not found: ' . $e->getMessage());
        }
    }
}