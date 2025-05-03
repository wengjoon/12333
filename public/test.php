<?php
/**
 * Diagnostic test file for 123moviespro.com
 * Use this file to check server configuration and routing issues
 */

echo "<h1>123 Movies Pro Server Diagnostic</h1>";

// Current URL and server info
echo "<h2>Server Information</h2>";
echo "<p><strong>Requested URL:</strong> " . htmlspecialchars($_SERVER['REQUEST_URI']) . "</p>";
echo "<p><strong>Server Software:</strong> " . htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Document Root:</strong> " . htmlspecialchars($_SERVER['DOCUMENT_ROOT']) . "</p>";

// Laravel existence check
echo "<h2>Laravel Installation</h2>";
$laravelExists = file_exists(__DIR__ . '/../artisan');
$routesExist = file_exists(__DIR__ . '/../routes/web.php');
$envExists = file_exists(__DIR__ . '/../.env');

echo "<p><strong>Laravel installed:</strong> " . ($laravelExists ? 'Yes' : 'No') . "</p>";
echo "<p><strong>Routes file exists:</strong> " . ($routesExist ? 'Yes' : 'No') . "</p>";
echo "<p><strong>Environment file exists:</strong> " . ($envExists ? 'Yes' : 'No') . "</p>";

// Check for mod_rewrite
echo "<h2>mod_rewrite Status</h2>";
$modRewriteEnabled = in_array('mod_rewrite', apache_get_modules());
echo "<p><strong>mod_rewrite enabled:</strong> " . ($modRewriteEnabled ? 'Yes' : 'No or cannot determine') . "</p>";

// Test the route pattern
echo "<h2>Route Pattern Test</h2>";
if (preg_match('/^\/movie\/tt([0-9]+)$/i', $_SERVER['REQUEST_URI'])) {
    echo "<p style='color:green;'>✓ URL matches movie pattern correctly</p>";
} else {
    echo "<p style='color:orange;'>⚠ URL does not match the movie pattern</p>";
}

// Check .htaccess
echo "<h2>.htaccess Check</h2>";
$htaccessContent = @file_get_contents(__DIR__ . '/.htaccess');
if ($htaccessContent !== false) {
    $htaccessLength = strlen($htaccessContent);
    echo "<p><strong>.htaccess file exists</strong> (size: {$htaccessLength} bytes)</p>";
    
    // Check for specific rules
    if (strpos($htaccessContent, 'RewriteEngine On') !== false) {
        echo "<p style='color:green;'>✓ RewriteEngine is enabled in .htaccess</p>";
    } else {
        echo "<p style='color:red;'>✗ RewriteEngine not found in .htaccess</p>";
    }

    if (strpos($htaccessContent, 'movie/tt') !== false) {
        echo "<p style='color:green;'>✓ Special movie ID rule found in .htaccess</p>";
    } else {
        echo "<p style='color:orange;'>⚠ Special movie ID rule not found in .htaccess</p>";
    }
} else {
    echo "<p style='color:red;'>✗ .htaccess file not found or not readable</p>";
}

// Check for headers
echo "<h2>Response Headers</h2>";
$allHeaders = getallheaders();
echo "<pre>";
print_r($allHeaders);
echo "</pre>";

// Check if IMDb ID can be extracted
echo "<h2>IMDb ID Extraction</h2>";
if (preg_match('/\/movie\/(tt[0-9]+)$/i', $_SERVER['REQUEST_URI'], $matches)) {
    echo "<p><strong>Extracted IMDb ID:</strong> " . htmlspecialchars($matches[1]) . "</p>";
    
    // Test if we can fetch movie details
    if (function_exists('curl_init')) {
        echo "<p><strong>Testing API access for this ID:</strong></p>";
        $apiKey = '918f232b'; // From your TmdbService.php
        $url = "https://www.omdbapi.com/?apikey={$apiKey}&i=" . urlencode($matches[1]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "<p>API Response (HTTP {$httpCode}):</p>";
        echo "<pre>";
        print_r(json_decode($response, true));
        echo "</pre>";
    } else {
        echo "<p>CURL not available to test API access</p>";
    }
} else {
    echo "<p>No IMDb ID found in current URL. Visit /movie/tt1611224 to test extraction.</p>";
}

echo "<p><small>Generated at: " . date('Y-m-d H:i:s') . "</small></p>"; 