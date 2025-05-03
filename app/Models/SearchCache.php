<?php
// Create this file at app/Models/SearchCache.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchCache extends Model
{
    protected $table = 'search_results';
    
    protected $fillable = [
        'query',
        'page',
        'results',
    ];
    
    protected $casts = [
        'results' => 'array',
    ];
    
    /**
     * Get cached search results or fetch new ones
     *
     * @param string $query
     * @param int $page
     * @param callable $fetchCallback
     * @return array
     */
    public static function getOrFetch($query, $page, $fetchCallback)
    {
        // Look for cached result
        $cachedSearch = self::where('query', $query)
            ->where('page', $page)
            ->first();
        
        // If result exists and is less than 24 hours old, return it
        if ($cachedSearch && $cachedSearch->updated_at->diffInHours(now()) < 24) {
            return $cachedSearch->results;
        }
        
        // Otherwise, fetch new data
        $results = $fetchCallback();
        
        // Store in the database
        self::updateOrCreate(
            ['query' => $query, 'page' => $page],
            ['results' => $results]
        );
        
        return $results;
    }
}