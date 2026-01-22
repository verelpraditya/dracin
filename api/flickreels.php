<?php
require_once '../config.php';

header('Content-Type: application/json');

// This is a mock API endpoint for FlickReels
// Replace this with your actual API integration

try {
    // Mock data for FlickReels platform
    $mockData = [
        [
            'id' => 1,
            'title' => 'Flick Action Hero',
            'poster' => 'https://via.placeholder.com/300x450/f9f871/333333?text=FlickReels+1',
            'rating' => 8.7,
            'tags' => ['Action', 'Hero'],
            'episodes' => 60,
            'year' => 2024,
            'description' => 'Aksi pahlawan yang spektakuler'
        ],
        [
            'id' => 2,
            'title' => 'Reel Romance',
            'poster' => 'https://via.placeholder.com/300x450/ffed4e/333333?text=FlickReels+2',
            'rating' => 9.1,
            'tags' => ['Romance', 'Youth'],
            'episodes' => 55,
            'year' => 2024,
            'description' => 'Romansa yang terlihat sempurna'
        ],
        [
            'id' => 3,
            'title' => 'Quick Revenge',
            'poster' => 'https://via.placeholder.com/300x450/ffe66d/333333?text=FlickReels+3',
            'rating' => 8.3,
            'tags' => ['Action', 'Revenge'],
            'episodes' => 48,
            'year' => 2024,
            'description' => 'Balas dendam yang cepat dan mematikan'
        ],
        [
            'id' => 4,
            'title' => 'Flash Love',
            'poster' => 'https://via.placeholder.com/300x450/fff68f/333333?text=FlickReels+4',
            'rating' => 7.8,
            'tags' => ['Romance', 'Comedy'],
            'episodes' => 40,
            'year' => 2024,
            'description' => 'Cinta kilat yang lucu'
        ],
        [
            'id' => 5,
            'title' => 'Reel Life',
            'poster' => 'https://via.placeholder.com/300x450/f7ea3d/333333?text=FlickReels+5',
            'rating' => 8.5,
            'tags' => ['Life', 'Drama'],
            'episodes' => 52,
            'year' => 2024,
            'description' => 'Kehidupan nyata dalam format pendek'
        ],
        [
            'id' => 6,
            'title' => 'Fast Forward Love',
            'poster' => 'https://via.placeholder.com/300x450/ffec5c/333333?text=FlickReels+6',
            'rating' => 8.9,
            'tags' => ['Romance', 'Time Travel'],
            'episodes' => 45,
            'year' => 2024,
            'description' => 'Cinta yang melompat ke depan'
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'platform' => 'flickreels',
        'data' => $mockData,
        'total' => count($mockData)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch data',
        'message' => $e->getMessage()
    ]);
}
