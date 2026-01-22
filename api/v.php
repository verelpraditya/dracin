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
    
    // Fetch video URL from new API (DramaBox)
    $playerUrl = 'https://dramabos.asia/api/dramabox/api/watch/player?bookId=' . urlencode($bookId) . '&index=' . $episode . '&lang=in';
    
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
    
    if (!$data || !isset($data['success']) || !$data['success']) {
        throw new Exception('Invalid video response');
    }
    
    $episodeData = $data['data'] ?? [];
    
    // Get video URL with best quality
    $videoUrl = $episodeData['videoUrl'] ?? '';
    
    // Try to get better quality from qualities array
    if (isset($episodeData['qualities']) && is_array($episodeData['qualities'])) {
        foreach ([1080, 720, 540] as $quality) {
            foreach ($episodeData['qualities'] as $q) {
                if (isset($q['quality']) && $q['quality'] == $quality && isset($q['videoPath'])) {
                    $videoUrl = $q['videoPath'];
                    break 2;
                }
            }
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
