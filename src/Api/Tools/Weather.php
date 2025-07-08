<?php

// src/Api/Tools/Weather.php - UPDATED with Tool base class

require_once __DIR__ . '/../Models/Tool.php';

class Weather extends Tool {
    
    public function getName(): string {
        return 'weather';
    }
    
    public function getDescription(): string {
        return 'Get current weather information for any city or location.';
    }
    
    public function getParametersSchema(): array {
        return [
            'location' => [
                'type' => 'string',
                'description' => 'City name, state, or country (e.g., "New York", "London, UK", "Tokyo")',
                'required' => true
            ],
            'unit' => [
                'type' => 'string',
                'description' => 'Temperature unit: "celsius", "fahrenheit", or "kelvin"',
                'required' => false
            ]
        ];
    }
    
    public function execute(array $parameters): array {
        $location = $parameters['location'];
        $unit = $parameters['unit'] ?? 'celsius';
        
        try {
            // Simple weather simulation
            $weatherData = $this->getSimulatedWeather($location, $unit);
            
            return [
                'success' => true,
                'location' => $location,
                'weather' => $weatherData,
                'tool' => $this->getName()
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Weather lookup failed: ' . $e->getMessage(),
                'location' => $location,
                'tool' => $this->getName()
            ];
        }
    }
    
    private function getSimulatedWeather($location, $unit) {
        // Simple weather simulation without external dependencies
        $cities = [
            'london' => ['temp' => 15, 'condition' => 'Cloudy', 'humidity' => 80],
            'paris' => ['temp' => 18, 'condition' => 'Partly Cloudy', 'humidity' => 70],
            'tokyo' => ['temp' => 28, 'condition' => 'Sunny', 'humidity' => 55],
            'new york' => ['temp' => 22, 'condition' => 'Partly Cloudy', 'humidity' => 65],
            'sydney' => ['temp' => 25, 'condition' => 'Sunny', 'humidity' => 60],
            'moscow' => ['temp' => 5, 'condition' => 'Snow', 'humidity' => 85],
            'russia' => ['temp' => 2, 'condition' => 'Cold', 'humidity' => 90],
            'amsterdam' => ['temp' => 12, 'condition' => 'Rainy', 'humidity' => 85],
            'berlin' => ['temp' => 16, 'condition' => 'Overcast', 'humidity' => 75],
            'madrid' => ['temp' => 24, 'condition' => 'Sunny', 'humidity' => 45],
            'rome' => ['temp' => 26, 'condition' => 'Clear', 'humidity' => 50]
        ];
        
        $locationKey = strtolower($location);
        
        // Check for exact or partial match
        $weather = null;
        foreach ($cities as $city => $data) {
            if ($city === $locationKey || strpos($locationKey, $city) !== false || strpos($city, $locationKey) !== false) {
                $weather = $data;
                break;
            }
        }
        
        // Default weather if location not found
        if (!$weather) {
            $weather = [
                'temp' => rand(10, 25),
                'condition' => 'Partly Cloudy',
                'humidity' => rand(50, 80)
            ];
        }
        
        // Convert temperature based on unit
        $temp = $weather['temp'];
        switch (strtolower($unit)) {
            case 'fahrenheit':
            case 'f':
                $temp = ($temp * 9/5) + 32;
                $unitSymbol = '°F';
                break;
            case 'kelvin':
            case 'k':
                $temp = $temp + 273.15;
                $unitSymbol = 'K';
                break;
            default:
                $unitSymbol = '°C';
                break;
        }
        
        return [
            'temperature' => round($temp, 1) . $unitSymbol,
            'condition' => $weather['condition'],
            'humidity' => $weather['humidity'] . '%',
            'description' => "Current weather in {$location}: {$weather['condition']}, " . round($temp, 1) . $unitSymbol . ", {$weather['humidity']}% humidity",
            'last_updated' => date('Y-m-d H:i:s')
        ];
    }
}