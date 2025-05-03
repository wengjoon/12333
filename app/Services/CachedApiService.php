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
        // Increase cache time to 3 days
        $cacheTime = env('APP_ENV') === 'production' ? 259200 : 86400; // 3 days in production
        
        // Cache key with improved expiration
        $cacheKey = 'movie_search_' . md5($query . '_' . $page);
        
        return Cache::remember($cacheKey, $cacheTime, function () use ($query, $page) {
            // Call the parent class method
            return parent::searchMovies($query, $page);
        });
    }
    
    /**
     * Override the getMovieDetails method to add caching
     */
    public function getMovieDetails($id)
    {
        // Increase cache time to 1 week
        $cacheTime = env('APP_ENV') === 'production' ? 604800 : 86400; // 1 week in production
        
        $cacheKey = 'movie_details_' . $id;
        
        return Cache::remember($cacheKey, $cacheTime, function () use ($id) {
            // Call the parent class method
            return parent::getMovieDetails($id);
        });
    }
    
    /**
     * Override makeRequest to remove throttling and improve performance
     */
    public function makeRequest($params = [])
    {
        // Add API key to params
        $params['apikey'] = $this->apiKey;
        
        // Create a cache key from the request
        $cacheKey = 'omdb_' . md5(json_encode($params));
        
        // Increase cache time to 2 weeks in production
        $cacheTime = env('APP_ENV') === 'production' ? 1209600 : 604800; // 2 weeks in production
        
        // Check cache first
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        try {
            // Build the URL
            $url = $this->apiUrl . '?' . http_build_query($params);
            
            // Initialize CURL for better performance and error handling
            $curl = curl_init();
            
            // Set CURL options with optimal timeout settings
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5, // Reduced timeout to 5 seconds
                CURLOPT_CONNECTTIMEOUT => 3, // Reduced connection timeout to 3 seconds
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5
            ]);
            
            // Execute the request
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            
            // Check for curl errors
            if (curl_errno($curl)) {
                $error = curl_error($curl);
                curl_close($curl);
                throw new \Exception('CURL Error: ' . $error);
            }
            
            // Close curl
            curl_close($curl);
            
            // Check HTTP code
            if ($httpCode != 200) {
                throw new \Exception('HTTP Error: ' . $httpCode);
            }
            
            // Process the result
            $data = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON Error: ' . json_last_error_msg());
            }
            
            // If successful (OMDB returns "Response": "True"), cache and return
            if (isset($data['Response']) && $data['Response'] === 'True') {
                Cache::put($cacheKey, $data, $cacheTime);
                return $data;
            } else {
                // Return empty result with error message
                return [
                    'Response' => 'False',
                    'Error' => $data['Error'] ?? 'Unknown error'
                ];
            }
        } catch (\Exception $e) {
            // Log error but don't expose to user
            \Log::error('OMDB API Exception: ' . $e->getMessage());
            
            // Return empty result
            return [
                'Response' => 'False',
                'Error' => 'Service temporarily unavailable' 
            ];
        }
    }
}