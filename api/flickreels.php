<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    // Fetch data from FlickReels API
    $apiUrl = 'https://dramabos.asia/api/flick/home?page=1&page_size=20&lang=6';
    
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
    
    if (!$apiData || !isset($apiData['status_code']) || $apiData['status_code'] != 1) {
        throw new Exception('Invalid API response');
    }
    
    // Transform API data to our format
    $dramas = [];
    
    // API returns sections in data array
    if (isset($apiData['data']) && is_array($apiData['data'])) {
        foreach ($apiData['data'] as $section) {
            if (isset($section['list']) && is_array($section['list'])) {
                foreach ($section['list'] as $item) {
                    // Skip empty items
                    if (empty($item['playlet_id'])) continue;
                    
                    // Extract tags (limit to 3)
                    $tags = [];
                    if (isset($item['playlet_tag_name']) && is_array($item['playlet_tag_name'])) {
                        $tags = array_slice($item['playlet_tag_name'], 0, 3);
                    }
                    
                    $playletId = strval($item['playlet_id']);
                    
                    // Avoid duplicates
                    $exists = false;
                    foreach ($dramas as $drama) {
                        if ($drama['id'] === $playletId) {
                            $exists = true;
                            break;
                        }
                    }
                    
                    if (!$exists) {
                        $dramas[] = [
                            'id' => $playletId,
                            'bookId' => $playletId,
                            'title' => $item['title'] ?? 'Unknown',
                            'poster' => $item['cover'] ?? '',
                            'rating' => 0,
                            'tags' => $tags,
                            'episodes' => intval($item['upload_num'] ?? 0),
                            'year' => date('Y'),
                            'description' => $item['introduce'] ?? ''
                        ];
                    }
                }
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'platform' => 'flickreels',
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
