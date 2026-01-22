<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    $bookId = $_GET['bookId'] ?? '';
    $platform = $_GET['platform'] ?? 'dramabox';
    $useNewApi = isset($_GET['newapi']) ? true : false;
    
    if (empty($bookId)) {
        throw new Exception('bookId is required');
    }
    
    // Use new API for DramaBox
    if ($platform === 'dramabox' && $useNewApi) {
        // New API: Fetch from list API to get chapterCount and drama info
        $totalFromList = intval($_GET['total'] ?? 0);
        $dramaTitle = 'Drama';
        $dramaCover = '';
        $dramaDesc = '';
        
        // If total not provided, fetch from list API
        if ($totalFromList <= 0) {
            $listUrl = 'https://dramabos.asia/api/dramabox/api/foryou/1?lang=in';
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $listUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode == 200 && $response) {
                $listData = json_decode($response, true);
                if ($listData && isset($listData['data']['list'])) {
                    // Find this drama in the list
                    foreach ($listData['data']['list'] as $item) {
                        if (isset($item['bookId']) && $item['bookId'] == $bookId) {
                            // Get drama info
                            $dramaTitle = $item['bookName'] ?? 'Drama';
                            $dramaCover = $item['cover'] ?? '';
                            $dramaDesc = $item['introduction'] ?? '';
                            
                            // API chapterCount is 1 more than actual episodes, so subtract 1
                            $totalFromList = intval($item['chapterCount'] ?? 0);
                            if ($totalFromList > 0) {
                                $totalFromList = $totalFromList - 1;
                            }
                            break;
                        }
                    }
                }
            }
            
            // Fallback to 50 if still not found
            if ($totalFromList <= 0) {
                $totalFromList = 50;
            }
        }
        
        // Generate episodes array without video URLs
        $episodes = [];
        for ($i = 1; $i <= $totalFromList; $i++) {
            $episodes[] = [
                'episode_id' => $bookId . '_' . $i,
                'episode_number' => $i,
                'title' => 'Episode ' . $i,
                'video_url' => '', // Will be fetched on-demand when playing
                'is_charge' => 0
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'drama' => [
                    'title' => $dramaTitle,
                    'cover' => $dramaCover,
                    'description' => $dramaDesc
                ],
                'episodes' => $episodes,
                'total_episodes' => $totalFromList
            ]
        ]);
        
        return;
    }
    
    // Old API logic (keep for backward compatibility)
    if ($platform === 'netshort') {
        $apiUrl = 'https://api.sansekai.my.id/api/netshort/allepisode?shortPlayId=' . urlencode($bookId);
    } else {
        $apiUrl = 'https://api.sansekai.my.id/api/dramabox/allepisode?bookId=' . urlencode($bookId);
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if (!$response || $httpCode != 200) {
        throw new Exception('Failed to fetch episodes from old API');
    }
    
    $apiData = json_decode($response, true);
    
    if (!is_array($apiData)) {
        throw new Exception('Invalid API response');
    }
    
    // Transform episodes data (old API format)
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
        foreach ($apiData as $ep) {
            $videoUrl = '';
            
            if (isset($ep['cdnList']) && is_array($ep['cdnList'])) {
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
