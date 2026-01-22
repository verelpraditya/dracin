<?php
require_once '../config.php';

header('Content-Type: application/json');

// This is a mock API endpoint for MELOLO
// Replace this with your actual API integration

try {
    // Mock data for MELOLO platform
    $mockData = [
        [
            'id' => 1,
            'title' => 'Melody of Love',
            'poster' => 'https://via.placeholder.com/300x450/ffd93d/333333?text=Melolo+1',
            'rating' => 8.9,
            'tags' => ['Music', 'Romance'],
            'episodes' => 50,
            'year' => 2024,
            'description' => 'Cinta yang terjalin melalui musik'
        ],
        [
            'id' => 2,
            'title' => 'Golden Voice',
            'poster' => 'https://via.placeholder.com/300x450/fcbf49/333333?text=Melolo+2',
            'rating' => 9.2,
            'tags' => ['Music', 'Drama'],
            'episodes' => 45,
            'year' => 2024,
            'description' => 'Perjalanan seorang penyanyi berbakat'
        ],
        [
            'id' => 3,
            'title' => 'Rhythm of Life',
            'poster' => 'https://via.placeholder.com/300x450/f77f00/ffffff?text=Melolo+3',
            'rating' => 8.4,
            'tags' => ['Music', 'Life'],
            'episodes' => 42,
            'year' => 2024,
            'description' => 'Hidup mengikuti irama yang indah'
        ],
        [
            'id' => 4,
            'title' => 'Band Brothers',
            'poster' => 'https://via.placeholder.com/300x450/edb31d/333333?text=Melolo+4',
            'rating' => 7.9,
            'tags' => ['Music', 'Brotherhood'],
            'episodes' => 38,
            'year' => 2024,
            'description' => 'Persaudaraan dalam sebuah band'
        ],
        [
            'id' => 5,
            'title' => 'Singing Star',
            'poster' => 'https://via.placeholder.com/300x450/f4a261/333333?text=Melolo+5',
            'rating' => 8.6,
            'tags' => ['Music', 'Competition'],
            'episodes' => 48,
            'year' => 2024,
            'description' => 'Kompetisi menyanyi yang sengit'
        ],
        [
            'id' => 6,
            'title' => 'Harmony Hearts',
            'poster' => 'https://via.placeholder.com/300x450/ffb627/333333?text=Melolo+6',
            'rating' => 9.0,
            'tags' => ['Music', 'Romance'],
            'episodes' => 52,
            'year' => 2024,
            'description' => 'Harmoni cinta dan musik'
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'platform' => 'melolo',
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
