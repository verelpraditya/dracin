<?php
require_once '../config.php';

header('Content-Type: application/json');

// Simple token validation
$token = $_GET['t'] ?? '';
if (empty($token) || $token !== md5(date('Ymd') . 'dre2')) {
    http_response_code(403);
    die(json_encode(['error' => 'Invalid request']));
}

try {
    // Decode parameters
    $data = base64_decode($_GET['q'] ?? '');
    parse_str($data, $params);
    
    $bookId = $params['i'] ?? '';
    $platform = $params['p'] ?? 'dramabox';
    
    if (empty($bookId)) {
        throw new Exception('Invalid parameters');
    }
    
    // FlickReels API
    if ($platform === 'flickreels') {
        $detailUrl = 'https://dramabos.asia/api/flick/drama/' . urlencode($bookId) . '?lang=6';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $detailUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200 && $response) {
            $detail = json_decode($response, true);
            if ($detail && isset($detail['data']['list'])) {
                $dramaInfo = $detail['data'];
                $episodes = [];
                
                foreach ($dramaInfo['list'] as $ep) {
                    $episodes[] = [
                        'n' => $ep['chapter_num'],
                        't' => $ep['chapter_title'] ?? 'Episode ' . $ep['chapter_num'],
                        'u' => $ep['play_url'] ?? ''
                    ];
                }
                
                // Encode response
                $result = base64_encode(json_encode([
                    's' => true,
                    'd' => [
                        'info' => [
                            't' => $dramaInfo['title'] ?? 'Drama',
                            'c' => $dramaInfo['cover'] ?? '',
                            'desc' => ''
                        ],
                        'eps' => $episodes,
                        'total' => count($episodes)
                    ]
                ]));
                
                echo json_encode(['r' => $result]);
                exit;
            }
        }
        
        throw new Exception('Failed to fetch FlickReels data');
    }
    
    // Use new API for DramaBox
    if ($platform === 'dramabox') {
        $dramaTitle = 'Drama';
        $dramaCover = '';
        $dramaDesc = '';
        $totalEpisodes = 0;
        
        // Fetch drama details from drama API
        $dramaUrl = 'https://dramabos.asia/api/dramabox/api/drama/' . urlencode($bookId) . '?lang=in';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $dramaUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200 && $response) {
            $dramaData = json_decode($response, true);
            if ($dramaData) {
                // Get drama info from API response
                $dramaTitle = $dramaData['bookName'] ?? 'Drama';
                $dramaCover = $dramaData['coverWap'] ?? $dramaData['cover'] ?? '';
                $dramaDesc = $dramaData['introduction'] ?? '';
                $totalEpisodes = intval($dramaData['chapterCount'] ?? 0);
            }
        }
        
        // Fallback to 50 if API failed
        if ($totalEpisodes <= 0) {
            $totalEpisodes = 50;
        }
        
        // Generate episodes array (episode numbers start from 1)
        $episodes = [];
        for ($i = 1; $i <= $totalEpisodes; $i++) {
            $episodes[] = [
                'n' => $i,
                't' => 'Episode ' . $i,
                'u' => ''
            ];
        }
        
        // Encode response
        $result = base64_encode(json_encode([
            's' => true,
            'd' => [
                'info' => [
                    't' => $dramaTitle,
                    'c' => $dramaCover,
                    'desc' => $dramaDesc
                ],
                'eps' => $episodes,
                'total' => $totalEpisodes
            ]
        ]));
        
        echo json_encode(['r' => $result]);
        
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
    echo json_encode(['error' => 'Request failed']);
}
