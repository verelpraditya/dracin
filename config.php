<?php
// Configuration file for Dracin Streaming Platform

// Site Configuration
define('SITE_NAME', 'Dramain');
define('SITE_URL', 'http://localhost/dracin');
define('BASE_PATH', __DIR__);

// API Configuration - You will fill these later
define('DRAMABOX_API_URL', 'https://api.dramabox.com/endpoint');
define('NETSHORT_API_URL', 'https://api.netshort.com/endpoint');
define('MELOLO_API_URL', 'https://api.melolo.com/endpoint');
define('FLICKREELS_API_URL', 'https://api.flickreels.com/endpoint');

// API Keys - You will fill these later
define('DRAMABOX_API_KEY', 'your_dramabox_api_key');
define('NETSHORT_API_KEY', 'your_netshort_api_key');
define('MELOLO_API_KEY', 'your_melolo_api_key');
define('FLICKREELS_API_KEY', 'your_flickreels_api_key');

// Platform Configuration
$platforms = [
    'dramabox' => [
        'name' => 'DramaBox',
        'icon' => '🎬',
        'color' => 'from-pink-500 to-red-500'
    ],
    'netshort' => [
        'name' => 'NetShort',
        'icon' => '🎥',
        'color' => 'from-orange-500 to-pink-500'
    ],
    'melolo' => [
        'name' => 'MELOLO',
        'icon' => '🎭',
        'color' => 'from-yellow-500 to-orange-500'
    ],
    'flickreels' => [
        'name' => 'FlickReels',
        'icon' => '🎞️',
        'color' => 'from-yellow-400 to-yellow-600'
    ]
];

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Helper function to make API requests
function makeApiRequest($url, $headers = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        return json_decode($response, true);
    }
    
    return null;
}
