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
        width: 760px;
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
        width: 420px; /* visual column width */
    }
    .bar {
        height: 16px;
        background: steelblue;
        border-radius: 3px;
    }
    .bar.high { background: #e05252; }
    .ah-cell { vertical-align: middle; }
    .ah-bar { height: 10px; border-radius: 3px; display: inline-block; }
    .value { font-weight: 600; padding-right: 8px; }
</style>

<?php

// Include the scraper
require_once __DIR__ . '/InfluenzaScraper.php';

// =====================
// Configuration
// =====================
$defaultStart = '2023-01-01';
$defaultEnd   = date('Y-m-d'); // Today
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
// Check if absolute humidity overlay is requested
// =====================
$showAbsoluteHumidity = isset($_GET['show_ah']) && $_GET['show_ah'] === '1';
$weeklyAbsoluteHumidity = [];

if ($showAbsoluteHumidity) {
    // Fetch absolute humidity data
    $ahUrl = "https://archive-api.open-meteo.com/v1/archive"
           . "?latitude=38.2203"
           . "&longitude=-90.3954"
           . "&start_date={$startDate}"
           . "&end_date={$endDate}"
           . "&hourly=temperature_2m,relative_humidity_2m"
           . "&temperature_unit=fahrenheit"
           . "&timezone={$timezone}";
    
    try {
        $ahResponse = file_get_contents($ahUrl);
        $ahData = json_decode($ahResponse, true);
        
        if ($ahData && isset($ahData['hourly'])) {
            $times = $ahData['hourly']['time'];
            $temps = $ahData['hourly']['temperature_2m'];
            $rhs   = $ahData['hourly']['relative_humidity_2m'];
            
            $weeklyAhTotals = [];
            $weeklyAhCounts = [];
            
            foreach ($times as $i => $time) {
                $dt = new DateTime($time, new DateTimeZone($timezone));
                
                // Get week start (Monday)
                $weekStart = clone $dt;
                $dayOfWeek = intval($weekStart->format('N'));
                if ($dayOfWeek !== 1) {
                    $weekStart->modify('-' . ($dayOfWeek - 1) . ' days');
                }
                $weekKey = $weekStart->format('Y-m-d');
                
                // Calculate absolute humidity
                $tempC = ($temps[$i] - 32) * 5 / 9;
                $svp = 6.112 * exp((17.67 * $tempC) / ($tempC + 243.5));
                $vp  = ($rhs[$i] / 100) * $svp;
                $ah = (216.7 * $vp) / (273.15 + $tempC);
                
                if (!isset($weeklyAhTotals[$weekKey])) {
                    $weeklyAhTotals[$weekKey] = 0;
                    $weeklyAhCounts[$weekKey] = 0;
                }
                
                $weeklyAhTotals[$weekKey] += $ah;
                $weeklyAhCounts[$weekKey]++;
            }
            
            foreach ($weeklyAhTotals as $weekKey => $total) {
                $weeklyAbsoluteHumidity[$weekKey] = $total / $weeklyAhCounts[$weekKey];
            }
        }
    } catch (Exception $e) {
        // Silently fail and just don't show AH data
    }
}

// =====================
// Linear regression helper function
// =====================
function calculateLinearRegression($x_values, $y_values) {
    $n = count($x_values);
    if ($n < 2) return null;
    
    $sum_x = array_sum($x_values);
    $sum_y = array_sum($y_values);
    $sum_xy = 0;
    $sum_x2 = 0;
    
    for ($i = 0; $i < $n; $i++) {
        $sum_xy += $x_values[$i] * $y_values[$i];
        $sum_x2 += $x_values[$i] * $x_values[$i];
    }
    
    $denominator = ($n * $sum_x2 - $sum_x * $sum_x);
    if ($denominator == 0) return null;
    
    $slope = ($n * $sum_xy - $sum_x * $sum_y) / $denominator;
    $intercept = ($sum_y - $slope * $sum_x) / $n;
    
    // Calculate R-squared
    $y_mean = $sum_y / $n;
    $ss_tot = 0;
    $ss_res = 0;
    for ($i = 0; $i < $n; $i++) {
        $ss_tot += pow($y_values[$i] - $y_mean, 2);
        $predicted = $slope * $x_values[$i] + $intercept;
        $ss_res += pow($y_values[$i] - $predicted, 2);
    }
    
    $r_squared = $ss_tot == 0 ? 0 : 1 - ($ss_res / $ss_tot);
    
    return [
        'slope' => $slope,
        'intercept' => $intercept,
        'r_squared' => $r_squared
    ];
}

// =====================
// Fetch influenza data from Missouri DHSS via web scraping
// =====================
$weeklyData = [];
$error = null;
$lastUpdated = null;
$cacheAge = null;
$dataSource = null;
$regressionModel = null;

try {
    $scraper = new MissouriInfluenzaScraper();
    $result = $scraper->fetchFluData();
    $cacheAge = $scraper->getCacheAge();
    
    if ($result && $result['success']) {
        $weeklyData = $result['data'] ?? [];
        $dataSource = $result['source'] ?? 'Missouri DHSS';
        $lastUpdated = $result['scraped_at'] ?? date('Y-m-d H:i:s');
        
        // If no data was scraped, show sample data
        if (empty($weeklyData)) {
            $weeklyData = getSampleData();
            $lastUpdated = "Sample data (live scraping not yet returning data). Last attempt: " . $result['scraped_at'];
        }
    } else {
        // Fallback to sample data
        $weeklyData = getSampleData();
        $error = isset($result['error']) ? $result['error'] : 'Could not fetch live data from Missouri DHSS';
        $lastUpdated = "Showing sample data structure. " . $result['scraped_at'];
    }
    
} catch (Exception $e) {
    $weeklyData = getSampleData();
    $error = "Error: " . htmlspecialchars($e->getMessage());
    $lastUpdated = date('Y-m-d H:i:s');
}

// =====================
// Calculate regression model if AH data is available
// =====================
if ($showAbsoluteHumidity && !empty($weeklyAbsoluteHumidity) && !empty($weeklyData)) {
    $ah_values = [];
    $case_values = [];
    
    foreach ($weeklyData as $weekStart => $data) {
        if (isset($weeklyAbsoluteHumidity[$weekStart])) {
            $ah_values[] = $weeklyAbsoluteHumidity[$weekStart];
            $case_values[] = $data['cases'];
        }
    }
    
    if (count($ah_values) >= 2) {
        $regressionModel = calculateLinearRegression($ah_values, $case_values);
    }
}

/**
 * Sample data structure with historical influenza data
 * Data represents weekly cases in St. Louis area
 */
function getSampleData() {
    return [
        // 2025-2026 Season (Current)
        '2025-12-01' => ['cases' => 145, 'hospitalizations' => 8, 'deaths' => 0, 'week_ending' => '2025-12-06'],
        '2025-11-24' => ['cases' => 132, 'hospitalizations' => 7, 'deaths' => 0, 'week_ending' => '2025-11-29'],
        '2025-11-17' => ['cases' => 118, 'hospitalizations' => 6, 'deaths' => 0, 'week_ending' => '2025-11-22'],
        '2025-11-10' => ['cases' => 105, 'hospitalizations' => 5, 'deaths' => 0, 'week_ending' => '2025-11-15'],
        '2025-11-03' => ['cases' => 92, 'hospitalizations' => 4, 'deaths' => 0, 'week_ending' => '2025-11-08'],
        '2025-10-27' => ['cases' => 78, 'hospitalizations' => 3, 'deaths' => 0, 'week_ending' => '2025-11-01'],
        '2025-10-20' => ['cases' => 62, 'hospitalizations' => 2, 'deaths' => 0, 'week_ending' => '2025-10-25'],
        '2025-10-13' => ['cases' => 48, 'hospitalizations' => 2, 'deaths' => 0, 'week_ending' => '2025-10-18'],
        
        // 2024-2025 Season
        '2025-09-29' => ['cases' => 35, 'hospitalizations' => 1, 'deaths' => 0, 'week_ending' => '2025-10-04'],
        '2025-03-17' => ['cases' => 892, 'hospitalizations' => 45, 'deaths' => 2, 'week_ending' => '2025-03-22'],
        '2025-03-10' => ['cases' => 945, 'hospitalizations' => 48, 'deaths' => 2, 'week_ending' => '2025-03-15'],
        '2025-03-03' => ['cases' => 1012, 'hospitalizations' => 52, 'deaths' => 3, 'week_ending' => '2025-03-08'],
        '2025-02-24' => ['cases' => 1089, 'hospitalizations' => 56, 'deaths' => 3, 'week_ending' => '2025-03-01'],
        '2025-02-17' => ['cases' => 1145, 'hospitalizations' => 59, 'deaths' => 4, 'week_ending' => '2025-02-22'],
        '2025-02-10' => ['cases' => 1210, 'hospitalizations' => 62, 'deaths' => 4, 'week_ending' => '2025-02-15'],
        '2025-02-03' => ['cases' => 1278, 'hospitalizations' => 65, 'deaths' => 5, 'week_ending' => '2025-02-08'],
        '2025-01-27' => ['cases' => 1345, 'hospitalizations' => 68, 'deaths' => 5, 'week_ending' => '2025-02-01'],
        '2025-01-20' => ['cases' => 1412, 'hospitalizations' => 72, 'deaths' => 6, 'week_ending' => '2025-01-25'],
        '2025-01-13' => ['cases' => 1489, 'hospitalizations' => 76, 'deaths' => 7, 'week_ending' => '2025-01-18'],
        '2025-01-06' => ['cases' => 1567, 'hospitalizations' => 80, 'deaths' => 7, 'week_ending' => '2025-01-11'],
        '2024-12-30' => ['cases' => 1645, 'hospitalizations' => 84, 'deaths' => 8, 'week_ending' => '2025-01-04'],
        '2024-12-23' => ['cases' => 1723, 'hospitalizations' => 88, 'deaths' => 9, 'week_ending' => '2024-12-28'],
        '2024-12-16' => ['cases' => 1801, 'hospitalizations' => 92, 'deaths' => 9, 'week_ending' => '2024-12-21'],
        '2024-12-09' => ['cases' => 1879, 'hospitalizations' => 96, 'deaths' => 10, 'week_ending' => '2024-12-14'],
        '2024-12-02' => ['cases' => 1957, 'hospitalizations' => 100, 'deaths' => 11, 'week_ending' => '2024-12-07'],
        '2024-11-25' => ['cases' => 2035, 'hospitalizations' => 104, 'deaths' => 12, 'week_ending' => '2024-11-30'],
        '2024-11-18' => ['cases' => 1945, 'hospitalizations' => 99, 'deaths' => 11, 'week_ending' => '2024-11-23'],
        '2024-11-11' => ['cases' => 1867, 'hospitalizations' => 95, 'deaths' => 10, 'week_ending' => '2024-11-16'],
        '2024-11-04' => ['cases' => 1789, 'hospitalizations' => 91, 'deaths' => 9, 'week_ending' => '2024-11-09'],
        '2024-10-28' => ['cases' => 1234, 'hospitalizations' => 63, 'deaths' => 5, 'week_ending' => '2024-11-02'],
        '2024-10-21' => ['cases' => 892, 'hospitalizations' => 45, 'deaths' => 3, 'week_ending' => '2024-10-26'],
        '2024-10-14' => ['cases' => 567, 'hospitalizations' => 28, 'deaths' => 2, 'week_ending' => '2024-10-19'],
        '2024-10-07' => ['cases' => 234, 'hospitalizations' => 11, 'deaths' => 0, 'week_ending' => '2024-10-12'],
        
        // 2023-2024 Season
        '2024-03-18' => ['cases' => 245, 'hospitalizations' => 12, 'deaths' => 1, 'week_ending' => '2024-03-23'],
        '2024-03-11' => ['cases' => 278, 'hospitalizations' => 14, 'deaths' => 1, 'week_ending' => '2024-03-16'],
        '2024-03-04' => ['cases' => 312, 'hospitalizations' => 16, 'deaths' => 1, 'week_ending' => '2024-03-09'],
        '2024-02-26' => ['cases' => 456, 'hospitalizations' => 23, 'deaths' => 2, 'week_ending' => '2024-03-02'],
        '2024-02-19' => ['cases' => 589, 'hospitalizations' => 30, 'deaths' => 2, 'week_ending' => '2024-02-24'],
        '2024-02-12' => ['cases' => 723, 'hospitalizations' => 37, 'deaths' => 3, 'week_ending' => '2024-02-17'],
        '2024-02-05' => ['cases' => 856, 'hospitalizations' => 43, 'deaths' => 4, 'week_ending' => '2024-02-10'],
        '2024-01-29' => ['cases' => 989, 'hospitalizations' => 50, 'deaths' => 4, 'week_ending' => '2024-02-03'],
        '2024-01-22' => ['cases' => 1123, 'hospitalizations' => 57, 'deaths' => 5, 'week_ending' => '2024-01-27'],
        '2024-01-15' => ['cases' => 1256, 'hospitalizations' => 64, 'deaths' => 6, 'week_ending' => '2024-01-20'],
        '2024-01-08' => ['cases' => 1389, 'hospitalizations' => 70, 'deaths' => 6, 'week_ending' => '2024-01-13'],
        '2024-01-01' => ['cases' => 1523, 'hospitalizations' => 77, 'deaths' => 7, 'week_ending' => '2024-01-06'],
        '2023-12-25' => ['cases' => 1656, 'hospitalizations' => 84, 'deaths' => 8, 'week_ending' => '2023-12-30'],
        '2023-12-18' => ['cases' => 1789, 'hospitalizations' => 91, 'deaths' => 9, 'week_ending' => '2023-12-23'],
        '2023-12-11' => ['cases' => 1923, 'hospitalizations' => 98, 'deaths' => 9, 'week_ending' => '2023-12-16'],
        '2023-12-04' => ['cases' => 2056, 'hospitalizations' => 105, 'deaths' => 10, 'week_ending' => '2023-12-09'],
        '2023-11-27' => ['cases' => 2189, 'hospitalizations' => 111, 'deaths' => 11, 'week_ending' => '2023-12-02'],
        '2023-11-20' => ['cases' => 2123, 'hospitalizations' => 108, 'deaths' => 11, 'week_ending' => '2023-11-25'],
        '2023-11-13' => ['cases' => 2056, 'hospitalizations' => 104, 'deaths' => 10, 'week_ending' => '2023-11-18'],
        '2023-11-06' => ['cases' => 1989, 'hospitalizations' => 101, 'deaths' => 10, 'week_ending' => '2023-11-11'],
        '2023-10-30' => ['cases' => 1456, 'hospitalizations' => 74, 'deaths' => 6, 'week_ending' => '2023-11-04'],
        '2023-10-23' => ['cases' => 1089, 'hospitalizations' => 55, 'deaths' => 4, 'week_ending' => '2023-10-28'],
        '2023-10-16' => ['cases' => 723, 'hospitalizations' => 37, 'deaths' => 3, 'week_ending' => '2023-10-21'],
        
        // Earlier seasons
        '2023-03-20' => ['cases' => 156, 'hospitalizations' => 8, 'deaths' => 1, 'week_ending' => '2023-03-25'],
        '2022-12-19' => ['cases' => 1834, 'hospitalizations' => 93, 'deaths' => 12, 'week_ending' => '2022-12-24'],
        '2022-01-17' => ['cases' => 1567, 'hospitalizations' => 79, 'deaths' => 8, 'week_ending' => '2022-01-22'],
    ];
}

?>
<html>
<body>
    <form method="get" style="margin-bottom:10px;">
        <label style="margin-right:10px;">Start: <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>"></label>
        <label style="margin-right:10px;">End: <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>"></label>
        <button type="submit">Update</button>
    </form>

    <h2 style="margin-left:20px;">Influenza Cases — St. Louis, MO</h2>

    <div style="margin-left:20px; margin-bottom:15px; display:flex; align-items:center; gap:15px; flex-wrap:wrap;">
        <?php if ($lastUpdated): ?>
            <div style="font-size:11px; color:#666;">
                <strong>Last Updated:</strong> <?php echo htmlspecialchars($lastUpdated); ?>
                <?php if ($cacheAge !== null): ?>
                    <br><small>(Cache age: <?php echo intval($cacheAge); ?>s)</small>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['refresh' => 1])); ?>" style="padding:5px 10px; background:#0066cc; color:white; text-decoration:none; border-radius:3px; font-size:11px;">
            Refresh Data
        </a>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['show_ah' => $showAbsoluteHumidity ? '0' : '1'])); ?>" style="padding:5px 10px; background:<?php echo $showAbsoluteHumidity ? '#28a745' : '#6c757d'; ?>; color:white; text-decoration:none; border-radius:3px; font-size:11px;">
            <?php echo $showAbsoluteHumidity ? '✓ Hide' : 'Show'; ?> Absolute Humidity
        </a>
        <?php if ($dataSource): ?>
            <small style="color:#999;">Data from: <?php echo htmlspecialchars($dataSource); ?></small>
        <?php endif; ?>
    </div>

    <?php if ($error): ?>
        <div style="margin-left:20px; margin-bottom:15px; padding:12px; background:#fff3cd; border:1px solid #ffc107; border-radius:4px; margin-right:20px; font-size:12px;">
            <strong>⚠️ Note:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($showAbsoluteHumidity && $regressionModel): ?>
        <div style="margin-left:20px; margin-bottom:15px; padding:12px; background:#e7f3ff; border:1px solid #0066cc; border-radius:4px; margin-right:20px; font-size:12px;">
            <strong>📊 Regression Model (AH → Cases):</strong><br>
            Expected Cases = <strong><?php echo number_format($regressionModel['slope'], 2); ?></strong> × AH + <strong><?php echo number_format($regressionModel['intercept'], 0); ?></strong><br>
            <small>R² = <?php echo number_format($regressionModel['r_squared'], 3); ?> (goodness of fit)</small>
        </div>
    <?php endif; ?>

    <?php if (!empty($weeklyData)): ?>
        <table class="report">
            <thead>
                <tr>
                    <th style="width:120px;">Week Starting</th>
                    <th style="width:140px;">Week Ending</th>
                    <th style="width:140px;">Confirmed Cases</th>
                    <th style="width:140px;">Hospitalizations</th>
                    <th style="width:100px;">Deaths</th>
                    <?php if ($showAbsoluteHumidity && $regressionModel): ?>
                        <th style="width:140px;">Predicted Cases</th>
                    <?php endif; ?>
                    <th class="bar-cell">Trend (Cases / AH)</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $maxCases = max(array_map(function($d) { return $d['cases']; }, $weeklyData));
            if (!empty($weeklyAbsoluteHumidity)) {
                $maxAh = max($weeklyAbsoluteHumidity);
                $minAh = min($weeklyAbsoluteHumidity);
            }
            
            foreach ($weeklyData as $weekStart => $data) {
                $weekEnd = isset($data['week_ending']) ? $data['week_ending'] : date('Y-m-d', strtotime($weekStart . ' +6 days'));
                $cases = $data['cases'];
                $hospitalizations = $data['hospitalizations'] ?? 0;
                $deaths = $data['deaths'] ?? 0;
                
                // Bar width scaling
                $barWidth = (int) round(($cases / $maxCases) * 420);
                if ($barWidth < 1) { $barWidth = 1; }
                
                // Color coding based on case count
                $barClass = '';
                if ($cases > $maxCases * 0.8) {
                    $barClass = 'high';
                }
                
                $dt = new DateTime($weekStart, new DateTimeZone($timezone));
                $formattedWeekStart = $dt->format('m/d/Y');
                $dt = new DateTime($weekEnd, new DateTimeZone($timezone));
                $formattedWeekEnd = $dt->format('m/d/Y');
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars($formattedWeekStart) . "</td>";
                echo "<td>" . htmlspecialchars($formattedWeekEnd) . "</td>";
                echo "<td><span class=\"value\">" . htmlspecialchars(number_format($cases)) . "</span></td>";
                echo "<td>" . htmlspecialchars(number_format($hospitalizations)) . "</td>";
                echo "<td>" . htmlspecialchars(number_format($deaths)) . "</td>";
                
                // Predicted cases from regression model
                if ($showAbsoluteHumidity && $regressionModel) {
                    $ah = isset($weeklyAbsoluteHumidity[$weekStart]) ? $weeklyAbsoluteHumidity[$weekStart] : null;
                    if ($ah !== null) {
                        $predicted = max(0, intval(round($regressionModel['slope'] * $ah + $regressionModel['intercept'])));
                        $variance = abs($cases - $predicted);
                        $variancePercent = $cases > 0 ? ($variance / $cases) * 100 : 0;
                        
                        // Color code: green if close, yellow if moderate, red if far off
                        $varColor = '#28a745'; // green
                        if ($variancePercent > 20) $varColor = '#ffc107'; // yellow
                        if ($variancePercent > 50) $varColor = '#dc3545'; // red
                        
                        echo "<td style=\"background:rgba(255,255,255,0.6);\">";
                        echo "<span class=\"value\">" . htmlspecialchars(number_format($predicted)) . "</span>";
                        echo "<div style=\"font-size:10px;color:#666;\">(" . htmlspecialchars(number_format($variancePercent, 0)) . "% diff)</div>";
                        echo "</td>";
                    } else {
                        echo "<td style=\"background:rgba(255,255,255,0.6);\">-</td>";
                    }
                }
                
                // Combined trend cell: Cases bar (large) + optional AH bar (small)
                $ah = ($showAbsoluteHumidity && isset($weeklyAbsoluteHumidity[$weekStart])) ? $weeklyAbsoluteHumidity[$weekStart] : null;
                // Prepare AH scaling
                $ahBarMax = 600;
                if ($ah !== null && isset($minAh) && isset($maxAh) && $maxAh > $minAh) {
                    $ahBarWidth = (int) round((($ah - $minAh) / ($maxAh - $minAh)) * $ahBarMax);
                    if ($ahBarWidth < 1) { $ahBarWidth = 1; }
                    $ahPercent = (($ah - $minAh) / ($maxAh - $minAh)) * 100;
                    if ($ahPercent > 50) {
                        $ahColor = 'hsl(' . intval(0 + (100 - $ahPercent)) . ',80%,45%)';
                    } else {
                        $ahColor = 'hsl(210,' . intval(min(80, max(10, $ahPercent))) . '%,45%)';
                    }
                } else {
                    $ahBarWidth = (int) round($ahBarMax / 2);
                    $ahColor = '#999';
                }

                echo "<td style=\"vertical-align:top;\">";
                echo "<div style=\"display:flex;flex-direction:column;gap:6px;padding:6px;background:rgba(250,250,250,0.8);border-radius:4px;\">";
                // Cases row
                echo "<div style=\"display:flex;align-items:center;gap:8px;\">";
                echo "<strong style=\"width:55px;display:inline-block;\">Cases</strong>";
                echo "<div style=\"width:420px;background:#f1f1f1;padding:4px;border-radius:4px;\">";
                echo "<div class=\"bar $barClass\" style=\"width:" . $barWidth . "px;\"></div>";
                echo "</div>";
                echo "<span style=\"margin-left:8px;\" class=\"value\">" . htmlspecialchars(number_format($cases)) . "</span>";
                echo "</div>";
                // AH row
                echo "<div style=\"display:flex;align-items:center;gap:8px;\">";
                echo "<strong style=\"width:55px;display:inline-block;\">AH</strong>";
                echo "<div style=\"width:{$ahBarMax}px;background:#f1f1f1;padding:3px;border-radius:4px;\">";
                echo "<div class=\"ah-bar\" style=\"width:" . $ahBarWidth . "px;background:" . $ahColor . ";\"></div>";
                echo "</div>";
                if ($ah !== null) {
                    echo "<span style=\"margin-left:8px;\" class=\"value\">" . htmlspecialchars(number_format($ah, 1)) . " g/m³</span>";
                } else {
                    echo "<span style=\"margin-left:8px;\">-</span>";
                }
                echo "</div>";
                echo "</div>";
                echo "</td>";
                echo "</tr>";
            }
            ?>
            </tbody>
        </table>
    <?php else: ?>
        <div style="margin-left:20px; padding:15px; background:#fff3cd; border:1px solid #ffc107; border-radius:4px; margin-right:20px;">
            <strong>Sample Data Shown:</strong><br>
            Currently displaying demonstration data structure. To display actual Missouri influenza data, integrate with one of the sources below.
        </div>
    <?php endif; ?>

    <div style="margin-left:20px; margin-top:20px; padding:15px; background:#e7f3ff; border:1px solid #0066cc; border-radius:4px; margin-right:20px;">
        <strong>Missouri DHSS Data Sources:</strong>
        <ul>
            <li><a href="https://health.mo.gov/living/healthcondiseases/communicable/influenza/dashboard.php" target="_blank"><strong>Missouri Weekly Influenza Surveillance Report Dashboard</strong></a> - Official state-level data</li>
            <li><a href="https://health.mo.gov/living/healthcondiseases/communicable/influenza/reports.php" target="_blank">Missouri Influenza Reports</a> - Weekly surveillance reports</li>
            <li><a href="https://www.cdc.gov/flu/weekly/" target="_blank">CDC FluView Weekly Reports</a> - National/regional data</li>
            <li><a href="https://www.stlouiscounty.com/dph/" target="_blank">St. Louis County Health Department</a> - Local data if available</li>
        </ul>
    </div>

    <h3 style="margin-left:20px; margin-top:20px;">Data Integration Notes:</h3>
    <ul style="margin-left:40px;">
        <li>Missouri DHSS reports influenza data <strong>by week</strong>, not by individual days</li>
        <li>Data includes: confirmed cases, hospitalizations, deaths (when available)</li>
        <li>Reports are updated weekly by Missouri DHSS</li>
        <li>St. Louis is located in the <strong>East Central region</strong> of Missouri</li>
        <li>Reporting delays of 1-2 weeks are typical</li>
        <li><strong>To implement:</strong> You can either<br/>
            &nbsp;&nbsp;• Scrape the HTML from the DHSS dashboard<br/>
            &nbsp;&nbsp;• Set up a database to manually import weekly DHSS CSV/Excel reports<br/>
            &nbsp;&nbsp;• Request API access from Missouri DHSS<br/>
            &nbsp;&nbsp;• Store data from healthcare facility lab reports
        </li>
    </ul>

</body>
</html>
