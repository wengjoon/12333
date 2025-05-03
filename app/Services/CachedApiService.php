<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CachedApiService extends TmdbService
{
    /**
     * Override the search method to add caching
     */
    public function searchMovies($query, $page = 1)
    {
        // Cache key with 24 hour expiration
        $cacheKey = 'movie_search_' . md5($query . '_' . $page);
        
        return Cache::remember($cacheKey, 86400, function () use ($query, $page) {
            // Call the parent class method
            return parent::searchMovies($query, $page);
        });
    }
    
    /**
     * Override the getMovieDetails method to add caching
     */
    public function getMovieDetails($id)
    {
        $cacheKey = 'movie_details_' . $id;
        
        return Cache::remember($cacheKey, 86400, function () use ($id) {
            // Call the parent class method
            return parent::getMovieDetails($id);
        });
    }
    
    /**
     * Override makeRequest to remove throttling
     */
    public function makeRequest($params = [])
    {
        // Add API key to params
        $params['apikey'] = $this->apiKey;
        
        // Create a cache key from the request
        $cacheKey = 'omdb_' . md5(json_encode($params));
        
        // Check cache first
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        try {
            // Build the URL
            $url = $this->apiUrl . '?' . http_build_query($params);
            
            // Create a context to handle SSL issues
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
                'http' => [
                    'timeout' => 15, // Increased timeout to 15 seconds
                ],
            ]);
            
            // Make the request without throttling delay
            $result = file_get_contents($url, false, $context);
            
            // Process the result
            if ($result !== false) {
                $data = json_decode($result, true);
                
                // If successful (OMDB returns "Response": "True"), cache and return
                if (isset($data['Response']) && $data['Response'] === 'True') {
                    Cache::put($cacheKey, $data, 604800); // 1 week
                    return $data;
                } else {
                    // Return empty result
                    return ['Response' => 'False'];
                }
            } else {
                return ['Response' => 'False'];
            }
        } catch (\Exception $e) {
            // Return empty result
            return ['Response' => 'False'];
        }
    }
}