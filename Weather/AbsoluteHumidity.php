<style>
    body { 
        overflow: scroll;
        width: fit-content;

    }
    .date {
        position: relative;
        right: 60px;
        font-size: 7pt;
    }
    .avg {

        
        font-size: 7pt;
    }
    div {
        height: 15px; 
         margin-right: 1px; 
          font-size: 7pt;
          text-align: left;
        
    }

    /* Table styles for nicer output */
    table.report {
        border-collapse: collapse;
        margin-left: 20px;
        font-size: 12px;
        width: 760px;
    }
    table.report th,
    table.report td {
        border: 1px solid #ddd;
        padding: 4px 8px;
        text-align: left;
        vertical-align: middle;
    }
    table.report th {
        background: #f4f4f6;
        font-weight: 600;
    }
    .bar-cell {
        width: 840px; /* visual column width (doubled) */
    }
    .bar {
        height: 16px;
        background: green;
        border-radius: 3px;
    }
    .bar.low { background: #e05252; }
    .value { font-weight: 600; padding-right: 8px; }
</style>

<?php

// =====================
// Configuration
// =====================
$defaultLat = 38.2203;
$defaultLon = -90.3954;

// Read lat/lon from query string (GET) with basic validation
$latitude = isset($_GET['lat']) && is_numeric($_GET['lat']) ? floatval($_GET['lat']) : $defaultLat;
$longitude = isset($_GET['lon']) && is_numeric($_GET['lon']) ? floatval($_GET['lon']) : $defaultLon;
$defaultStart = '2025-01-01';
$defaultEnd   = date('Y-m-d');
$timezone  = 'America/Chicago';

// Read dates from query string (GET) with basic validation
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : $defaultStart;
$endDate   = isset($_GET['end_date'])   ? $_GET['end_date']   : $defaultEnd;

function validateDate(string $date): bool
{
    $dt = DateTime::createFromFormat('Y-m-d', $date);
    return $dt && $dt->format('Y-m-d') === $date;
}

if (!validateDate($startDate)) {
    $startDate = $defaultStart;
}
if (!validateDate($endDate)) {
    $endDate = $defaultEnd;
}

// Ensure start <= end; if not, swap them
try {
    $sd = new DateTime($startDate);
    $ed = new DateTime($endDate);
    if ($sd > $ed) {
        $tmp = $startDate;
        $startDate = $endDate;
        $endDate = $tmp;
    }
} catch (Exception $e) {
    $startDate = $defaultStart;
    $endDate = $defaultEnd;
}

// =====================
// Fetch hourly data (Fahrenheit)
// =====================
$url = "https://archive-api.open-meteo.com/v1/archive"
     . "?latitude={$latitude}"
     . "&longitude={$longitude}"
     . "&start_date={$startDate}"
     . "&end_date={$endDate}"
     . "&hourly=temperature_2m,relative_humidity_2m"
     . "&temperature_unit=fahrenheit"
     . "&timezone={$timezone}";

$response = file_get_contents($url);
$data = json_decode($response, true);
/* 
if (!$data || !isset($data['hourly'])) {
    die("Failed to fetch weather data<br>");
} */




function fToC(float $tempF): float
{
    return ($tempF - 32) * 5 / 9;
}

function absoluteHumidity(float $tempF, float $rh): float
{
    // Convert F → C (required for formula)
    $tempC = fToC($tempF);

    // Magnus formula for saturation vapor pressure (in hPa)
    $svp = 6.112 * exp((17.67 * $tempC) / ($tempC + 243.5));
    // Actual vapor pressure
    $vp  = ($rh / 100) * $svp;

    // Absolute humidity in g/m³ using the formula:
    // AH = (216.7 * VP) / (273.15 + T_C)
    // where VP is in hPa and T_C is in Celsius
    return (216.7 * $vp) / (273.15 + $tempC);
}

// =====================
// Hourly → Daily processing
// =====================
$times = $data['hourly']['time'];
$temps = $data['hourly']['temperature_2m']; // °F
$rhs   = $data['hourly']['relative_humidity_2m'];

$dailyTotals = [];
$dailyCounts = [];

foreach ($times as $i => $time) {
    $dt = new DateTime($time, new DateTimeZone($timezone));
    $dateKey = $dt->format('Y-m-d');

    $ah = absoluteHumidity($temps[$i], $rhs[$i]);

    if (!isset($dailyTotals[$dateKey])) {
        $dailyTotals[$dateKey] = 0;
        $dailyCounts[$dateKey] = 0;
    }

    $dailyTotals[$dateKey] += $ah;
    $dailyCounts[$dateKey]++;
}

// =====================
// Output
// =====================

?>
<html>
<body>



    <?php
    // Preset locations
    $locations = [
        'festus' => ['name' => 'Festus, MO', 'lat' => 38.2203, 'lon' => -90.3954],
        'nyc'    => ['name' => 'New York, NY', 'lat' => 40.7128, 'lon' => -74.0060],
        'la'     => ['name' => 'Los Angeles, CA', 'lat' => 34.0522, 'lon' => -118.2437],
        'chicago'=> ['name' => 'Chicago, IL', 'lat' => 41.8781, 'lon' => -87.6298],
        'other'  => ['name' => 'Other (Custom)', 'lat' => '', 'lon' => ''],
    ];
    // Determine selected preset
    $selectedPreset = 'other';
    foreach ($locations as $key => $loc) {
        if (abs($latitude - floatval($loc['lat'])) < 0.0001 && abs($longitude - floatval($loc['lon'])) < 0.0001) {
            $selectedPreset = $key;
            break;
        }
    }
    ?>
    <form method="get" id="locationForm" style="margin-bottom:10px;display:flex;flex-wrap:wrap;align-items:center;gap:10px;">
        <label>Location:
            <select name="preset" id="presetSelect" style="min-width:140px;">
                <?php foreach ($locations as $key => $loc): ?>
                    <option value="<?php echo $key; ?>" <?php if ($selectedPreset === $key) echo 'selected'; ?>><?php echo htmlspecialchars($loc['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Latitude: <input type="number" step="0.0001" name="lat" id="latInput" value="<?php echo htmlspecialchars($latitude); ?>" style="width:90px;"></label>
        <label>Longitude: <input type="number" step="0.0001" name="lon" id="lonInput" value="<?php echo htmlspecialchars($longitude); ?>" style="width:100px;"></label>
        <label>Start: <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>"></label>
        <label>End: <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>"></label>
        <button type="submit">Update</button>
    </form>
    <script>
    // JS to auto-fill lat/lon when preset changes
    const presetSelect = document.getElementById('presetSelect');
    const latInput = document.getElementById('latInput');
    const lonInput = document.getElementById('lonInput');
    const presetData = {
        festus: {lat: 38.2203, lon: -90.3954},
        nyc:    {lat: 40.7128, lon: -74.0060},
        la:     {lat: 34.0522, lon: -118.2437},
        chicago:{lat: 41.8781, lon: -87.6298},
        other:  {lat: '', lon: ''}
    };
    presetSelect.addEventListener('change', function() {
        const val = presetSelect.value;
        if (val !== 'other') {
            latInput.value = presetData[val].lat;
            lonInput.value = presetData[val].lon;
            latInput.readOnly = true;
            lonInput.readOnly = true;
        } else {
            latInput.readOnly = false;
            lonInput.readOnly = false;
        }
    });
    // On page load, set readonly if preset is not 'other'
    if (presetSelect.value !== 'other') {
        latInput.readOnly = true;
        lonInput.readOnly = true;
    }
    </script>


    <?php
    // Show the current location name
    $locationLabel = 'Custom Location';
    if ($selectedPreset !== 'other') {
        $locationLabel = $locations[$selectedPreset]['name'];
    }
    echo '<div style="font-size:15px;font-weight:500;margin-bottom:2px;">Location: ' . htmlspecialchars($locationLabel) . '</div>';

    // Show the most recent absolute humidity value (live)
    $latestIndex = count($times) - 1;
    if ($latestIndex >= 0) {
        $latestTemp = $temps[$latestIndex];
        $latestRh = $rhs[$latestIndex];
        $latestAh = absoluteHumidity($latestTemp, $latestRh);
        $latestTime = $times[$latestIndex];
        $dt = new DateTime($latestTime, new DateTimeZone($timezone));
        $formattedTime = $dt->format('m/j/Y H:i');
        echo '<div style="font-size:18px;font-weight:600;margin-bottom:8px;">';
        echo 'Live Absolute Humidity: <span style="color:green;">' . htmlspecialchars(number_format($latestAh, 2)) . ' g/m³</span>';
        echo ' <span style="font-size:12px;font-weight:400;color:#555;">(' . htmlspecialchars($formattedTime) . ')</span>';
        echo '</div>';
    }
    ?>

    <h2>Daily Absolute Humidity (Hourly Avg)</h2>


    <table class="report" style="margin-left:0;">
        <thead>
            <tr>
                <?php
                // Show each date as a column header (newest first)
                $dates = array_keys($dailyTotals);
                krsort($dates);
                foreach ($dates as $date) {
                    $dt = new DateTime($date, new DateTimeZone($timezone));
                    $formattedDate = $dt->format('m/j/Y');
                    echo "<th style=\"width:16px;writing-mode:vertical-lr;transform:rotate(180deg);font-size:10px;\">" . htmlspecialchars($formattedDate) . "</th>";
                }
                ?>
            </tr>
        </thead>
        <tbody>
            <tr>
                <?php
                // Draw vertical bars for each day
                $threshold = 50;
                foreach ($dates as $date) {
                    $avgAh = $dailyTotals[$date] / $dailyCounts[$date];
                    $avg = ($avgAh * 10);
                    $barHeight = (int) round($avg * 2); // scale as before
                    if ($barHeight < 1) { $barHeight = 1; }
                    if ($barHeight > 420) { $barHeight = 420; }
                    $lowClass = ($avg < $threshold) ? 'low' : '';
                    echo "<td style=\"vertical-align:bottom;height:420px;width:16px;padding:0;text-align:center;\"><div class=\"bar $lowClass\" style=\"width:14px;height:" . $barHeight . "px;margin:0 auto;\"></div><div style=\"font-size:9px;text-align:center;\">" . htmlspecialchars(number_format($avgAh, 2)) . "</div></td>";
                }
                ?>
            </tr>
        </tbody>
    </table>

</body>
</html>