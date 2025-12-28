# API Documentation

## Overview

The Weather Maps application integrates multiple data sources and provides a dual-mode visualization system:

### Data Sources
- **Open-Meteo API** - Provides numerical weather data via PHP proxy endpoints
- **Windy API** - Provides animated weather visualizations directly in the browser

### API Endpoints (PHP Backend)
- **api.php** - Production endpoint that proxies requests to Open-Meteo API
- **api-demo.php** - Demo endpoint that generates simulated weather data for testing

### Client-Side APIs
- **Windy API v3** - Integrated directly in the browser for animated overlays
  - No server-side proxy required
  - API Key: Pre-configured in `map.js`
  - Used only when animated overlay mode is enabled

## Dual-Mode System

### Static Mode (Default)
- Uses Leaflet.js for map rendering
- Fetches numerical data from PHP backend (api.php)
- Supports both current and historical data
- Shows color-coded legend
- Allows precise data point collection

### Animated Mode
- Uses Windy API for map rendering and animations
- Still fetches numerical data from PHP backend (api.php) when user clicks on map
- Shows forecast animations only (no historical data animations)
- Provides visual weather patterns and movement
- Includes built-in timeline controls

## PHP API Endpoints

### 1. Production API (api.php)

**Base URL:** `api.php`

**Method:** GET

**Purpose:** Proxy requests to Open-Meteo API for numerical weather data. Used in both static and animated modes when fetching point data.

**Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `latitude` | float | Yes | Latitude coordinate (-90 to 90) |
| `longitude` | float | Yes | Longitude coordinate (-180 to 180) |
| `parameter` | string | Yes | Weather parameter to retrieve (see list below) |
| `mode` | string | Yes | Data mode: `current` or `historical` |
| `units` | string | Yes | Unit system: `metric` or `imperial` |
| `date` | string | Conditional | Required if mode=historical. Format: YYYY-MM-DD |
| `hour` | integer | Conditional | Required if mode=historical. Hour (0-23) |

**Supported Weather Parameters:**
- `temperature_2m` - Temperature at 2 meters
- `precipitation` - Total precipitation
- `rain` - Liquid precipitation
- `snowfall` - Snow accumulation
- `wind_speed_10m` - Wind speed at 10 meters
- `wind_direction_10m` - Wind direction at 10 meters
- `cloud_cover` - Cloud coverage percentage
- `pressure_msl` - Sea level pressure
- `relative_humidity_2m` - Relative humidity at 2 meters
- `visibility` - Horizontal visibility
- `weather_code` - WMO weather code
- `dew_point_2m` - Dew point temperature
- `apparent_temperature` - Feels-like temperature

**Example Requests:**

Current temperature in New York:
```
GET api.php?latitude=40.7128&longitude=-74.0060&parameter=temperature_2m&mode=current&units=metric
```

Historical wind speed in Chicago on June 15, 2020 at 2 PM:
```
GET api.php?latitude=41.8781&longitude=-87.6298&parameter=wind_speed_10m&mode=historical&units=imperial&date=2020-06-15&hour=14
```

**Response Format:**

Success response:
```json
{
  "value": 22.5,
  "time": "2025-12-28T21:00:00Z",
  "latitude": 40.7128,
  "longitude": -74.006,
  "parameter": "temperature_2m",
  "units": "metric"
}
```

Historical data response includes additional fields:
```json
{
  "value": 15.3,
  "time": "2020-06-15T14:00:00Z",
  "latitude": 41.8781,
  "longitude": -87.6298,
  "parameter": "wind_speed_10m",
  "units": "imperial",
  "date": "2020-06-15",
  "hour": 14
}
```

Error response:
```json
{
  "error": "Error message describing the issue"
}
```

**Error Codes:**

- Missing parameters: Returns error message
- Invalid latitude/longitude: Returns validation error
- API connection failure: Returns connection error
- Invalid parameter name: Returns parameter error

### 2. Demo API (api-demo.php)

**Base URL:** `api-demo.php`

**Method:** GET

**Parameters:** Same as production API

**Purpose:** Generate simulated weather data for testing when external API access is unavailable or for development purposes.

**Features:**
- Generates consistent data for same location (deterministic based on coordinates)
- Simulates realistic weather patterns
- Temperature varies by latitude
- Supports all weather parameters
- Includes `demo_mode: true` flag in response

**Example Request:**
```
GET api-demo.php?latitude=45.5&longitude=-122.7&parameter=precipitation&mode=current&units=metric
```

**Response:**
```json
{
  "value": 2.3,
  "time": "2025-12-28T21:45:24Z",
  "latitude": 45.5,
  "longitude": -122.7,
  "parameter": "precipitation",
  "units": "metric",
  "demo_mode": true
}
```

## Units

### Metric Units
- Temperature: °C (Celsius)
- Wind Speed: km/h (kilometers per hour)
- Precipitation: mm (millimeters)
- Pressure: hPa (hectopascals)
- Visibility: km (kilometers)

### Imperial Units
- Temperature: °F (Fahrenheit)
- Wind Speed: mph (miles per hour)
- Precipitation: inch (inches)
- Pressure: inHg (inches of mercury)
- Visibility: mi (miles)

### Unit-Independent
- Cloud Cover: % (percentage)
- Relative Humidity: % (percentage)
- Wind Direction: ° (degrees, 0-360)
- Weather Code: WMO code (integer)

## Weather Codes (WMO)

| Code | Description |
|------|-------------|
| 0 | Clear sky |
| 1 | Mainly clear |
| 2 | Partly cloudy |
| 3 | Overcast |
| 45 | Fog |
| 48 | Depositing rime fog |
| 51 | Light drizzle |
| 53 | Moderate drizzle |
| 55 | Dense drizzle |
| 61 | Slight rain |
| 63 | Moderate rain |
| 65 | Heavy rain |
| 71 | Slight snow fall |
| 73 | Moderate snow fall |
| 75 | Heavy snow fall |
| 80 | Slight rain showers |
| 81 | Moderate rain showers |
| 82 | Violent rain showers |
| 95 | Thunderstorm |

## Rate Limiting

### Production API (Open-Meteo)
The production API proxies to Open-Meteo which has the following limits:
- **Non-commercial:** 10,000 API calls per day
- **No API key required** for basic usage
- Requests are throttled automatically by Open-Meteo

### Windy API
The Windy API has its own rate limits:
- **API Key:** Pre-configured in application
- **Free tier:** Generous limits for personal/testing use
- **Attribution:** Required (automatically included via Windy library)
- **Documentation:** https://api.windy.com/

### Demo API
No rate limiting on the demo API as it generates data locally.

## Windy API Integration

### Configuration

The Windy API key is configured in `map.js`:
```javascript
const WINDY_API_KEY = 'VUlmt9CjBWsehQomhqHyFscMbw3dGMCX';
```

**To update the API key:**
1. Open `map.js`
2. Locate the `WINDY_API_KEY` constant (approximately line 16)
3. Replace with your own API key from https://api.windy.com/

### Initialization

Windy is initialized when user enables animated overlays:
```javascript
function initWindyMap() {
    const options = {
        key: WINDY_API_KEY,
        lat: 45.0,
        lon: -95.0,
        zoom: 4
    };
    windyInit(options, windyAPIReady);
}
```

### Available Layers

The application supports these Windy layers:
- `wind` - Wind particle animations
- `temp` - Temperature gradients
- `rain` - Precipitation forecasts
- `clouds` - Cloud coverage
- `pressure` - Pressure systems
- `waves` - Ocean waves

### Layer Mapping

Weather parameters are automatically mapped to Windy layers:
```javascript
const parameterToWindyLayer = {
    'temperature_2m': 'temp',
    'wind_speed_10m': 'wind',
    'wind_direction_10m': 'wind',
    'precipitation': 'rain',
    'rain': 'rain',
    'cloud_cover': 'clouds',
    'pressure_msl': 'pressure'
};
```

### Animation Controls

The application provides controls for Windy animations:
- **Play/Pause:** Toggle timeline animation
- **Stop:** Reset to current time
- **Layer Selection:** Switch between weather phenomena
- **Time Scrubber:** Windy's built-in timeline control (appears at bottom of map)

## Error Handling

Both APIs implement comprehensive error handling:

1. **Validation Errors:** Invalid parameters return descriptive error messages
2. **Connection Errors:** Network issues are caught and reported
3. **API Errors:** Upstream API errors are forwarded with context
4. **Timeout Handling:** Requests timeout after 30 seconds

## Integration Examples

### JavaScript (Fetch API)
```javascript
async function getWeatherData(lat, lon, parameter) {
    const params = new URLSearchParams({
        latitude: lat,
        longitude: lon,
        parameter: parameter,
        mode: 'current',
        units: 'metric'
    });
    
    try {
        const response = await fetch(`api.php?${params}`);
        const data = await response.json();
        
        if (data.error) {
            console.error('Error:', data.error);
            return null;
        }
        
        return data;
    } catch (error) {
        console.error('Request failed:', error);
        return null;
    }
}

// Usage
const weatherData = await getWeatherData(40.7128, -74.0060, 'temperature_2m');
console.log(`Temperature: ${weatherData.value}°C`);
```

### jQuery
```javascript
$.ajax({
    url: 'api.php',
    method: 'GET',
    data: {
        latitude: 40.7128,
        longitude: -74.0060,
        parameter: 'temperature_2m',
        mode: 'current',
        units: 'metric'
    },
    success: function(data) {
        if (data.error) {
            console.error('Error:', data.error);
        } else {
            console.log('Temperature:', data.value + '°C');
        }
    },
    error: function(xhr, status, error) {
        console.error('Request failed:', error);
    }
});
```

### cURL (Command Line)
```bash
curl "http://localhost:8000/api.php?latitude=40.7128&longitude=-74.0060&parameter=temperature_2m&mode=current&units=metric"
```

## Best Practices

1. **Always check for errors** in the response before using data
2. **Implement caching** for frequently requested locations to reduce API calls
3. **Use demo mode** during development to avoid hitting rate limits
4. **Handle network failures** gracefully with appropriate user feedback
5. **Validate input** on the client side before making API requests
6. **Use HTTPS** in production to ensure secure data transmission

## Support for Historical Data

Historical weather data is available from **1940-01-01** to yesterday's date.

**Limitations:**
- Some parameters may have limited historical coverage
- Data availability depends on Open-Meteo's archive
- Hourly resolution only (not minute-level data)

**Date Range Validation:**
- Minimum date: 1940-01-01
- Maximum date: Yesterday (current date - 1 day)
- Future dates are not supported for historical mode

## Troubleshooting

### Common Issues and Solutions

**Problem:** "Failed to fetch data from Open-Meteo API"
- Check that PHP has `allow_url_fopen` enabled
- Verify cURL is installed and enabled
- Check firewall/network permissions
- Try demo mode to verify application functionality

**Problem:** "Invalid date format"
- Ensure date is in YYYY-MM-DD format
- Verify date is within valid range (1940-present)

**Problem:** "Parameter not found in API response"
- Some parameters may not be available for all locations
- Historical data may not include all modern parameters
- Try a different parameter or location

## Data Attribution

Data is provided by [Open-Meteo](https://open-meteo.com/) and must be attributed according to their terms:
- Include link to Open-Meteo in your application
- Acknowledge data source in documentation
- For commercial use, review Open-Meteo's commercial licensing
