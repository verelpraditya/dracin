<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    $bookId = $_GET['bookId'] ?? '';
    $episode = intval($_GET['episode'] ?? 1);
    
    if (empty($bookId) || $episode < 1) {
        throw new Exception('bookId and episode are required');
    }
    
    // Convert episode number to 0-based index for API (Episode 1 = index 0)
    $index = $episode - 1;
    
    // Fetch video URL from new API
    $playerUrl = 'https://dramabos.asia/api/dramabox/api/watch/player?bookId=' . urlencode($bookId) . '&index=' . $index . '&lang=in';
    
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
    
    echo json_encode([
        'success' => true,
        'data' => [
            'video_url' => $videoUrl,
            'episode_number' => $episode
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch video',
        'message' => $e->getMessage()
    ]);
}
