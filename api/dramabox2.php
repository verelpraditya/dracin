<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    // Get page parameter (default to 1)
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    if ($page < 1) $page = 1;
    
    // Fetch data from new DramaBox API with page
    $apiUrl = 'https://dramabos.asia/api/dramabox/api/foryou/' . $page . '?lang=in';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode != 200 || !$response) {
        throw new Exception('Failed to fetch from API: ' . $error . ' (HTTP ' . $httpCode . ')');
    }
    
    $apiData = json_decode($response, true);
    
    if (!$apiData || !isset($apiData['success']) || !$apiData['success']) {
        throw new Exception('Invalid API response');
    }
    
    // Transform API data to our format
    $dramas = [];
    
    if (isset($apiData['data']['list']) && is_array($apiData['data']['list'])) {
        foreach ($apiData['data']['list'] as $item) {
            // Extract tags
            $tags = [];
            if (isset($item['tags']) && is_array($item['tags'])) {
                $tags = array_slice($item['tags'], 0, 3);
            }
            
            $bookId = $item['bookId'] ?? '';
            if (!empty($bookId)) {
                // Get episode count from chapterCount
                $chapterCount = intval($item['chapterCount'] ?? 0);
                
                $dramas[] = [
                    'id' => $bookId,
                    'bookId' => $bookId,
                    'title' => $item['bookName'] ?? 'Unknown',
                    'poster' => $item['cover'] ?? '',
                    'rating' => 0,
                    'tags' => $tags,
                    'episodes' => $chapterCount,
                    'year' => date('Y'),
                    'description' => $item['introduction'] ?? ''
                ];
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'platform' => 'dramabox',
        'data' => $dramas,
        'total' => count($dramas)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch data',
        'message' => $e->getMessage()
    ]);
}
