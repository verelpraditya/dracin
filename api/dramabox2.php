<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    // Get page parameter (default to 1)
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    if ($page < 1) $page = 1;
    
    // Fetch data from new DramaBox API with page
    $apiUrl = 'https://dramabos.asia/api/dramabox/api/recommend/' . $page . '?lang=in';
    
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
    
    if (!$apiData || !isset($apiData['recommendList'])) {
        throw new Exception('Invalid API response');
    }
    
    // Transform API data to our format
    $dramas = [];
    
    // New API structure uses recommendList.records instead of data.list
    if (isset($apiData['recommendList']['records']) && is_array($apiData['recommendList']['records'])) {
        foreach ($apiData['recommendList']['records'] as $item) {
            // Skip tag cards (cardType 3)
            if (isset($item['cardType']) && $item['cardType'] == 3) {
                continue;
            }
            
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
                    'poster' => $item['coverWap'] ?? $item['cover'] ?? '',
                    'rating' => 0,
                    'tags' => $tags,
                    'episodes' => $chapterCount,
                    'year' => date('Y'),
                    'description' => $item['introduction'] ?? '',
                    'playCount' => $item['playCount'] ?? ''
                ];
            }
        }
    }
    
    // Get pagination info from API response
    $pagination = $apiData['recommendList'];
    
    echo json_encode([
        'success' => true,
        'platform' => 'dramabox',
        'data' => $dramas,
        'total' => count($dramas),
        'pagination' => [
            'current' => $pagination['current'] ?? 1,
            'size' => $pagination['size'] ?? 9,
            'totalPages' => $pagination['pages'] ?? 1,
            'totalRecords' => $pagination['total'] ?? count($dramas)
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch data',
        'message' => $e->getMessage()
    ]);
}
