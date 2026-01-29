<?php
require_once '../config.php';

header('Content-Type: application/json');

// Simple token validation
$token = $_GET['t'] ?? '';
if (empty($token) || $token !== md5(date('Ymd') . 'drv2')) {
    http_response_code(403);
    die(json_encode(['error' => 'Invalid request']));
}

try {
    // Decode base64 parameters
    $data = base64_decode($_GET['q'] ?? '');
    parse_str($data, $params);
    
    $bookId = $params['i'] ?? '';
    $episode = intval($params['e'] ?? 1);
    $platform = $params['p'] ?? 'dramabox';
    
    if (empty($bookId) || $episode < 1) {
        throw new Exception('Invalid parameters');
    }
    
    // FlickReels - video URL already provided in episodes list
    if ($platform === 'flickreels') {
        // For FlickReels, the URL is already embedded in episode list
        // Just return success, actual URL will be used directly from episodes
        $result = base64_encode(json_encode([
            's' => true,
            'd' => [
                'u' => '', // Will be replaced by actual URL from episode list
                'e' => $episode
            ]
        ]));
        
        echo json_encode(['r' => $result]);
        exit;
    }
    
    // New API uses episode number directly (starts from 1)
    // URL format: https://dramabos.asia/api/dramabox/api/watch/{bookId}/{episode}?lang=in
    $playerUrl = 'https://dramabos.asia/api/dramabox/api/watch/' . urlencode($bookId) . '/' . $episode . '?lang=in';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $playerUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode != 200 || !$response) {
        throw new Exception('Failed to fetch video URL');
    }
    
    $data = json_decode($response, true);
    
    if (!$data) {
        throw new Exception('Invalid video response');
    }
    
    // New API response structure: {chapterId, name, videos: [{quality, videoPath, isDefault}]}
    $videoUrl = '';
    
    // Get video URL with best quality from videos array
    if (isset($data['videos']) && is_array($data['videos'])) {
        // Try to get 720p first (good balance), then others
        foreach ([720, 1080, 540, 360, 144] as $preferredQuality) {
            foreach ($data['videos'] as $video) {
                if (isset($video['quality']) && $video['quality'] == $preferredQuality && !empty($video['videoPath'])) {
                    $videoUrl = $video['videoPath'];
                    break 2;
                }
            }
        }
        
        // If no preferred quality found, get the default one
        if (empty($videoUrl)) {
            foreach ($data['videos'] as $video) {
                if (!empty($video['isDefault']) && !empty($video['videoPath'])) {
                    $videoUrl = $video['videoPath'];
                    break;
                }
            }
        }
        
        // Last fallback: get first available video
        if (empty($videoUrl) && count($data['videos']) > 0 && !empty($data['videos'][0]['videoPath'])) {
            $videoUrl = $data['videos'][0]['videoPath'];
        }
    }
    
    if (empty($videoUrl)) {
        throw new Exception('Video URL not found');
    }
    
    // Encode response
    $result = base64_encode(json_encode([
        's' => true,
        'd' => [
            'u' => $videoUrl,
            'e' => $episode
        ]
    ]));
    
    echo json_encode(['r' => $result]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Request failed']);
}
