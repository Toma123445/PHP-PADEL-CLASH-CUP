<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

/**
 * Fetches and parses weather data from external API (example).
 * In production, replace with actual API.
 */
function get_weather_data(string $city = 'Bucharest'): array
{
    // Example: Using OpenWeatherMap API (you need to get an API key)
    // For demo purposes, we'll use a mock response
    
    $api_key = 'your-api-key-here'; // Replace with actual API key
    $url = "https://api.openweathermap.org/data/2.5/weather?q={$city}&appid={$api_key}&units=metric&lang=ro";
    
    // For demo, return mock data
    // In production, uncomment the following:
    /*
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        return json_decode($response, true);
    }
    */
    
    // Mock data for demonstration
    return [
        'name' => $city,
        'main' => [
            'temp' => 15,
            'feels_like' => 14,
            'humidity' => 65
        ],
        'weather' => [
            [
                'main' => 'Clear',
                'description' => 'Cer senin',
                'icon' => '01d'
            ]
        ],
        'wind' => [
            'speed' => 3.5
        ]
    ];
}

/**
 * Fetches sports news from external RSS feed.
 */
function get_sports_news(int $limit = 5): array
{
    // Example RSS feed (replace with actual sports news feed)
    $rss_url = 'https://www.sport.ro/rss';
    
    // For demo, return mock data
    // In production, parse actual RSS:
    /*
    $rss = simplexml_load_file($rss_url);
    if ($rss === false) {
        return [];
    }
    
    $news = [];
    $count = 0;
    foreach ($rss->channel->item as $item) {
        if ($count >= $limit) break;
        $news[] = [
            'title' => (string)$item->title,
            'link' => (string)$item->link,
            'description' => (string)$item->description,
            'date' => (string)$item->pubDate
        ];
        $count++;
    }
    return $news;
    */
    
    // Mock data
    return [
        [
            'title' => 'Turneu de padel la Bucuresti',
            'description' => 'Se organizeaza un turneu important de padel in capitala.',
            'date' => date('Y-m-d')
        ],
        [
            'title' => 'Rezultate competitie padel',
            'description' => 'Vezi rezultatele ultimei competitii de padel.',
            'date' => date('Y-m-d', strtotime('-1 day'))
        ]
    ];
}

/**
 * Fetches tournament statistics from external source.
 */
function get_tournament_stats(): array
{
    // This could fetch from an external API or database
    // For now, return structured data that can be used for charts
    return [
        'total_teams' => 12,
        'total_matches' => 30,
        'completed_matches' => 18,
        'upcoming_matches' => 12,
        'average_score' => 6.5
    ];
}

/**
 * Parses external data and formats it for use in the application.
 */
function parse_external_data(string $source, array $data): array
{
    $parsed = [];
    
    switch ($source) {
        case 'weather':
            $parsed = [
                'temperature' => $data['main']['temp'] ?? 0,
                'condition' => $data['weather'][0]['description'] ?? 'Necunoscut',
                'humidity' => $data['main']['humidity'] ?? 0,
                'wind_speed' => $data['wind']['speed'] ?? 0
            ];
            break;
            
        case 'news':
            $parsed = array_map(function($item) {
                return [
                    'title' => $item['title'] ?? '',
                    'description' => substr($item['description'] ?? '', 0, 100) . '...',
                    'date' => $item['date'] ?? ''
                ];
            }, $data);
            break;
            
        case 'stats':
            $parsed = $data;
            break;
    }
    
    return $parsed;
}

