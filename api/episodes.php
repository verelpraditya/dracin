<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    $bookId = $_GET['bookId'] ?? '';
    $platform = $_GET['platform'] ?? 'dramabox';
    
    if (empty($bookId)) {
        throw new Exception('bookId is required');
    }
    
    if ($platform === 'netshort') {
        $apiUrl = 'https://api.sansekai.my.id/api/netshort/allepisode?shortPlayId=' . urlencode($bookId);
    } else {
        // Fetch episodes from DramaBox API
        $apiUrl = 'https://api.sansekai.my.id/api/dramabox/allepisode?bookId=' . urlencode($bookId);
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if (!$response) {
        throw new Exception('Failed to fetch episodes: ' . ($error ?: 'No response from API'));
    }
    
    if ($httpCode != 200) {
        throw new Exception('API returned error code: ' . $httpCode);
    }
    
    $apiData = json_decode($response, true);
    
    if (!is_array($apiData)) {
        throw new Exception('Invalid API response');
    }
    
    // Transform episodes data
    $episodes = [];
    $dramaTitle = '';
    $dramaCover = '';
    $dramaDescription = '';
    $totalEpisodes = 0;
    
    if ($platform === 'netshort') {
        $dramaTitle = $apiData['shortPlayName'] ?? '';
        $dramaCover = $apiData['shortPlayCover'] ?? '';
        $dramaDescription = $apiData['shotIntroduce'] ?? '';
        
        if (isset($apiData['shortPlayEpisodeInfos']) && is_array($apiData['shortPlayEpisodeInfos'])) {
            foreach ($apiData['shortPlayEpisodeInfos'] as $ep) {
                $episodes[] = [
                    'episode_id' => $ep['episodeId'] ?? '',
                    'episode_number' => $ep['episodeNo'] ?? 0,
                    'title' => 'Episode ' . ($ep['episodeNo'] ?? 0),
                    'video_url' => $ep['playVoucher'] ?? '',
                    'is_charge' => !empty($ep['isLock']) ? 1 : 0
                ];
            }
        }
        
        $totalEpisodes = intval($apiData['totalEpisode'] ?? count($episodes));
    } else {
        // Response is direct array of episodes
        foreach ($apiData as $ep) {
            $videoUrl = '';
            
            // Get video URL from cdnList with preferred quality
            if (isset($ep['cdnList']) && is_array($ep['cdnList'])) {
                // Find default CDN or use first one
                $selectedCdn = null;
                foreach ($ep['cdnList'] as $cdn) {
                    if (isset($cdn['isDefault']) && $cdn['isDefault'] == 1) {
                        $selectedCdn = $cdn;
                        break;
                    }
                }
                if (!$selectedCdn && count($ep['cdnList']) > 0) {
                    $selectedCdn = $ep['cdnList'][0];
                }
                
                // Get preferred quality video (1080 > 720 > 540)
                if ($selectedCdn && isset($selectedCdn['videoPathList'])) {
                    foreach ([1080, 720, 540] as $quality) {
                        foreach ($selectedCdn['videoPathList'] as $video) {
                            if (isset($video['quality']) && $video['quality'] == $quality) {
                                $videoUrl = $video['videoPath'] ?? '';
                                break 2;
                            }
                        }
                    }
                }
            }
            
            $episodes[] = [
                'episode_id' => $ep['chapterId'] ?? '',
                'episode_number' => ($ep['chapterIndex'] ?? 0) + 1,
                'title' => $ep['chapterName'] ?? 'Episode ' . (($ep['chapterIndex'] ?? 0) + 1),
                'video_url' => $videoUrl,
                'is_charge' => $ep['isCharge'] ?? 0
            ];
        }
        
        if (count($episodes) > 0) {
            $dramaTitle = 'Drama Episode ' . count($episodes) . ' Episodes';
        }
        $totalEpisodes = count($episodes);
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'drama' => [
                'title' => $dramaTitle,
                'cover' => $dramaCover,
                'description' => $dramaDescription
            ],
            'episodes' => $episodes,
            'total_episodes' => $totalEpisodes
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch episodes',
        'message' => $e->getMessage()
    ]);
}
