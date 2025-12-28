// Global Variables
let map;
let windyMap = null;
let windyAPI = null;
let markers = [];
let currentLayer = null;
let currentDataMode = 'current';
let currentWeatherParam = 'temperature_2m';
let currentUnits = 'metric';
let demoMode = false; // Set to true to use demo data without external API
let animatedOverlayMode = false; // Track if animated overlays are enabled
let isAnimationPlaying = false;

// Windy API Configuration
// Note: Windy API is a client-side API, so the key is safely exposed in the browser.
// This is the standard way to use Windy API. For production, consider using environment
// variables or a build-time configuration to manage the key.
// The key can be updated here or replaced during build: https://api.windy.com/
const WINDY_API_KEY = 'VUlmt9CjBWsehQomhqHyFscMbw3dGMCX';

// Mapping between weather parameters and Windy layers
const parameterToWindyLayer = {
    'temperature_2m': 'temp',
    'wind_speed_10m': 'wind',
    'wind_direction_10m': 'wind',
    'precipitation': 'rain',
    'rain': 'rain',
    'cloud_cover': 'clouds',
    'pressure_msl': 'pressure',
    'waves': 'waves'
};

// Check if demo mode should be enabled from URL parameter
if (window.location.search.includes('demo=true')) {
    demoMode = true;
    console.log('Demo mode enabled - using simulated data');
    // Show demo mode indicator
    document.addEventListener('DOMContentLoaded', () => {
        const indicator = document.getElementById('demoModeIndicator');
        if (indicator) {
            indicator.classList.remove('d-none');
        }
    });
}

// Weather parameter configurations
const weatherParams = {
    temperature_2m: {
        name: 'Temperature (2m)',
        unit: { metric: '°C', imperial: '°F' },
        colors: [
            { threshold: -30, color: '#0000ff', label: '< -30' },
            { threshold: -20, color: '#4169e1', label: '-30 to -20' },
            { threshold: -10, color: '#87ceeb', label: '-20 to -10' },
            { threshold: 0, color: '#90ee90', label: '-10 to 0' },
            { threshold: 10, color: '#ffff00', label: '0 to 10' },
            { threshold: 20, color: '#ffa500', label: '10 to 20' },
            { threshold: 30, color: '#ff4500', label: '20 to 30' },
            { threshold: 40, color: '#8b0000', label: '> 30' }
        ]
    },
    precipitation: {
        name: 'Precipitation',
        unit: { metric: 'mm', imperial: 'in' },
        colors: [
            { threshold: 0, color: '#ffffff', label: 'None' },
            { threshold: 1, color: '#b3e5fc', label: '0-1' },
            { threshold: 5, color: '#4fc3f7', label: '1-5' },
            { threshold: 10, color: '#0288d1', label: '5-10' },
            { threshold: 20, color: '#01579b', label: '> 10' }
        ]
    },
    rain: {
        name: 'Rain',
        unit: { metric: 'mm', imperial: 'in' },
        colors: [
            { threshold: 0, color: '#ffffff', label: 'None' },
            { threshold: 1, color: '#b3e5fc', label: '0-1' },
            { threshold: 5, color: '#4fc3f7', label: '1-5' },
            { threshold: 10, color: '#0288d1', label: '5-10' },
            { threshold: 20, color: '#01579b', label: '> 10' }
        ]
    },
    snowfall: {
        name: 'Snowfall',
        unit: { metric: 'cm', imperial: 'in' },
        colors: [
            { threshold: 0, color: '#ffffff', label: 'None' },
            { threshold: 1, color: '#e3f2fd', label: '0-1' },
            { threshold: 5, color: '#90caf9', label: '1-5' },
            { threshold: 10, color: '#42a5f5', label: '5-10' },
            { threshold: 20, color: '#1976d2', label: '> 10' }
        ]
    },
    wind_speed_10m: {
        name: 'Wind Speed',
        unit: { metric: 'km/h', imperial: 'mph' },
        colors: [
            { threshold: 0, color: '#e8f5e9', label: 'Calm (0-10)' },
            { threshold: 10, color: '#81c784', label: 'Light (10-30)' },
            { threshold: 30, color: '#ffa726', label: 'Moderate (30-50)' },
            { threshold: 50, color: '#ef5350', label: 'Strong (50-80)' },
            { threshold: 80, color: '#c62828', label: 'Very Strong (> 80)' }
        ]
    },
    wind_direction_10m: {
        name: 'Wind Direction',
        unit: { metric: '°', imperial: '°' },
        colors: [
            { threshold: 0, color: '#f44336', label: 'N (0-45)' },
            { threshold: 45, color: '#ff9800', label: 'NE (45-90)' },
            { threshold: 90, color: '#ffeb3b', label: 'E (90-135)' },
            { threshold: 135, color: '#8bc34a', label: 'SE (135-180)' },
            { threshold: 180, color: '#00bcd4', label: 'S (180-225)' },
            { threshold: 225, color: '#2196f3', label: 'SW (225-270)' },
            { threshold: 270, color: '#3f51b5', label: 'W (270-315)' },
            { threshold: 315, color: '#9c27b0', label: 'NW (315-360)' }
        ]
    },
    cloud_cover: {
        name: 'Cloud Cover',
        unit: { metric: '%', imperial: '%' },
        colors: [
            { threshold: 0, color: '#e3f2fd', label: 'Clear (0-20%)' },
            { threshold: 20, color: '#90caf9', label: 'Few (20-40%)' },
            { threshold: 40, color: '#64b5f6', label: 'Scattered (40-60%)' },
            { threshold: 60, color: '#42a5f5', label: 'Broken (60-80%)' },
            { threshold: 80, color: '#1976d2', label: 'Overcast (80-100%)' }
        ]
    },
    pressure_msl: {
        name: 'Pressure (Sea Level)',
        unit: { metric: 'hPa', imperial: 'inHg' },
        colors: [
            { threshold: 980, color: '#f44336', label: 'Very Low (< 980)' },
            { threshold: 990, color: '#ff9800', label: 'Low (980-1000)' },
            { threshold: 1000, color: '#ffeb3b', label: 'Normal (1000-1020)' },
            { threshold: 1020, color: '#8bc34a', label: 'High (1020-1040)' },
            { threshold: 1040, color: '#4caf50', label: 'Very High (> 1040)' }
        ]
    },
    relative_humidity_2m: {
        name: 'Relative Humidity',
        unit: { metric: '%', imperial: '%' },
        colors: [
            { threshold: 0, color: '#fff8e1', label: 'Low (0-30%)' },
            { threshold: 30, color: '#ffcc80', label: 'Moderate (30-60%)' },
            { threshold: 60, color: '#ff9800', label: 'High (60-80%)' },
            { threshold: 80, color: '#ef6c00', label: 'Very High (> 80%)' }
        ]
    },
    visibility: {
        name: 'Visibility',
        unit: { metric: 'km', imperial: 'mi' },
        colors: [
            { threshold: 0, color: '#424242', label: 'Very Poor (< 1)' },
            { threshold: 1, color: '#757575', label: 'Poor (1-4)' },
            { threshold: 4, color: '#9e9e9e', label: 'Moderate (4-10)' },
            { threshold: 10, color: '#e0e0e0', label: 'Good (> 10)' }
        ]
    },
    weather_code: {
        name: 'Weather Code',
        unit: { metric: '', imperial: '' },
        colors: [
            { threshold: 0, color: '#ffeb3b', label: 'Clear sky' },
            { threshold: 1, color: '#e3f2fd', label: 'Mainly clear' },
            { threshold: 2, color: '#90caf9', label: 'Partly cloudy' },
            { threshold: 3, color: '#42a5f5', label: 'Overcast' },
            { threshold: 45, color: '#9e9e9e', label: 'Fog' },
            { threshold: 51, color: '#b3e5fc', label: 'Drizzle' },
            { threshold: 61, color: '#4fc3f7', label: 'Rain' },
            { threshold: 71, color: '#e3f2fd', label: 'Snow' },
            { threshold: 95, color: '#ff6f00', label: 'Thunderstorm' }
        ]
    },
    dew_point_2m: {
        name: 'Dew Point',
        unit: { metric: '°C', imperial: '°F' },
        colors: [
            { threshold: -20, color: '#0000ff', label: '< -20' },
            { threshold: -10, color: '#4169e1', label: '-20 to -10' },
            { threshold: 0, color: '#87ceeb', label: '-10 to 0' },
            { threshold: 10, color: '#90ee90', label: '0 to 10' },
            { threshold: 20, color: '#ffa500', label: '10 to 20' },
            { threshold: 30, color: '#ff4500', label: '> 20' }
        ]
    },
    apparent_temperature: {
        name: 'Apparent Temperature',
        unit: { metric: '°C', imperial: '°F' },
        colors: [
            { threshold: -30, color: '#0000ff', label: '< -30' },
            { threshold: -20, color: '#4169e1', label: '-30 to -20' },
            { threshold: -10, color: '#87ceeb', label: '-20 to -10' },
            { threshold: 0, color: '#90ee90', label: '-10 to 0' },
            { threshold: 10, color: '#ffff00', label: '0 to 10' },
            { threshold: 20, color: '#ffa500', label: '10 to 20' },
            { threshold: 30, color: '#ff4500', label: '20 to 30' },
            { threshold: 40, color: '#8b0000', label: '> 30' }
        ]
    }
};

// Weather code descriptions
const weatherCodeDescriptions = {
    0: 'Clear sky',
    1: 'Mainly clear',
    2: 'Partly cloudy',
    3: 'Overcast',
    45: 'Fog',
    48: 'Depositing rime fog',
    51: 'Light drizzle',
    53: 'Moderate drizzle',
    55: 'Dense drizzle',
    56: 'Light freezing drizzle',
    57: 'Dense freezing drizzle',
    61: 'Slight rain',
    63: 'Moderate rain',
    65: 'Heavy rain',
    66: 'Light freezing rain',
    67: 'Heavy freezing rain',
    71: 'Slight snow fall',
    73: 'Moderate snow fall',
    75: 'Heavy snow fall',
    77: 'Snow grains',
    80: 'Slight rain showers',
    81: 'Moderate rain showers',
    82: 'Violent rain showers',
    85: 'Slight snow showers',
    86: 'Heavy snow showers',
    95: 'Thunderstorm',
    96: 'Thunderstorm with slight hail',
    99: 'Thunderstorm with heavy hail'
};

// Initialize map
function initMap() {
    // Create map centered on North America
    map = L.map('map').setView([45.0, -95.0], 4);

    // Add base tile layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(map);

    // Set up event listeners
    setupEventListeners();

    // Initialize date input to today
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('dateInput').value = today;
    document.getElementById('dateInput').max = today;
    document.getElementById('dateInput').min = '1940-01-01';

    // Update legend
    updateLegend();

    // Click on map to get weather data
    map.on('click', onMapClick);
}

// Initialize Windy Map
function initWindyMap() {
    // Check if Windy API is loaded
    if (typeof windyInit === 'undefined') {
        console.error('Windy API not loaded. Falling back to static mode.');
        showTemporaryMessage('Animated overlays unavailable. Windy API failed to load.');
        // Reset toggle
        document.getElementById('animatedOverlayToggle').checked = false;
        return;
    }
    
    showLoading(true);
    
    const options = {
        key: WINDY_API_KEY,
        lat: 45.0,
        lon: -95.0,
        zoom: 4
    };

    windyInit(options, windyAPIReady);
}

// Windy API Ready Callback
function windyAPIReady(windyAPIInstance) {
    windyAPI = windyAPIInstance;
    const { map, store, overlays } = windyAPI;
    windyMap = map;
    
    // Set initial layer
    const initialLayer = document.getElementById('windyLayer').value;
    store.set('overlay', initialLayer);
    
    // Add click listener for weather data
    windyMap.on('click', function(e) {
        if (animatedOverlayMode) {
            onMapClick({ latlng: e.latlng });
        }
    });
    
    showLoading(false);
    console.log('Windy API initialized successfully');
}

// Toggle between static and animated modes
function toggleAnimatedOverlay(enabled) {
    animatedOverlayMode = enabled;
    const mapDiv = document.getElementById('map');
    const windyMapDiv = document.getElementById('windyMap');
    const animationControls = document.getElementById('animationControls');
    const legend = document.getElementById('legend');
    
    if (enabled) {
        // Switch to animated mode
        mapDiv.classList.add('d-none');
        windyMapDiv.classList.remove('d-none');
        animationControls.classList.remove('d-none');
        
        // Initialize Windy if not already done
        if (!windyMap) {
            initWindyMap();
        } else {
            // Sync the view with the Leaflet map
            const center = map.getCenter();
            const zoom = map.getZoom();
            windyMap.setView([center.lat, center.lng], zoom);
        }
        
        // Update legend to show Windy is active
        legend.style.display = 'none';
        
        // Show message if historical data is selected
        if (currentDataMode === 'historical') {
            showTemporaryMessage('Animated overlays show forecast data. Historical data mode disabled for animations.');
        }
    } else {
        // Switch to static mode
        mapDiv.classList.remove('d-none');
        windyMapDiv.classList.add('d-none');
        animationControls.classList.add('d-none');
        legend.style.display = 'block';
        
        // Sync the view with Windy map if it exists
        if (windyMap) {
            const center = windyMap.getCenter();
            const zoom = windyMap.getZoom();
            map.setView([center.lat, center.lng], zoom);
        }
    }
}

// Update Windy Layer
function updateWindyLayer(layer) {
    if (windyAPI && animatedOverlayMode) {
        windyAPI.store.set('overlay', layer);
    }
}

// Play/Pause Animation
function togglePlayPause() {
    if (!windyAPI || !animatedOverlayMode) return;
    
    const { store } = windyAPI;
    const playPauseBtn = document.getElementById('playPauseBtn');
    
    if (isAnimationPlaying) {
        // Pause - Disable timeline animation
        // Note: Windy API uses store.set('play', false) to pause the timeline
        store.set('play', false);
        isAnimationPlaying = false;
        playPauseBtn.textContent = '▶ Play';
    } else {
        // Play
        store.set('play', true);
        isAnimationPlaying = true;
        playPauseBtn.textContent = '⏸ Pause';
    }
}

// Stop Animation
function stopAnimation() {
    if (!windyAPI || !animatedOverlayMode) return;
    
    const { store } = windyAPI;
    store.set('timestamp', Date.now());
    isAnimationPlaying = false;
    
    const playPauseBtn = document.getElementById('playPauseBtn');
    playPauseBtn.textContent = '▶ Play';
}

// Show temporary message
function showTemporaryMessage(message) {
    const messageDiv = document.createElement('div');
    messageDiv.className = 'alert alert-info alert-dismissible fade show position-fixed top-50 start-50 translate-middle';
    messageDiv.style.zIndex = '9999';
    
    // Safely set text content to avoid XSS
    messageDiv.textContent = message;
    
    // Create and append close button
    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'btn-close';
    closeBtn.setAttribute('data-bs-dismiss', 'alert');
    closeBtn.setAttribute('aria-label', 'Close');
    
    // Add manual click handler as fallback if Bootstrap isn't ready
    closeBtn.addEventListener('click', () => {
        if (messageDiv.parentNode) {
            messageDiv.remove();
        }
    });
    
    messageDiv.appendChild(closeBtn);
    
    document.body.appendChild(messageDiv);
    
    setTimeout(() => {
        if (messageDiv.parentNode) {
            messageDiv.remove();
        }
    }, 5000);
}

// Setup event listeners
function setupEventListeners() {
    // Animated Overlay Toggle
    document.getElementById('animatedOverlayToggle').addEventListener('change', (e) => {
        toggleAnimatedOverlay(e.target.checked);
    });
    
    // Windy Layer Change
    document.getElementById('windyLayer').addEventListener('change', (e) => {
        updateWindyLayer(e.target.value);
    });
    
    // Play/Pause Button
    document.getElementById('playPauseBtn').addEventListener('click', togglePlayPause);
    
    // Stop Animation Button
    document.getElementById('stopAnimationBtn').addEventListener('click', stopAnimation);
    
    // Data mode change
    document.querySelectorAll('input[name="dataMode"]').forEach(radio => {
        radio.addEventListener('change', (e) => {
            currentDataMode = e.target.value;
            toggleDateTimeSection();
            
            // Show warning if historical mode selected with animated overlays
            if (currentDataMode === 'historical' && animatedOverlayMode) {
                showTemporaryMessage('Animated overlays show forecast data. Switch to static mode for historical data.');
            }
        });
    });

    // Weather parameter change
    document.getElementById('weatherParam').addEventListener('change', (e) => {
        currentWeatherParam = e.target.value;
        updateLegend();
        
        // Sync with Windy layer if in animated mode
        if (animatedOverlayMode) {
            const windyLayer = parameterToWindyLayer[currentWeatherParam];
            if (windyLayer) {
                document.getElementById('windyLayer').value = windyLayer;
                updateWindyLayer(windyLayer);
            }
        }
    });

    // Units change
    document.querySelectorAll('input[name="units"]').forEach(radio => {
        radio.addEventListener('change', (e) => {
            currentUnits = e.target.value;
            updateLegend();
        });
    });

    // Update button
    document.getElementById('updateMap').addEventListener('click', updateMap);

    // Clear markers button
    document.getElementById('clearMarkers').addEventListener('click', clearMarkers);

    // Close data panel
    document.getElementById('closeDataPanel').addEventListener('click', () => {
        document.getElementById('dataPanel').classList.remove('active');
    });

    // Initial state
    toggleDateTimeSection();
}

// Toggle date/time section based on data mode
function toggleDateTimeSection() {
    const dateTimeSection = document.getElementById('dateTimeSection');
    if (currentDataMode === 'historical') {
        dateTimeSection.style.display = 'block';
    } else {
        dateTimeSection.style.display = 'none';
    }
}

// Update map (placeholder for now)
function updateMap() {
    showLoading(true);
    
    // Show a message that the feature is coming
    setTimeout(() => {
        showLoading(false);
        alert('Map layer updates are in progress. Click on the map to fetch weather data for specific locations.');
    }, 500);
}

// Map click handler
async function onMapClick(e) {
    const lat = e.latlng.lat.toFixed(4);
    const lon = e.latlng.lng.toFixed(4);

    showLoading(true);

    try {
        const data = await fetchWeatherData(lat, lon);
        displayWeatherData(data, lat, lon, e.latlng);
    } catch (error) {
        console.error('Error fetching weather data:', error);
        showError('Failed to fetch weather data: ' + error.message);
    } finally {
        showLoading(false);
    }
}

// Fetch weather data from API
async function fetchWeatherData(lat, lon) {
    const apiEndpoint = demoMode ? 'api-demo.php' : 'api.php';
    
    const params = new URLSearchParams({
        latitude: lat,
        longitude: lon,
        parameter: currentWeatherParam,
        mode: currentDataMode,
        units: currentUnits
    });

    if (currentDataMode === 'historical') {
        params.append('date', document.getElementById('dateInput').value);
        params.append('hour', document.getElementById('timeInput').value);
    }

    const response = await fetch(`${apiEndpoint}?${params.toString()}`);
    
    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const data = await response.json();
    
    if (data.error) {
        throw new Error(data.error);
    }
    
    return data;
}

// Display weather data
function displayWeatherData(data, lat, lon, latlng) {
    const dataPanel = document.getElementById('dataPanel');
    const dataPanelContent = document.getElementById('dataPanelContent');

    // Get parameter config
    const paramConfig = weatherParams[currentWeatherParam];
    const unit = paramConfig.unit[currentUnits];

    // Format value
    let value = data.value;
    let formattedValue = value;
    
    if (currentWeatherParam === 'weather_code') {
        formattedValue = `${value} (${weatherCodeDescriptions[value] || 'Unknown'})`;
    } else {
        formattedValue = `${value} ${unit}`;
    }

    // Build HTML
    let html = `
        <div class="data-item">
            <div class="data-label">Location</div>
            <div class="data-value">${lat}°, ${lon}°</div>
        </div>
        <div class="data-item">
            <div class="data-label">Parameter</div>
            <div class="data-value">${paramConfig.name}</div>
        </div>
        <div class="data-item">
            <div class="data-label">Value</div>
            <div class="data-value">${formattedValue}</div>
        </div>
        <div class="data-item">
            <div class="data-label">Time</div>
            <div class="data-value">${data.time || 'Current'}</div>
        </div>
    `;

    dataPanelContent.innerHTML = html;
    dataPanel.classList.add('active');

    // Add marker
    addMarker(latlng, value, unit);
}

// Add marker to map
function addMarker(latlng, value, unit) {
    const marker = L.marker(latlng, {
        icon: L.divIcon({
            className: 'weather-marker',
            html: `<div style="background: white; border: 2px solid #0d6efd; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 10px;">${value}</div>`,
            iconSize: [30, 30]
        })
    }).addTo(map);

    // Add popup
    marker.bindPopup(`<strong>${weatherParams[currentWeatherParam].name}</strong><br>${value} ${unit}`);

    markers.push(marker);
}

// Clear all markers
function clearMarkers() {
    markers.forEach(marker => map.removeLayer(marker));
    markers = [];
    document.getElementById('dataPanel').classList.remove('active');
}

// Update legend
function updateLegend() {
    const legendContent = document.getElementById('legendContent');
    const paramConfig = weatherParams[currentWeatherParam];
    const unit = paramConfig.unit[currentUnits];

    let html = `<div class="mb-2"><strong>${paramConfig.name}</strong></div>`;

    paramConfig.colors.forEach(item => {
        html += `
            <div class="legend-item">
                <div class="legend-color" style="background: ${item.color};"></div>
                <div class="legend-label">${item.label} ${unit}</div>
            </div>
        `;
    });

    legendContent.innerHTML = html;
}

// Show/hide loading overlay
function showLoading(show) {
    const overlay = document.getElementById('loadingOverlay');
    const spinner = document.getElementById('updateSpinner');
    const buttonText = document.getElementById('updateButtonText');

    if (show) {
        overlay.classList.remove('d-none');
        spinner.classList.remove('d-none');
        buttonText.textContent = 'Loading...';
    } else {
        overlay.classList.add('d-none');
        spinner.classList.add('d-none');
        buttonText.textContent = 'Update Map';
    }
}

// Show error message
function showError(message) {
    alert('Error: ' + message);
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', initMap);
