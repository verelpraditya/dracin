<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    $bookId = $_GET['bookId'] ?? '';
    $episode = intval($_GET['episode'] ?? 1);
    
    if (empty($bookId) || $episode < 1) {
        throw new Exception('bookId and episode are required');
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
        // Sort by quality to get highest quality first
        $videos = $data['videos'];
        usort($videos, function($a, $b) {
            return ($b['quality'] ?? 0) - ($a['quality'] ?? 0);
        });
        
        // Try to get 720p first (good balance of quality and compatibility), then others
        foreach ([720, 1080, 540, 360, 144] as $preferredQuality) {
            foreach ($videos as $video) {
                if (isset($video['quality']) && $video['quality'] == $preferredQuality && !empty($video['videoPath'])) {
                    $videoUrl = $video['videoPath'];
                    break 2;
                }
            }
        }
        
        // If no preferred quality found, get the default one or first available
        if (empty($videoUrl)) {
            foreach ($videos as $video) {
                if (!empty($video['isDefault']) && !empty($video['videoPath'])) {
                    $videoUrl = $video['videoPath'];
                    break;
                }
            }
        }
        
        // Last fallback: get first available video
        if (empty($videoUrl) && count($videos) > 0 && !empty($videos[0]['videoPath'])) {
            $videoUrl = $videos[0]['videoPath'];
        }
    }
    
    if (empty($videoUrl)) {
        throw new Exception('Video URL not found');
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'video_url' => $videoUrl,
            'episode_number' => $episode,
            'chapter_id' => $data['chapterId'] ?? '',
            'chapter_name' => $data['name'] ?? 'Episode ' . $episode
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
