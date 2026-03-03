<?php
/**
 * Space Weather API Proxy
 * Proxies requests to NOAA SWPC services to avoid CORS issues.
 * Usage: api.php?endpoint=planetary_k_index_1m.json
 *        api.php?endpoint=goes/primary/xrays-6-hour.json
 *        api.php?action=list  (returns known endpoint catalog)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=60');

$BASE_URL = 'https://services.swpc.noaa.gov';

// Known SWPC JSON endpoints organized by category
$ENDPOINT_CATALOG = [
    'solar_wind' => [
        'label' => 'Solar Wind',
        'endpoints' => [
            ['id' => 'rtsw/rtsw_wind_1m.json', 'label' => 'Real-Time Solar Wind (1-min)', 'description' => 'Plasma speed, density, temperature from DSCOVR'],
            ['id' => 'rtsw/rtsw_wind_5m.json', 'label' => 'Real-Time Solar Wind (5-min)', 'description' => 'Plasma data averaged over 5 minutes'],
            ['id' => 'rtsw/rtsw_mag_1m.json', 'label' => 'Interplanetary Magnetic Field (1-min)', 'description' => 'Bx, By, Bz magnetic field components'],
            ['id' => 'rtsw/rtsw_mag_5m.json', 'label' => 'Interplanetary Magnetic Field (5-min)', 'description' => 'Magnetic field averaged over 5 minutes'],
        ]
    ],
    'geomagnetic' => [
        'label' => 'Geomagnetic Activity',
        'endpoints' => [
            ['id' => 'planetary_k_index_1m.json', 'label' => 'Planetary K-Index (1-min)', 'description' => 'Real-time estimated Kp index'],
            ['id' => 'boulder_k_index_1m.json', 'label' => 'Boulder K-Index (1-min)', 'description' => 'K-index from Boulder, Colorado'],
            ['id' => 'estimated_kp.json', 'label' => 'Estimated Kp', 'description' => 'Current estimated planetary Kp'],
        ]
    ],
    'goes_xray' => [
        'label' => 'GOES X-Ray Flux',
        'endpoints' => [
            ['id' => 'goes/primary/xrays-1-day.json', 'label' => 'X-Ray Flux (1-day)', 'description' => 'GOES primary satellite X-ray flux - 1 day'],
            ['id' => 'goes/primary/xrays-3-day.json', 'label' => 'X-Ray Flux (3-day)', 'description' => 'GOES primary satellite X-ray flux - 3 days'],
            ['id' => 'goes/primary/xrays-7-day.json', 'label' => 'X-Ray Flux (7-day)', 'description' => 'GOES primary satellite X-ray flux - 7 days'],
        ]
    ],
    'goes_proton' => [
        'label' => 'GOES Proton Flux',
        'endpoints' => [
            ['id' => 'goes/primary/integral-protons-1-day.json', 'label' => 'Proton Flux (1-day)', 'description' => 'Energetic proton flux - 1 day'],
            ['id' => 'goes/primary/integral-protons-3-day.json', 'label' => 'Proton Flux (3-day)', 'description' => 'Energetic proton flux - 3 days'],
            ['id' => 'goes/primary/integral-protons-7-day.json', 'label' => 'Proton Flux (7-day)', 'description' => 'Energetic proton flux - 7 days'],
        ]
    ],
    'goes_magnetometer' => [
        'label' => 'GOES Magnetometer',
        'endpoints' => [
            ['id' => 'goes/primary/magnetometers-1-day.json', 'label' => 'Magnetometer (1-day)', 'description' => 'Geosynchronous magnetic field - 1 day'],
            ['id' => 'goes/primary/magnetometers-3-day.json', 'label' => 'Magnetometer (3-day)', 'description' => 'Geosynchronous magnetic field - 3 days'],
            ['id' => 'goes/primary/magnetometers-7-day.json', 'label' => 'Magnetometer (7-day)', 'description' => 'Geosynchronous magnetic field - 7 days'],
        ]
    ],
    'solar' => [
        'label' => 'Solar Activity',
        'endpoints' => [
            ['id' => 'solar-cycle/predicted-solar-cycle.json', 'label' => 'Predicted Solar Cycle', 'description' => 'Monthly sunspot number and F10.7 predictions'],
            ['id' => 'solar-cycle/observed-solar-cycle-indices.json', 'label' => 'Observed Solar Cycle Indices', 'description' => 'Historical observed sunspot numbers and F10.7'],
            ['id' => 'f107_cm_flux.json', 'label' => 'F10.7 cm Radio Flux', 'description' => '10.7 cm solar radio flux measurements'],
            ['id' => 'sunspot_report.json', 'label' => 'Sunspot Report', 'description' => 'Current sunspot observations and regions'],
        ]
    ],
    'alerts' => [
        'label' => 'Alerts & Warnings',
        'endpoints' => [
            ['id' => 'alerts.json', 'label' => 'All Alerts', 'description' => 'Space weather alerts, watches and warnings'],
        ]
    ],
    'aurora' => [
        'label' => 'Aurora Forecast',
        'endpoints' => [
            ['id' => 'ovation_aurora_latest.json', 'label' => 'Aurora Forecast (Latest)', 'description' => 'OVATION model aurora probability map data'],
        ]
    ],
    'enlil' => [
        'label' => 'ENLIL Model',
        'endpoints' => [
            ['id' => 'enlil_time_series.json', 'label' => 'ENLIL Time Series', 'description' => 'WSA-ENLIL solar wind prediction model output'],
        ]
    ],
];

$action = $_GET['action'] ?? '';
$endpoint = $_GET['endpoint'] ?? '';

if ($action === 'list') {
    echo json_encode(['status' => 'ok', 'catalog' => $ENDPOINT_CATALOG]);
    exit;
}

if (empty($endpoint)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing "endpoint" parameter. Use ?endpoint=planetary_k_index_1m.json or ?action=list']);
    exit;
}

// Sanitize endpoint - only allow alphanumeric, hyphens, underscores, slashes, dots
$endpoint = preg_replace('/[^a-zA-Z0-9\-_\/\.]/', '', $endpoint);

// Prevent directory traversal
if (strpos($endpoint, '..') !== false) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid endpoint']);
    exit;
}

$url = $BASE_URL . '/json/' . $endpoint;

$context = stream_context_create([
    'http' => [
        'timeout' => 15,
        'header' => "User-Agent: SpaceWeatherDashboard/1.0\r\n"
    ],
    'ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true
    ]
]);

$response = @file_get_contents($url, false, $context);

if ($response === false) {
    // Try with cURL as fallback
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'SpaceWeatherDashboard/1.0',
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode >= 400) {
            http_response_code(502);
            echo json_encode(['error' => 'Failed to fetch data from SWPC', 'url' => $url]);
            exit;
        }
    } else {
        http_response_code(502);
        echo json_encode(['error' => 'Failed to fetch data from SWPC', 'url' => $url]);
        exit;
    }
}

echo $response;
