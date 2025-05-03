<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TmdbService
{
    /**
     * Base OMDB API URL
     */
    protected $apiUrl;
    
    /**
     * API Key configuration
     */
    protected $apiKey;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->apiUrl = env('OMDB_API_URL', 'https://www.omdbapi.com/');
        $this->apiKey = env('OMDB_API_KEY', '918f232b');
    }
    
    /**
     * Make a request to OMDB API
     */
    protected function makeRequest($params = [])
    {
        // Add API key to params
        $params['apikey'] = $this->apiKey;
        
        // Create a cache key from the request
        $cacheKey = 'omdb_' . md5(json_encode($params));
        
        // Check cache first with longer caching period
        if (Cache::has($cacheKey)) {
            Log::info('Using cached data for request', ['params' => array_diff_key($params, ['apikey' => true])]);
            return Cache::get($cacheKey);
        }
        
        try {
            // Build the URL
            $url = $this->apiUrl . '?' . http_build_query($params);
            
            Log::info('Making API request to OMDB', [
                'url' => str_replace($this->apiKey, '[REDACTED]', $url),
                'params' => array_diff_key($params, ['apikey' => true])
            ]);
            
            // Initialize CURL
            $curl = curl_init();
            
            // Set CURL options
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10, // Reduced timeout from 15 to 10 seconds
                CURLOPT_CONNECTTIMEOUT => 5, // Added connection timeout of 5 seconds
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => false,
                // Add a browser-like user agent to avoid potential API restrictions
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                // Follow redirects
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                // Enable HTTP/2 if available
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0
            ]);
            
            // Execute the request
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            
            // Check for CURL errors
            if (curl_errno($curl)) {
                $errorMsg = curl_error($curl);
                curl_close($curl);
                Log::error('CURL error in API request', [
                    'error' => $errorMsg,
                    'params' => array_diff_key($params, ['apikey' => true])
                ]);
                return ['Response' => 'False', 'Error' => 'Connection error: ' . $errorMsg];
            }
            
            // Close CURL
            curl_close($curl);
            
            // Log raw response for debugging
            Log::debug('Raw API response', [
                'http_code' => $httpCode,
                'response_length' => strlen($response),
                'response_sample' => substr($response, 0, 100)
            ]);
            
            // Process the result
            if ($httpCode == 200 && $response) {
                $data = json_decode($response, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('JSON decode error in API response', [
                        'error' => json_last_error_msg(),
                        'params' => array_diff_key($params, ['apikey' => true])
                    ]);
                    return ['Response' => 'False', 'Error' => 'Invalid JSON response'];
                }
                
                // If successful, cache for a longer period
                if (isset($data['Response']) && $data['Response'] === 'True') {
                    Cache::put($cacheKey, $data, 604800); // 1 week
                    Log::info('Successful API response', [
                        'params' => array_diff_key($params, ['apikey' => true]),
                        'title' => $data['Title'] ?? $data['Search'][0]['Title'] ?? 'Unknown'
                    ]);
                    return $data;
                } else {
                    Log::warning('API returned error response', [
                        'params' => array_diff_key($params, ['apikey' => true]),
                        'error' => $data['Error'] ?? 'Unknown error'
                    ]);
                    return $data; // Return the original error response
                }
            } else {
                Log::error('API request failed with HTTP code ' . $httpCode, [
                    'params' => array_diff_key($params, ['apikey' => true])
                ]);
                return ['Response' => 'False', 'Error' => 'HTTP error: ' . $httpCode];
            }
        } catch (\Exception $e) {
            Log::error('Exception during API request', [
                'params' => array_diff_key($params, ['apikey' => true]),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['Response' => 'False', 'Error' => $e->getMessage()];
        }
    }
    
    /**
     * Throttle requests to prevent overloading the API
     */
    protected function throttleRequests()
    {
        // Get the timestamp of the last API call
        $lastCallTimestamp = Cache::get('omdb_last_api_call', 0);
        $now = microtime(true);
        
        // If the last call was made less than 250ms ago, wait
        if ($now - $lastCallTimestamp < 0.25) {
            $sleepTime = 0.25 - ($now - $lastCallTimestamp);
            usleep($sleepTime * 1000000); // Convert to microseconds
        }
        
        // Update the last call timestamp
        Cache::put('omdb_last_api_call', microtime(true), 60);
    }
    
    /**
     * Search for movies
     */
    public function searchMovies($query, $page = 1)
    {
        // Add validation for the query parameter
        if (empty($query) || $query === '{search_term_string}') {
            Log::error('Invalid search query provided', ['query' => $query]);
            return [
                'results' => [],
                'page' => (int)$page,
                'total_pages' => 0,
                'total_results' => 0
            ];
        }
        
        // Log the search request
        Log::info('Searching for movies', ['query' => $query, 'page' => $page]);
        
        // Build parameters for API call
        $params = [
            's' => $query,
            'type' => 'movie',
            'page' => $page
        ];
        
        // Initialize curl
        $curl = curl_init();
        
        // Build the URL
        $apiUrl = $this->apiUrl . '?' . http_build_query($params + ['apikey' => $this->apiKey]);
        
        // Set curl options
        curl_setopt_array($curl, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5
        ]);
        
        // Format response to match the application's expected structure
        $formattedResponse = [
            'results' => [],
            'page' => (int)$page,
            'total_pages' => 1,
            'total_results' => 0,
            'query' => $query
        ];
        
        try {
            // Execute the curl request
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            
            Log::info('Search API raw response stats', [
                'query' => $query,
                'http_code' => $httpCode,
                'response_length' => strlen($response),
                'curl_error' => curl_error($curl)
            ]);
            
            if ($httpCode === 200 && !curl_errno($curl)) {
                // Decode the JSON response
                $data = json_decode($response, true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    // Success, we have a valid JSON response
                    Log::info('Search API response decoded', [
                        'query' => $query,
                        'success' => $data['Response'] ?? 'Unknown'
                    ]);
                    
                    if (isset($data['Response']) && $data['Response'] === 'True' && isset($data['Search'])) {
                        // We have search results, format them
                        $results = [];
                        foreach ($data['Search'] as $movie) {
                            $results[] = [
                                'id' => $movie['imdbID'],
                                'title' => $movie['Title'],
                                'release_date' => $movie['Year'] . '-01-01',
                                'poster_path' => $movie['Poster'] !== 'N/A' ? $movie['Poster'] : null,
                                // Use placeholder values instead of fetching details
                                'vote_average' => 0,
                                'vote_count' => 0,
                                'overview' => '',
                            ];
                        }
                        
                        $formattedResponse['results'] = $results;
                        $formattedResponse['total_results'] = (int)($data['totalResults'] ?? count($results));
                        $formattedResponse['total_pages'] = ceil($formattedResponse['total_results'] / 10);
                        
                        Log::info('Search successful', [
                            'query' => $query, 
                            'results_count' => count($results),
                            'total_pages' => $formattedResponse['total_pages']
                        ]);
                    } else {
                        // No search results or error
                        Log::warning('No search results found in API response', [
                            'query' => $query,
                            'error' => $data['Error'] ?? 'Unknown error',
                            'response' => $data
                        ]);
                    }
                } else {
                    // JSON parsing error
                    Log::error('JSON parse error in search results', [
                        'query' => $query,
                        'error' => json_last_error_msg(),
                        'response_sample' => substr($response, 0, 100)
                    ]);
                }
            } else {
                // HTTP error or curl error
                Log::error('HTTP error in search request', [
                    'query' => $query,
                    'http_code' => $httpCode,
                    'curl_error' => curl_error($curl)
                ]);
            }
        } catch (\Exception $e) {
            // Exception handling
            Log::error('Exception in search request', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
        } finally {
            // Always close curl
            curl_close($curl);
        }
        
        return $formattedResponse;
    }
    
    /**
     * Get movie details
     */
    public function getMovieDetails($movieId)
    {
        if (empty($movieId)) {
            Log::error('Empty movie ID provided');
            return [
                'id' => 'unknown',
                'title' => 'Movie not found',
                'overview' => 'No movie ID was provided',
                'poster_path' => null,
                'directors' => [],
                'top_cast' => []
            ];
        }
        
        // Log the request for debugging
        Log::info('Requesting movie details for ID: ' . $movieId);
        
        // Build the URL
        $apiUrl = $this->apiUrl . '?' . http_build_query([
            'i' => $movieId,
            'plot' => 'full',
            'apikey' => $this->apiKey
        ]);
        
        // Initialize CURL
        $curl = curl_init();
        
        // Set CURL options
        curl_setopt_array($curl, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5
        ]);
        
        try {
            // Execute the request
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            
            // Log detailed information
            Log::info('Movie details API response stats', [
                'id' => $movieId,
                'http_code' => $httpCode,
                'response_length' => strlen($response),
                'curl_error' => curl_error($curl)
            ]);
            
            if ($httpCode === 200 && !curl_errno($curl)) {
                // Process the result
                $data = json_decode($response, true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    // Log the API response
                    Log::info('Movie details API decoded response', [
                        'id' => $movieId,
                        'success' => $data['Response'] ?? 'Unknown',
                        'title' => $data['Title'] ?? 'Not provided'
                    ]);
                    
                    if (isset($data['Response']) && $data['Response'] === 'True') {
                        // Format OMDB response to match TMDB format
                        $formattedResponse = [
                            'id' => $data['imdbID'],
                            'title' => $data['Title'],
                            'original_title' => $data['Title'],
                            'release_date' => $this->formatReleaseDate($data['Released'] ?? 'N/A'),
                            'poster_path' => isset($data['Poster']) && $data['Poster'] !== 'N/A' ? $data['Poster'] : null,
                            'backdrop_path' => null, // OMDB doesn't provide backdrop
                            'vote_average' => (float)($data['imdbRating'] ?? 0),
                            'vote_count' => (int)str_replace(',', '', $data['imdbVotes'] ?? 0),
                            'overview' => isset($data['Plot']) && $data['Plot'] !== 'N/A' ? $data['Plot'] : '',
                            'tagline' => '',
                            'runtime' => $this->extractRuntime($data['Runtime'] ?? 'N/A'),
                            'genres' => $this->formatGenres($data['Genre'] ?? 'N/A'),
                            'directors' => $this->extractDirectors($data['Director'] ?? 'N/A'),
                            'top_cast' => $this->extractCast($data['Actors'] ?? 'N/A'),
                            'videos' => [],
                            'credits' => [
                                'cast' => $this->formatCast($data['Actors'] ?? 'N/A'),
                                'crew' => $this->formatCrew($data['Director'] ?? 'N/A', $data['Writer'] ?? 'N/A')
                            ]
                        ];
                        
                        // Add ratings for display
                        $formattedResponse['ratings'] = $data['Ratings'] ?? [];
                        
                        // Add additional OMDB fields for display
                        foreach(['Rated', 'Awards', 'Production', 'Country', 'Language', 'BoxOffice', 'Writer'] as $field) {
                            if(isset($data[$field]) && $data[$field] !== 'N/A') {
                                $formattedResponse[$field] = $data[$field];
                            }
                        }
                        
                        return $formattedResponse;
                    }
                } else {
                    Log::error('JSON parse error in movie details', [
                        'id' => $movieId,
                        'error' => json_last_error_msg(),
                        'response_sample' => substr($response, 0, 100)
                    ]);
                }
            } else {
                Log::error('HTTP error in movie details request', [
                    'id' => $movieId,
                    'http_code' => $httpCode,
                    'curl_error' => curl_error($curl)
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Exception in movie details request', [
                'id' => $movieId,
                'error' => $e->getMessage()
            ]);
        } finally {
            // Always close curl
            curl_close($curl);
        }
        
        // Log error condition
        Log::error('Movie not found or API error', [
            'movieId' => $movieId
        ]);
        
        // Return empty structure if movie not found
        return [
            'id' => $movieId,
            'title' => 'Movie not found',
            'overview' => 'Details not available',
            'poster_path' => null,
            'directors' => [],
            'top_cast' => []
        ];
    }
    
    /**
     * Format release date from OMDB format to YYYY-MM-DD
     */
    protected function formatReleaseDate($released)
    {
        if ($released === 'N/A') {
            return null;
        }
        
        try {
            $date = \DateTime::createFromFormat('d M Y', $released);
            return $date ? $date->format('Y-m-d') : null;
        } catch (\Exception $e) {
            // If parsing fails, try to extract just the year
            if (preg_match('/(\d{4})/', $released, $matches)) {
                return $matches[1] . '-01-01';
            }
            return null;
        }
    }
    
    /**
     * Extract runtime in minutes from OMDB format
     */
    protected function extractRuntime($runtime)
    {
        if ($runtime === 'N/A') {
            return 0;
        }
        
        if (preg_match('/(\d+)\s+min/', $runtime, $matches)) {
            return (int)$matches[1];
        }
        
        return 0;
    }
    
    /**
     * Format genres from comma-separated string to array of objects
     */
    protected function formatGenres($genreString)
    {
        if ($genreString === 'N/A') {
            return [];
        }
        
        $genres = explode(', ', $genreString);
        $result = [];
        
        foreach ($genres as $genre) {
            $result[] = [
                'id' => md5($genre), // Generate a pseudo-id
                'name' => $genre
            ];
        }
        
        return $result;
    }
    
    /**
     * Extract directors from comma-separated string
     */
    protected function extractDirectors($directorString)
    {
        if ($directorString === 'N/A') {
            return [];
        }
        
        return explode(', ', $directorString);
    }
    
    /**
     * Extract cast from comma-separated string
     */
    protected function extractCast($castString)
    {
        if ($castString === 'N/A') {
            return [];
        }
        
        return explode(', ', $castString);
    }
    
    /**
     * Format cast for credits section
     */
    protected function formatCast($castString)
    {
        if ($castString === 'N/A') {
            return [];
        }
        
        $castNames = explode(', ', $castString);
        $cast = [];
        
        foreach ($castNames as $index => $name) {
            $cast[] = [
                'id' => $index,
                'name' => $name,
                'character' => '',
                'order' => $index
            ];
        }
        
        return $cast;
    }
    
    /**
     * Format crew for credits section
     */
    protected function formatCrew($directorString, $writerString)
    {
        $crew = [];
        
        // Add directors
        if ($directorString !== 'N/A') {
            $directors = explode(', ', $directorString);
            foreach ($directors as $index => $name) {
                $crew[] = [
                    'id' => 'd' . $index,
                    'name' => $name,
                    'job' => 'Director',
                    'department' => 'Directing'
                ];
            }
        }
        
        // Add writers
        if ($writerString !== 'N/A') {
            $writers = explode(', ', $writerString);
            foreach ($writers as $index => $name) {
                $crew[] = [
                    'id' => 'w' . $index,
                    'name' => $name,
                    'job' => 'Writer',
                    'department' => 'Writing'
                ];
            }
        }
        
        return $crew;
    }
    
    /**
     * Get popular movies (emulated as OMDB doesn't have this endpoint)
     */
    public function getPopularMovies($page = 1)
    {
        // OMDB doesn't have a popular movies endpoint, so we'll search for some popular terms
        $popularQueries = ['action', 'drama', 'comedy', 'thriller', 'romance', 'sci-fi'];
        $query = $popularQueries[array_rand($popularQueries)];
        
        return $this->searchMovies($query, $page);
    }
    
    /**
     * Get top rated movies (emulated as OMDB doesn't have this endpoint)
     */
    public function getTopRatedMovies($page = 1)
    {
        // Use a specific cache key for top rated movies
        $cacheKey = 'omdb_top_rated_movies_list';
        
        // Get the preselected movie IDs from cache to avoid reshuffling on each call
        $topRatedMovies = Cache::remember($cacheKey, 86400, function() {
            // For top rated, we'll use a curated list of known top IMDb movies
            $movies = [
                'tt0111161', // The Shawshank Redemption
                'tt0068646', // The Godfather
                'tt0071562', // The Godfather Part II
                'tt0468569', // The Dark Knight
                'tt0050083', // 12 Angry Men
                'tt0108052', // Schindler's List
                'tt0167260', // The Lord of the Rings: The Return of the King
                'tt0110912', // Pulp Fiction
                // Adding more for variety
                'tt0137523', // Fight Club
                'tt0109830', // Forrest Gump
                'tt0080684', // Star Wars: Episode V - The Empire Strikes Back
                'tt0133093', // The Matrix
                'tt0099685', // Goodfellas
                'tt0073486', // One Flew Over the Cuckoo's Nest
                'tt0047478', // Seven Samurai
                'tt0114369', // Se7en
            ];
            
            // Shuffle once and cache the order
            shuffle($movies);
            return $movies;
        });
        
        $results = [];
        $startIndex = ($page - 1) * 8;
        $endIndex = min($startIndex + 8, count($topRatedMovies));
        
        // Get the movie IDs for this page
        $pageMovieIds = array_slice($topRatedMovies, $startIndex, $endIndex - $startIndex);
        
        // Log which movies we're fetching
        Log::info('OMDB getTopRatedMovies - Fetching movies for page ' . $page, [
            'count' => count($pageMovieIds),
            'ids' => $pageMovieIds
        ]);
        
        // Process at most 4 movies to avoid timeout
        $pageMovieIds = array_slice($pageMovieIds, 0, 4);
        
        foreach ($pageMovieIds as $imdbId) {
            // Check if we already have this movie details in cache
            $movieCacheKey = 'omdb_movie_details_' . $imdbId;
            
            $details = Cache::remember($movieCacheKey, 86400, function() use ($imdbId) {
                try {
                    Log::info('OMDB getTopRatedMovies - Fetching details for ' . $imdbId);
                    return $this->getMovieDetails($imdbId);
                } catch (\Exception $e) {
                    Log::error('OMDB getTopRatedMovies - Error fetching details for ' . $imdbId . ': ' . $e->getMessage());
                    return null;
                }
            });
            
            if ($details && isset($details['title']) && $details['title'] !== 'Movie not found') {
                $results[] = $details;
                Log::info('OMDB getTopRatedMovies - Added: ' . $details['title']);
            }
        }
        
        Log::info('OMDB getTopRatedMovies - Completed with ' . count($results) . ' results');
        
        return [
            'results' => $results,
            'page' => $page,
            'total_pages' => ceil(count($topRatedMovies) / 8),
            'total_results' => count($topRatedMovies)
        ];
    }
}