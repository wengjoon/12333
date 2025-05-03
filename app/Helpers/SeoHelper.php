<?php

namespace App\Helpers;

class SeoHelper
{
    /**
     * Get page title for the current page
     * 
     * @param string|null $title
     * @param bool $withSuffix
     * @return string
     */
    public static function title($title = null, $withSuffix = true)
    {
        $config = config('seo');
        
        if (empty($title)) {
            return $config['default_title'];
        }
        
        return $title . ($withSuffix ? $config['title_suffix'] : '');
    }
    
    /**
     * Get movie page title - adjusted for OMDB data format
     * 
     * @param array $movie
     * @return string
     */
    public static function movieTitle($movie)
    {
        $title = $movie['title'] ?? $movie['Title'] ?? 'Movie';
        return sprintf(config('seo.movie_title'), $title);
    }
    
    /**
     * Get movie meta description - adjusted for OMDB data format
     * 
     * @param array $movie
     * @return string
     */
    public static function movieDescription($movie)
    {
        $title = $movie['title'] ?? $movie['Title'] ?? 'Movie';
        
        // Extract year from release_date or Year field
        $year = '';
        if (isset($movie['release_date'])) {
            $year = substr($movie['release_date'], 0, 4);
        } elseif (isset($movie['Year'])) {
            $year = $movie['Year'];
        }
        
        // Extract overview/plot
        $overview = '';
        if (isset($movie['overview']) && !empty($movie['overview'])) {
            $overview = self::truncate($movie['overview'], 150);
        } elseif (isset($movie['Plot']) && !empty($movie['Plot'])) {
            $overview = self::truncate($movie['Plot'], 150);
        }
        
        return sprintf(config('seo.movie_description'), $title, $year, $overview);
    }
    
    /**
     * Get search page title
     * 
     * @param string $query
     * @return string
     */
    public static function searchTitle($query)
    {
        return sprintf(config('seo.search_title'), $query) . config('seo.title_suffix');
    }
    
    /**
     * Get meta description
     * 
     * @param string|null $description
     * @return string
     */
    public static function description($description = null)
    {
        return $description ?? config('seo.default_description');
    }
    
    /**
     * Get meta keywords
     * 
     * @param string|null $keywords
     * @return string
     */
    public static function keywords($keywords = null)
    {
        return $keywords ?? config('seo.default_keywords');
    }
    
    /**
     * Get Open Graph image URL - adjusted for OMDB poster format
     * 
     * @param string|null $image
     * @return string
     */
    public static function ogImage($image = null)
    {
        if ($image && strpos($image, 'http') === 0) {
            return $image; // OMDB gives full URLs
        }
        
        return asset($image ?? config('seo.default_og_image'));
    }
    
    /**
     * Get Twitter image URL - adjusted for OMDB poster format
     * 
     * @param string|null $image
     * @return string
     */
    public static function twitterImage($image = null)
    {
        if ($image && strpos($image, 'http') === 0) {
            return $image; // OMDB gives full URLs
        }
        
        return asset($image ?? config('seo.default_twitter_image'));
    }
    
    /**
     * Generate JSON-LD schema markup for the homepage
     * 
     * FIX: Return raw array instead of encoded string to prevent double encoding
     * 
     * @return array
     */
    public static function homeSchema()
    {
        $config = config('seo');
        
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $config['site_name'],
            'url' => url('/'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => url('/search') . '?query={search_term_string}',
                'query-input' => 'required name=search_term_string'
            ],
            'description' => $config['structured_data']['website']['description']
        ];
    }
    
    /**
     * Generate JSON-LD schema markup for a movie - adjusted for OMDB data format
     * 
     * FIX: Return raw array instead of encoded string to prevent double encoding
     * 
     * @param array $movie
     * @return array
     */
    public static function movieSchema($movie)
    {
        // Get title from either TMDB or OMDB format
        $title = $movie['title'] ?? $movie['Title'] ?? '';
        
        // Get description from overview or Plot
        $description = '';
        if (isset($movie['overview']) && !empty($movie['overview'])) {
            $description = $movie['overview'];
        } elseif (isset($movie['Plot']) && $movie['Plot'] !== 'N/A') {
            $description = $movie['Plot'];
        } else {
            $description = 'Watch ' . $title . ' online for free in HD quality on ' . config('seo.site_name') . '.';
        }
        
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Movie',
            'name' => $title,
            'description' => $description,
        ];
        
        // Add poster image if available
        if (!empty($movie['poster_path'])) {
            $data['image'] = 'https://image.tmdb.org/t/p/w500' . $movie['poster_path'];
        } elseif (!empty($movie['Poster']) && $movie['Poster'] !== 'N/A') {
            $data['image'] = $movie['Poster'];
        }
        
        // Add release date if available
        if (!empty($movie['release_date'])) {
            $data['datePublished'] = $movie['release_date'];
        } elseif (!empty($movie['Released']) && $movie['Released'] !== 'N/A') {
            $data['datePublished'] = $movie['Released'];
        } elseif (!empty($movie['Year'])) {
            $data['datePublished'] = $movie['Year'] . '-01-01';
        }
        
        // Add rating if available
        if (isset($movie['vote_average']) && isset($movie['vote_count'])) {
            $data['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $movie['vote_average'],
                'bestRating' => '10',
                'worstRating' => '0',
                'ratingCount' => $movie['vote_count']
            ];
        } elseif (isset($movie['imdbRating']) && $movie['imdbRating'] !== 'N/A' && isset($movie['imdbVotes']) && $movie['imdbVotes'] !== 'N/A') {
            $data['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $movie['imdbRating'],
                'bestRating' => '10',
                'worstRating' => '0',
                'ratingCount' => str_replace(',', '', $movie['imdbVotes'])
            ];
        }
        
        // Add directors if available
        if (!empty($movie['directors'])) {
            $data['director'] = [];
            foreach ($movie['directors'] as $director) {
                $data['director'][] = [
                    '@type' => 'Person',
                    'name' => $director
                ];
            }
        } elseif (!empty($movie['Director']) && $movie['Director'] !== 'N/A') {
            $directors = explode(', ', $movie['Director']);
            $data['director'] = [];
            foreach ($directors as $director) {
                $data['director'][] = [
                    '@type' => 'Person',
                    'name' => $director
                ];
            }
        }
        
        // Add cast if available
        if (!empty($movie['top_cast'])) {
            $data['actor'] = [];
            foreach ($movie['top_cast'] as $actor) {
                $data['actor'][] = [
                    '@type' => 'Person',
                    'name' => $actor
                ];
            }
        } elseif (!empty($movie['Actors']) && $movie['Actors'] !== 'N/A') {
            $actors = explode(', ', $movie['Actors']);
            $data['actor'] = [];
            foreach ($actors as $actor) {
                $data['actor'][] = [
                    '@type' => 'Person',
                    'name' => $actor
                ];
            }
        }
        
        // Add genre if available
        if (!empty($movie['Genre']) && $movie['Genre'] !== 'N/A') {
            $genres = explode(', ', $movie['Genre']);
            $data['genre'] = $genres;
        } elseif (isset($movie['genres']) && !empty($movie['genres'])) {
            $data['genre'] = [];
            foreach ($movie['genres'] as $genre) {
                if (is_array($genre)) {
                    $data['genre'][] = $genre['name'];
                } else {
                    $data['genre'][] = $genre;
                }
            }
        }
        
        // Add reviews if available
        if (!empty($movie['reviews'])) {
            $data['review'] = [];
            foreach ($movie['reviews'] as $review) {
                $reviewData = [
                    '@type' => 'Review',
                    'reviewBody' => $review['content'] ?? '',
                    'datePublished' => $review['created_at'] ?? date('Y-m-d')
                ];
                
                if (!empty($review['author'])) {
                    $reviewData['author'] = [
                        '@type' => 'Person',
                        'name' => $review['author']
                    ];
                }
                
                if (!empty($review['rating'])) {
                    $reviewData['reviewRating'] = [
                        '@type' => 'Rating',
                        'ratingValue' => $review['rating'],
                        'bestRating' => '10',
                        'worstRating' => '0'
                    ];
                }
                
                $data['review'][] = $reviewData;
            }
        }
        
        // Add watch action
        $data['potentialAction'] = [
            '@type' => 'WatchAction',
            'target' => url()->current()
        ];
        
        return $data;
    }
    
    /**
     * Generate JSON-LD schema markup for FAQs
     * 
     * FIX: Return raw array instead of encoded string to prevent double encoding
     * 
     * @param array $faqs
     * @return array
     */
    public static function faqSchema($faqs)
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => []
        ];
        
        foreach ($faqs as $faq) {
            $data['mainEntity'][] = [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['answer']
                ]
            ];
        }
        
        return $data;
    }
    
    /**
     * Truncate a string to a specified length
     * 
     * @param string $text
     * @param int $length
     * @param string $append
     * @return string
     */
    private static function truncate($text, $length = 160, $append = '...')
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        $text = substr($text, 0, $length);
        $text = substr($text, 0, strrpos($text, ' '));
        
        return $text . $append;
    }
}