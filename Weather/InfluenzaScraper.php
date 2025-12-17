<?php
/**
 * Missouri DHSS Influenza Data Scraper
 * Fetches and parses influenza surveillance data from Missouri DHSS
 */

class MissouriInfluenzaScraper {
    
    private $dashboardUrl = 'https://health.mo.gov/living/healthcondiseases/communicable/influenza/dashboard.php';
    private $cacheDir = __DIR__ . '/cache';
    private $cacheExpiry = 3600; // 1 hour in seconds
    private $timeout = 30;
    
    public function __construct() {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Fetch influenza data from Missouri DHSS
     * Uses caching to avoid excessive requests
     */
    public function fetchFluData() {
        $cacheFile = $this->cacheDir . '/mo_flu_data.json';
        
        // Return cached data if still valid
        if (file_exists($cacheFile)) {
            $fileAge = time() - filemtime($cacheFile);
            if ($fileAge < $this->cacheExpiry) {
                return json_decode(file_get_contents($cacheFile), true);
            }
        }
        
        // Fetch fresh data from DHSS
        $data = $this->scrapeDashboard();
        
        if ($data) {
            // Cache the data
            file_put_contents($cacheFile, json_encode($data, JSON_PRETTY_PRINT));
        }
        
        return $data;
    }
    
    /**
     * Scrape the Missouri DHSS influenza dashboard
     */
    private function scrapeDashboard() {
        try {
            $html = $this->fetchUrl($this->dashboardUrl);
            
            if (!$html) {
                return [
                    'success' => false,
                    'error' => 'Failed to fetch URL content',
                    'scraped_at' => date('Y-m-d H:i:s')
                ];
            }
            
            // Parse HTML with DOM if available, otherwise use regex
            $data = [];
            
            if (class_exists('DOMDocument')) {
                $data = $this->extractTableDataWithDOM($html);
            } else {
                // Fallback to regex-based extraction
                $data = $this->extractTableDataWithRegex($html);
            }
            
            // If no tables found, look for embedded JSON/data
            if (empty($data)) {
                $data = $this->extractEmbeddedData($html);
            }
            
            return [
                'success' => true,
                'data' => $data,
                'scraped_at' => date('Y-m-d H:i:s'),
                'source' => $this->dashboardUrl
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'scraped_at' => date('Y-m-d H:i:s')
            ];
        }
    }
    
    /**
     * Extract data from HTML tables using DOM (if available)
     */
    private function extractTableDataWithDOM($html) {
        try {
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            
            $xpath = new DOMXPath($dom);
            
            return $this->extractTableData($xpath);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Extract data from HTML tables using regex (fallback)
     */
    private function extractTableDataWithRegex($html) {
        $data = [];
        
        // Look for table patterns
        if (preg_match_all('/<table[^>]*>(.*?)<\/table>/is', $html, $tableMatches)) {
            foreach ($tableMatches[1] as $tableContent) {
                // Extract header row
                $headers = [];
                if (preg_match('/<tr[^>]*>(.*?)<\/tr>/is', $tableContent, $headerMatch)) {
                    if (preg_match_all('/<th[^>]*>([^<]*)<\/th>/is', $headerMatch[1], $headerCells)) {
                        $headers = array_map('trim', $headerCells[1]);
                    }
                }
                
                // Extract data rows
                $pattern = '/<tr[^>]*>(.*?)<\/tr>/is';
                if (preg_match_all($pattern, $tableContent, $rowMatches)) {
                    $skipFirst = !empty($headers); // Skip first row if we already have headers
                    
                    foreach ($rowMatches[1] as $index => $rowContent) {
                        if ($skipFirst && $index === 0) {
                            continue; // Skip header row
                        }
                        
                        if (preg_match_all('/<td[^>]*>([^<]*)<\/td>/is', $rowContent, $cellMatches)) {
                            $rowData = [];
                            foreach ($cellMatches[1] as $cellIndex => $cellValue) {
                                $key = isset($headers[$cellIndex]) ? $headers[$cellIndex] : "col_$cellIndex";
                                $rowData[$key] = trim(strip_tags($cellValue));
                            }
                            
                            if (!empty($rowData)) {
                                $data[] = $rowData;
                            }
                        }
                    }
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Extract embedded JSON or data from script tags
     */
    private function extractEmbeddedData($html) {
        $data = [];
        
        // Look for common data patterns in JavaScript
        // Pattern 1: Look for JSON arrays
        if (preg_match_all('/\[[\s\n]*\{.*?"cases".*?\}\s*\]/is', $html, $matches)) {
            foreach ($matches[0] as $match) {
                $decoded = json_decode($match, true);
                if (is_array($decoded)) {
                    $data = array_merge($data, $decoded);
                }
            }
        }
        
        // Pattern 2: Look for data attributes in divs
        if (preg_match_all('/data-week="([^"]+)".*?data-cases="(\d+)".*?data-hospitalizations="(\d+)"/is', $html, $matches)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $data[] = [
                    'week' => $matches[1][$i],
                    'cases' => $matches[2][$i],
                    'hospitalizations' => $matches[3][$i]
                ];
            }
        }
        
        return $data;
    }
    
    /**
     * Fetch URL content with cURL or stream wrapper
     */
    private function fetchUrl($url) {
        // Try cURL first if available
        if (function_exists('curl_init')) {
            return $this->fetchUrlWithCurl($url);
        }
        
        // Fallback to PHP stream wrapper
        return $this->fetchUrlWithStream($url);
    }
    
    /**
     * Fetch URL using cURL
     */
    private function fetchUrlWithCurl($url) {
        $ch = curl_init($url);
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.5',
                'Accept-Encoding: gzip, deflate',
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        if ($httpCode === 200) {
            return $response;
        }
        
        return false;
    }
    
    /**
     * Fetch URL using PHP stream wrapper (fallback)
     */
    private function fetchUrlWithStream($url) {
        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => $this->timeout,
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'header' => [
                        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                        'Accept-Language: en-US,en;q=0.5',
                    ]
                ],
                'https' => [
                    'method' => 'GET',
                    'timeout' => $this->timeout,
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'header' => [
                        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                        'Accept-Language: en-US,en;q=0.5',
                    ]
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            
            if ($response !== false) {
                return $response;
            }
        } catch (Exception $e) {
            // Fall through to return false
        }
        
        return false;
    }
    
    /**
     * Get cached data age in seconds
     */
    public function getCacheAge() {
        $cacheFile = $this->cacheDir . '/mo_flu_data.json';
        
        if (file_exists($cacheFile)) {
            return time() - filemtime($cacheFile);
        }
        
        return null;
    }
    
    /**
     * Clear cache
     */
    public function clearCache() {
        $cacheFile = $this->cacheDir . '/mo_flu_data.json';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
            return true;
        }
        return false;
    }
}

// Alternative: Fetch data from CDC's publicly available APIs
class CDCFluViewScraper {
    
    /**
     * Fetch flu data from CDC FluView API
     * Returns weekly data for a specific region
     */
    public static function fetchRegionalData($season = '2025-2026') {
        // CDC ILINet data endpoint
        $url = "https://gis.cdc.gov/grasp/fluview1/flusurveillancedata.aspx";
        
        try {
            $ch = curl_init($url);
            
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_USERAGENT => 'Mozilla/5.0',
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            // Parse the response and extract regional data
            // This would require parsing the specific format of CDC's response
            
            return $response;
        } catch (Exception $e) {
            return null;
        }
    }
}

// Example usage in the main page
if (isset($_GET['action']) && $_GET['action'] === 'fetch') {
    header('Content-Type: application/json');
    
    $scraper = new MissouriInfluenzaScraper();
    $data = $scraper->fetchFluData();
    
    echo json_encode($data);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'clear_cache') {
    header('Content-Type: application/json');
    
    $scraper = new MissouriInfluenzaScraper();
    $result = $scraper->clearCache();
    
    echo json_encode(['success' => $result]);
    exit;
}

?>
