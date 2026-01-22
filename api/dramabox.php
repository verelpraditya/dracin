<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    // Fetch data from real DramaBox API
    $apiUrl = 'https://api.sansekai.my.id/api/dramabox/vip';
    
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
    
    if (!$apiData) {
        throw new Exception('Invalid JSON response from API');
    }
    
    // Transform API data to our format
    $dramas = [];
    
    // API returns object with columnVoList containing bookList arrays
    if (isset($apiData['columnVoList']) && is_array($apiData['columnVoList'])) {
        foreach ($apiData['columnVoList'] as $column) {
            if (isset($column['bookList']) && is_array($column['bookList'])) {
                foreach ($column['bookList'] as $item) {
                    // Extract tags from tagV3s array
                    $tags = [];
                    if (isset($item['tagV3s']) && is_array($item['tagV3s'])) {
                        foreach ($item['tagV3s'] as $tagObj) {
                            if (isset($tagObj['tagName'])) {
                                $tags[] = $tagObj['tagName'];
                            }
                        }
                    }
                    
                    if (empty($tags)) {
                        $tags = ['Drama'];
                    }
                    
                    // Only add if has valid bookId and not duplicate
                    $bookId = $item['bookId'] ?? '';
                    if (!empty($bookId)) {
                        // Check if already added (to avoid duplicates)
                        $exists = false;
                        foreach ($dramas as $drama) {
                            if ($drama['bookId'] === $bookId) {
                                $exists = true;
                                break;
                            }
                        }
                        
                        if (!$exists) {
                            $dramas[] = [
                                'id' => $bookId,
                                'bookId' => $bookId,
                                'title' => $item['bookName'] ?? 'Unknown',
                                'poster' => $item['coverWap'] ?? $item['coverMap'] ?? '',
                                'rating' => 0, // API doesn't provide rating in this response
                                'tags' => array_slice($tags, 0, 3), // Max 3 tags
                                'episodes' => intval($item['chapterCount'] ?? 0),
                                'year' => date('Y'),
                                'description' => $item['introduction'] ?? ''
                            ];
                        }
                    }
                }
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

