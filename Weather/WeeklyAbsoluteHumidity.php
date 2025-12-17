<style>
    body { 
        overflow: scroll;
        width: fit-content;
    }
    
    /* Table styles for nicer output */
    table.report {
        border-collapse: collapse;
        margin-left: 20px;
        font-size: 12px;
        width: 900px;
    }
    table.report th,
    table.report td {
        border: 1px solid #ddd;
        padding: 6px 8px;
        text-align: left;
        vertical-align: middle;
    }
    table.report th {
        background: #f4f4f6;
        font-weight: 600;
    }
    .bar-cell {
        width: 300px; /* visual column width */
    }
    .bar {
        height: 16px;
        background: steelblue;
        border-radius: 3px;
        display: inline-block;
    }
    .bar.high { background: #e05252; }
    .bar.low { background: #52b3e0; }
    .value { font-weight: 600; padding-right: 8px; }
</style>

<?php

// =====================
// Configuration
// =====================
$defaultStart = '2023-01-01';
$defaultEnd   = date('Y-m-d'); // Today
$timezone  = 'America/Chicago';
$latitude  = 38.2203;
$longitude = -90.3954;

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
// Fetch hourly data from Open-Meteo and convert to weekly
// =====================
$url = "https://archive-api.open-meteo.com/v1/archive"
     . "?latitude={$latitude}"
     . "&longitude={$longitude}"
     . "&start_date={$startDate}"
     . "&end_date={$endDate}"
     . "&hourly=temperature_2m,relative_humidity_2m"
     . "&temperature_unit=fahrenheit"
     . "&timezone={$timezone}";

$weeklyData = [];
$error = null;

try {
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['hourly'])) {
        throw new Exception('Failed to fetch weather data from Open-Meteo');
    }
    
    // Helper functions
    function fToC(float $tempF): float {
        return ($tempF - 32) * 5 / 9;
    }
    
    function absoluteHumidity(float $tempF, float $rh): float {
        $tempC = fToC($tempF);
        $svp = 6.112 * exp((17.67 * $tempC) / ($tempC + 243.5));
        $vp  = ($rh / 100) * $svp;
        return (216.7 * $vp) / (273.15 + $tempC);
    }
    
    // Process hourly data into weekly
    $times = $data['hourly']['time'];
    $temps = $data['hourly']['temperature_2m'];
    $rhs   = $data['hourly']['relative_humidity_2m'];
    
    $weeklyTotals = [];
    $weeklyCounts = [];
    
    foreach ($times as $i => $time) {
        $dt = new DateTime($time, new DateTimeZone($timezone));
        
        // Get week start (Monday)
        $weekStart = clone $dt;
        $dayOfWeek = intval($weekStart->format('N')); // 1=Monday, 7=Sunday
        if ($dayOfWeek !== 1) {
            $weekStart->modify('-' . ($dayOfWeek - 1) . ' days');
        }
        $weekKey = $weekStart->format('Y-m-d');
        
        $ah = absoluteHumidity($temps[$i], $rhs[$i]);
        
        if (!isset($weeklyTotals[$weekKey])) {
            $weeklyTotals[$weekKey] = 0;
            $weeklyCounts[$weekKey] = 0;
        }
        
        $weeklyTotals[$weekKey] += $ah;
        $weeklyCounts[$weekKey]++;
    }
    
    // Calculate weekly averages
    foreach ($weeklyTotals as $weekKey => $total) {
        $weekEnd = new DateTime($weekKey, new DateTimeZone($timezone));
        $weekEnd->modify('+6 days');
        
        $weeklyData[$weekKey] = [
            'avg_ah' => $total / $weeklyCounts[$weekKey],
            'week_ending' => $weekEnd->format('Y-m-d'),
            'count' => $weeklyCounts[$weekKey]
        ];
    }
    
    // Sort by date descending
    krsort($weeklyData);
    
} catch (Exception $e) {
    $error = "Error: " . htmlspecialchars($e->getMessage());
}

?>
<html>
<body>
    <form method="get" style="margin-bottom:10px;">
        <label style="margin-right:10px;">Start: <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>"></label>
        <label style="margin-right:10px;">End: <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>"></label>
        <button type="submit">Update</button>
    </form>

    <h2 style="margin-left:20px;">Weekly Absolute Humidity (Hourly Avg) — Festus, MO</h2>

    <?php if ($error): ?>
        <div style="margin-left:20px; padding:15px; background:#fff3cd; border:1px solid #ffc107; border-radius:4px; margin-right:20px;">
            <strong>Error:</strong><br>
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($weeklyData)): ?>
        <table class="report">
            <thead>
                <tr>
                    <th style="width:100px;">Week Start</th>
                    <th style="width:100px;">Week End</th>
                    <th style="width:140px;">Avg Abs Humidity (g/m³)</th>
                    <th style="width:120px;">Data Points</th>
                    <th class="bar-cell">Visual</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $avgAhs = array_column($weeklyData, 'avg_ah');
            $maxAh = max($avgAhs);
            $minAh = min($avgAhs);
            $rangeAh = $maxAh - $minAh;
            
            foreach ($weeklyData as $weekStart => $data) {
                $avgAh = $data['avg_ah'];
                $weekEnd = $data['week_ending'];
                
                // Bar width: scale 0-300px based on range
                if ($rangeAh > 0) {
                    $barWidth = (int) round((($avgAh - $minAh) / $rangeAh) * 300);
                } else {
                    $barWidth = 150;
                }
                if ($barWidth < 1) { $barWidth = 1; }
                
                // Color coding
                $midpoint = $minAh + ($rangeAh / 2);
                $barClass = '';
                if ($avgAh > $midpoint + ($rangeAh * 0.25)) {
                    $barClass = 'high';
                } elseif ($avgAh < $midpoint - ($rangeAh * 0.25)) {
                    $barClass = 'low';
                }
                
                $dtStart = new DateTime($weekStart, new DateTimeZone($timezone));
                $formattedStart = $dtStart->format('m/d/Y');
                $dtEnd = new DateTime($weekEnd, new DateTimeZone($timezone));
                $formattedEnd = $dtEnd->format('m/d/Y');
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars($formattedStart) . "</td>";
                echo "<td>" . htmlspecialchars($formattedEnd) . "</td>";
                echo "<td><span class=\"value\">" . htmlspecialchars(number_format($avgAh, 2)) . "</span>g/m³</td>";
                echo "<td>" . htmlspecialchars(number_format($data['count'])) . "</td>";
                echo "<td><div style=\"width:300px;background:#f1f1f1;padding:4px;border-radius:4px;\"><div class=\"bar $barClass\" style=\"width:" . $barWidth . "px;\"></div></div></td>";
                echo "</tr>";
            }
            ?>
            </tbody>
        </table>
    <?php else: ?>
        <div style="margin-left:20px; padding:15px; background:#e7f3ff; border:1px solid #0066cc; border-radius:4px; margin-right:20px;">
            No data available for the selected date range.
        </div>
    <?php endif; ?>

    <div style="margin-left:20px; margin-top:20px;">
        <p><strong>Information:</strong></p>
        <ul>
            <li>Absolute humidity is calculated from temperature and relative humidity using the Magnus formula</li>
            <li>Data is from Open-Meteo historical weather archive (Festus, MO)</li>
            <li>Weekly averages are computed from hourly data</li>
            <li>Higher absolute humidity may correlate with certain health conditions</li>
        </ul>
    </div>

</body>
</html>
