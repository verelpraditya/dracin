<?php
require_once '../config.php';

header('Content-Type: application/json');

function extractNetshortTags($item)
{
    $tags = [];

    if (is_array($item) && isset($item['labelArray']) && is_array($item['labelArray'])) {
        foreach ($item['labelArray'] as $tag) {
            if (is_string($tag) && $tag !== '') {
                $tags[] = $tag;
            }
        }
    }

    if (empty($tags) && is_array($item) && isset($item['shortPlayLabels']) && is_string($item['shortPlayLabels'])) {
        $decoded = json_decode($item['shortPlayLabels'], true);
        if (is_array($decoded)) {
            foreach ($decoded as $tag) {
                if (is_string($tag) && $tag !== '') {
                    $tags[] = $tag;
                }
            }
        } else {
            $parts = preg_split('/[,|;]/', $item['shortPlayLabels']);
            if (is_array($parts)) {
                foreach ($parts as $tag) {
                    $tag = trim($tag, " \t\n\r\0\x0B\"[]");
                    if ($tag !== '') {
                        $tags[] = $tag;
                    }
                }
            }
        }
    }

    if (empty($tags) && is_array($item) && isset($item['scriptName']) && is_string($item['scriptName']) && $item['scriptName'] !== '') {
        $tags[] = $item['scriptName'];
    }

    if (empty($tags)) {
        $tags = ['Short Play'];
    }

    return array_slice(array_values(array_unique($tags)), 0, 3);
}

function getNetshortPoster($item)
{
    if (is_array($item) && !empty($item['highImage']) && !empty($item['isNeedHighImage'])) {
        return $item['highImage'];
    }

    if (!is_array($item)) {
        return '';
    }

    return isset($item['shortPlayCover']) ? $item['shortPlayCover'] : (isset($item['groupShortPlayCover']) ? $item['groupShortPlayCover'] : '');
}

function getNetshortYear($item)
{
    if (is_array($item) && !empty($item['publishTime'])) {
        $timestamp = intval($item['publishTime']);
        if ($timestamp > 0) {
            return date('Y', intval($timestamp / 1000));
        }
    }

    return date('Y');
}

try {
    // Get page parameter (default to 1)
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    if ($page < 1) $page = 1;
    
    // Fetch data from new NetShort API with pagination
    $apiUrl = 'https://api.sansekai.my.id/api/netshort/foryou?page=' . $page;

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

    if (!is_array($apiData)) {
        throw new Exception('Invalid JSON response from API');
    }

    // Transform API data to our format
    $shortPlays = [];
    $seen = [];
    $hasMore = false;
    $maxOffset = 0;

    // New API structure: contentInfos array contains the items
    if (isset($apiData['contentInfos']) && is_array($apiData['contentInfos'])) {
        foreach ($apiData['contentInfos'] as $item) {
            $shortPlayId = $item['shortPlayId'] ?? $item['shortPlayLibraryId'] ?? $item['id'] ?? '';
            if ($shortPlayId === '') {
                continue;
            }

            if (isset($seen[$shortPlayId])) {
                continue;
            }

            $seen[$shortPlayId] = true;

            $shortPlays[] = [
                'id' => $shortPlayId,
                'bookId' => $shortPlayId,
                'title' => $item['shortPlayName'] ?? 'Unknown',
                'poster' => getNetshortPoster($item),
                'rating' => 0,
                'tags' => extractNetshortTags($item),
                'episodes' => 1,
                'year' => getNetshortYear($item),
                'description' => '',
                'heatScore' => $item['heatScoreShow'] ?? ''
            ];
        }
        
        // Check if there's more data
        $maxOffset = intval($apiData['maxOffset'] ?? 0);
        $hasMore = !($apiData['completed'] ?? true);
    }

    echo json_encode([
        'success' => true,
        'platform' => 'netshort',
        'data' => $shortPlays,
        'total' => count($shortPlays),
        'pagination' => [
            'current' => $page,
            'hasMore' => $hasMore,
            'maxOffset' => $maxOffset
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch data',
        'message' => $e->getMessage()
    ]);
}
