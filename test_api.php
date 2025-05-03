<?php

// Test OMDB API
$apiKey = '918f232b';
$searchTerm = 'metal';
$url = "https://www.omdbapi.com/?apikey={$apiKey}&s={$searchTerm}&type=movie";

echo "Testing API URL: " . $url . "\n\n";

// Method 1: file_get_contents
echo "Method 1: file_get_contents\n";
try {
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
        'http' => [
            'timeout' => 15,
        ],
    ]);
    
    $result1 = file_get_contents($url, false, $context);
    echo "Response: " . $result1 . "\n\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

// Method 2: curl
echo "Method 2: curl\n";
try {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3'
    ]);
    
    $result2 = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    echo "HTTP Code: " . $httpCode . "\n";
    echo "Response: " . $result2 . "\n";
    
    curl_close($curl);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 