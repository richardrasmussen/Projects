<?php
/**
 * Weather Maps API Proxy
 * 
 * This file acts as a proxy between the frontend and Open-Meteo API
 * Handles both current and historical weather data requests
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to user
ini_set('log_errors', 1);

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Get request parameters
$latitude = isset($_GET['latitude']) ? floatval($_GET['latitude']) : null;
$longitude = isset($_GET['longitude']) ? floatval($_GET['longitude']) : null;
$parameter = isset($_GET['parameter']) ? $_GET['parameter'] : 'temperature_2m';
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'current';
$units = isset($_GET['units']) ? $_GET['units'] : 'metric';
$date = isset($_GET['date']) ? $_GET['date'] : null;
$hour = isset($_GET['hour']) ? intval($_GET['hour']) : 12;

// Validate required parameters
if ($latitude === null || $longitude === null) {
    echo json_encode(['error' => 'Missing latitude or longitude']);
    exit;
}

// Validate latitude and longitude ranges
if ($latitude < -90 || $latitude > 90) {
    echo json_encode(['error' => 'Invalid latitude. Must be between -90 and 90']);
    exit;
}

if ($longitude < -180 || $longitude > 180) {
    echo json_encode(['error' => 'Invalid longitude. Must be between -180 and 180']);
    exit;
}

// Map parameters to API parameter names
$parameterMap = [
    'temperature_2m' => 'temperature_2m',
    'precipitation' => 'precipitation',
    'rain' => 'rain',
    'snowfall' => 'snowfall',
    'wind_speed_10m' => 'wind_speed_10m',
    'wind_direction_10m' => 'wind_direction_10m',
    'cloud_cover' => 'cloud_cover',
    'pressure_msl' => 'pressure_msl',
    'relative_humidity_2m' => 'relative_humidity_2m',
    'visibility' => 'visibility',
    'weather_code' => 'weather_code',
    'dew_point_2m' => 'dew_point_2m',
    'apparent_temperature' => 'apparent_temperature'
];

// Check if parameter is valid
if (!isset($parameterMap[$parameter])) {
    echo json_encode(['error' => 'Invalid weather parameter']);
    exit;
}

$apiParameter = $parameterMap[$parameter];

// Determine units for API
$temperatureUnit = ($units === 'imperial') ? 'fahrenheit' : 'celsius';
$windSpeedUnit = ($units === 'imperial') ? 'mph' : 'kmh';
$precipitationUnit = ($units === 'imperial') ? 'inch' : 'mm';

/**
 * Fetch data from URL using cURL or file_get_contents
 */
function fetchUrl($url) {
    // Try cURL first
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($response !== false) {
            return $response;
        }
        
        // If cURL failed, try file_get_contents
        if (ini_get('allow_url_fopen')) {
            $response = @file_get_contents($url);
            if ($response !== false) {
                return $response;
            }
        }
        
        throw new Exception('Failed to fetch data: ' . ($error ?: 'Unknown error'));
    }
    
    // Fallback to file_get_contents
    if (ini_get('allow_url_fopen')) {
        $response = @file_get_contents($url);
        if ($response !== false) {
            return $response;
        }
        throw new Exception('Failed to fetch data. Check allow_url_fopen setting.');
    }
    
    throw new Exception('No method available to fetch external data (cURL or file_get_contents)');
}

try {
    if ($mode === 'current') {
        // Current weather API
        $apiUrl = "https://api.open-meteo.com/v1/forecast";
        $params = [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'current' => $apiParameter,
            'temperature_unit' => $temperatureUnit,
            'wind_speed_unit' => $windSpeedUnit,
            'precipitation_unit' => $precipitationUnit,
            'timezone' => 'UTC'
        ];
        
        $url = $apiUrl . '?' . http_build_query($params);
        $response = fetchUrl($url);
        
        $data = json_decode($response, true);
        
        if (!$data) {
            throw new Exception('Invalid JSON response from API');
        }
        
        if (isset($data['error'])) {
            throw new Exception($data['reason'] ?? 'API error');
        }
        
        if (!isset($data['current'][$apiParameter])) {
            throw new Exception('Parameter not found in API response');
        }
        
        $result = [
            'value' => $data['current'][$apiParameter],
            'time' => $data['current']['time'] ?? 'Current',
            'latitude' => $latitude,
            'longitude' => $longitude,
            'parameter' => $parameter,
            'units' => $units
        ];
        
    } else {
        // Historical weather API
        if (!$date) {
            throw new Exception('Date is required for historical data');
        }
        
        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new Exception('Invalid date format. Use YYYY-MM-DD');
        }
        
        $apiUrl = "https://archive-api.open-meteo.com/v1/archive";
        $params = [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'start_date' => $date,
            'end_date' => $date,
            'hourly' => $apiParameter,
            'temperature_unit' => $temperatureUnit,
            'wind_speed_unit' => $windSpeedUnit,
            'precipitation_unit' => $precipitationUnit,
            'timezone' => 'UTC'
        ];
        
        $url = $apiUrl . '?' . http_build_query($params);
        $response = fetchUrl($url);
        
        $data = json_decode($response, true);
        
        if (!$data) {
            throw new Exception('Invalid JSON response from API');
        }
        
        if (isset($data['error'])) {
            throw new Exception($data['reason'] ?? 'API error');
        }
        
        if (!isset($data['hourly'][$apiParameter])) {
            throw new Exception('Parameter not found in historical API response');
        }
        
        // Get the specific hour
        if ($hour < 0 || $hour > 23) {
            throw new Exception('Invalid hour. Must be between 0 and 23');
        }
        
        $hourlyData = $data['hourly'][$apiParameter];
        $hourlyTime = $data['hourly']['time'];
        
        // Find the index for the specified hour
        $value = null;
        $time = null;
        
        for ($i = 0; $i < count($hourlyTime); $i++) {
            $timestamp = $hourlyTime[$i];
            $hourFromTimestamp = intval(date('H', strtotime($timestamp)));
            
            if ($hourFromTimestamp === $hour) {
                $value = $hourlyData[$i];
                $time = $timestamp;
                break;
            }
        }
        
        if ($value === null) {
            throw new Exception('No data available for the specified hour');
        }
        
        $result = [
            'value' => $value,
            'time' => $time,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'parameter' => $parameter,
            'units' => $units,
            'date' => $date,
            'hour' => $hour
        ];
    }
    
    // Return successful response
    echo json_encode($result);
    
} catch (Exception $e) {
    // Return error response
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
?>
