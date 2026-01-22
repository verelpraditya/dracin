<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    $platform = $_GET['platform'] ?? 'dramabox';
    $query = $_GET['query'] ?? '';
    
    if (empty($query)) {
        throw new Exception('Query parameter is required');
    }
    
    if (!isset($platforms[$platform])) {
        throw new Exception('Invalid platform');
    }
    
    // Map platform to API endpoint
    $useNewApi = USE_NEW_DRAMABOX_API && $platform === 'dramabox';
    
    if ($useNewApi) {
        // New DramaBox API - use new search endpoint
        $apiUrl = 'https://dramabos.asia/api/dramabox/api/search/' . urlencode($query) . '/1?lang=in&pageSize=20';
    } else {
        $apiUrls = [
            'dramabox' => 'https://api.sansekai.my.id/api/dramabox/search?query=' . urlencode($query),
            'netshort' => 'https://api.sansekai.my.id/api/netshort/search?query=' . urlencode($query)
        ];
        
        if (!isset($apiUrls[$platform])) {
            throw new Exception('Search not available for this platform yet');
        }
        
        $apiUrl = $apiUrls[$platform];
    }
    
    // Fetch from API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode != 200 || !$response) {
        throw new Exception('Failed to fetch from API: ' . $error . ' (HTTP ' . $httpCode . ')');
    }
    
    $apiData = json_decode($response, true);
    
    if (!is_array($apiData)) {
        throw new Exception('Invalid API response');
    }
    
    // Transform data based on platform
    $results = [];
    
    if ($platform === 'dramabox') {
        // New API response structure
        if ($useNewApi) {
            $items = [];
            if (isset($apiData['data']['list']) && is_array($apiData['data']['list'])) {
                $items = $apiData['data']['list'];
            }
            
            foreach ($items as $item) {
                if (!is_array($item)) continue;
                
                $tags = [];
                if (isset($item['tags']) && is_array($item['tags'])) {
                    $tags = array_slice($item['tags'], 0, 3);
                }
                
                // API chapterCount is 1 more than actual episodes
                $chapterCount = intval($item['chapterCount'] ?? 0);
                if ($chapterCount > 0) {
                    $chapterCount = $chapterCount - 1;
                }
                
                $results[] = [
                    'id' => $item['bookId'] ?? '',
                    'bookId' => $item['bookId'] ?? '',
                    'title' => $item['bookName'] ?? 'Unknown',
                    'poster' => $item['cover'] ?? '',
                    'rating' => 0,
                    'tags' => $tags,
                    'episodes' => $chapterCount,
                    'year' => date('Y'),
                    'description' => $item['introduction'] ?? ''
                ];
            }
        } else {
            // Old API response structure
            $items = $apiData;
            
            // If it's wrapped in 'value' key, use that instead
            if (isset($apiData['value']) && is_array($apiData['value'])) {
                $items = $apiData['value'];
            }
            
            if (is_array($items)) {
                foreach ($items as $item) {
                    if (!is_array($item)) continue;
                    
                    $tags = [];
                    if (isset($item['tagNames']) && is_array($item['tagNames'])) {
                        $tags = array_slice($item['tagNames'], 0, 3);
                    }
                    
                    $results[] = [
                        'id' => $item['bookId'] ?? '',
                        'bookId' => $item['bookId'] ?? '',
                        'title' => $item['bookName'] ?? 'Unknown',
                        'poster' => $item['cover'] ?? '',
                        'rating' => 0,
                        'tags' => $tags,
                        'episodes' => 0,
                        'year' => date('Y'),
                        'description' => $item['introduction'] ?? ''
                    ];
                }
            }
        }
    } elseif ($platform === 'netshort') {
        if (isset($apiData['searchCodeSearchResult']) && is_array($apiData['searchCodeSearchResult'])) {
            foreach ($apiData['searchCodeSearchResult'] as $item) {
                $tags = [];
                if (isset($item['labelNameList']) && is_array($item['labelNameList'])) {
                    $tags = array_slice($item['labelNameList'], 0, 3);
                }
                
                $results[] = [
                    'id' => $item['shortPlayId'] ?? '',
                    'bookId' => $item['shortPlayId'] ?? '',
                    'title' => $item['shortPlayName'] ?? 'Unknown',
                    'poster' => $item['shortPlayCover'] ?? '',
                    'rating' => 0,
                    'tags' => $tags,
                    'episodes' => 1,
                    'year' => date('Y'),
                    'description' => ''
                ];
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'platform' => $platform,
        'query' => $query,
        'data' => $results,
        'total' => count($results)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Search failed',
        'message' => $e->getMessage()
    ]);
}
