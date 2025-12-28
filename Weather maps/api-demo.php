<?php
/**
 * Demo Weather Data Generator
 * 
 * This file generates simulated weather data for testing purposes
 * when external API access is not available
 */

header('Content-Type: application/json');

// Get request parameters
$latitude = isset($_GET['latitude']) ? floatval($_GET['latitude']) : 40.0;
$longitude = isset($_GET['longitude']) ? floatval($_GET['longitude']) : -95.0;
$parameter = isset($_GET['parameter']) ? $_GET['parameter'] : 'temperature_2m';
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'current';
$units = isset($_GET['units']) ? $_GET['units'] : 'metric';
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$hour = isset($_GET['hour']) ? intval($_GET['hour']) : 12;

// Generate demo data based on parameter and location
function generateDemoValue($parameter, $latitude, $longitude, $units) {
    // Seed random with location for consistency
    $seed = abs(intval($latitude * 1000 + $longitude * 1000));
    mt_srand($seed);
    
    switch ($parameter) {
        case 'temperature_2m':
        case 'apparent_temperature':
            // Temperature varies by latitude
            $baseTemp = 20 - ($latitude - 30) * 0.5; // Cooler at higher latitudes
            $variation = mt_rand(-10, 10);
            $temp = $baseTemp + $variation;
            return ($units === 'imperial') ? round($temp * 9/5 + 32, 1) : round($temp, 1);
            
        case 'dew_point_2m':
            $baseTemp = 15 - ($latitude - 30) * 0.4;
            return ($units === 'imperial') ? round($baseTemp * 9/5 + 32, 1) : round($baseTemp, 1);
            
        case 'precipitation':
        case 'rain':
            return round(mt_rand(0, 50) / 10, 1);
            
        case 'snowfall':
            // More snow at higher latitudes
            $snowChance = $latitude > 40 ? mt_rand(0, 20) : 0;
            return round($snowChance / 10, 1);
            
        case 'wind_speed_10m':
            $speed = mt_rand(0, 80);
            return ($units === 'imperial') ? round($speed * 0.621371, 1) : $speed;
            
        case 'wind_direction_10m':
            return mt_rand(0, 359);
            
        case 'cloud_cover':
            return mt_rand(0, 100);
            
        case 'pressure_msl':
            $pressure = mt_rand(980, 1040);
            return ($units === 'imperial') ? round($pressure * 0.02953, 2) : $pressure;
            
        case 'relative_humidity_2m':
            return mt_rand(20, 95);
            
        case 'visibility':
            $vis = mt_rand(1, 50);
            return ($units === 'imperial') ? round($vis * 0.621371, 1) : $vis;
            
        case 'weather_code':
            $codes = [0, 1, 2, 3, 45, 51, 61, 71, 80, 95];
            return $codes[mt_rand(0, count($codes) - 1)];
            
        default:
            return mt_rand(0, 100);
    }
}

$value = generateDemoValue($parameter, $latitude, $longitude, $units);

// Current time or specified historical time
$time = ($mode === 'historical') 
    ? $date . 'T' . str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00:00Z'
    : date('Y-m-d\TH:i:s\Z');

$result = [
    'value' => $value,
    'time' => $time,
    'latitude' => $latitude,
    'longitude' => $longitude,
    'parameter' => $parameter,
    'units' => $units,
    'demo_mode' => true
];

if ($mode === 'historical') {
    $result['date'] = $date;
    $result['hour'] = $hour;
}

echo json_encode($result);
?>
