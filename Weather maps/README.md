# Interactive Weather Maps - North America

A comprehensive PHP web application that displays interactive weather maps for North America using real-time and historical data from the Open-Meteo API, with animated weather overlays powered by Windy API.

## Features

### 🎬 Animated Weather Overlays (NEW!)
- **Windy API Integration** - Beautiful animated weather visualizations
- **Toggle Display Mode** - Switch between static data view and animated overlays
- **Multiple Animation Layers**:
  - Wind patterns with particle animations
  - Temperature gradients
  - Rain/Precipitation forecasts
  - Cloud coverage
  - Atmospheric pressure systems
  - Ocean waves (for coastal areas)
- **Animation Controls**:
  - Play/Pause functionality
  - Stop button to reset timeline
  - Layer selector to choose which weather phenomenon to visualize
- **Seamless Integration** - Both modes share the same numerical data capabilities

### 🗺️ Interactive Map
- **Dual-Mode System**:
  - **Static Mode**: Leaflet.js-powered map with Open-Meteo data
  - **Animated Mode**: Windy-powered map with live weather animations
- Click anywhere on the map to fetch weather data for that location (works in both modes)
- Pan and zoom capabilities to explore different regions
- Visual markers showing selected locations with weather values
- Smooth transitions between static and animated modes

### 🌡️ Weather Parameters
Support for multiple weather parameters including:
- **Temperature (2m)** - Air temperature at 2 meters above ground
- **Precipitation** - Total precipitation (rain + snow)
- **Rain** - Liquid precipitation only
- **Snowfall** - Snow accumulation
- **Wind Speed** - Wind speed at 10 meters
- **Wind Direction** - Wind direction at 10 meters
- **Cloud Cover** - Percentage of sky covered by clouds
- **Atmospheric Pressure** - Sea level pressure
- **Relative Humidity** - Humidity percentage
- **Visibility** - Horizontal visibility distance
- **Weather Code** - General weather condition codes
- **Dew Point** - Dew point temperature
- **Apparent Temperature** - "Feels like" temperature

### 📅 Data Modes
- **Current Data** - Real-time weather information
- **Historical Data** - Access to weather data dating back to 1940
  - Date picker for easy selection
  - Hourly resolution (select specific hour)

### 📊 Data Display
- **Numerical Data Panel** - Shows detailed information for clicked locations:
  - Exact coordinates (latitude/longitude)
  - Weather parameter name
  - Current value with appropriate units
  - Timestamp of data
- **Color-coded Legend** - Visual representation of value ranges for each parameter
- **Multiple Markers** - Add multiple data points and compare

### ⚙️ User Controls
- **Display Mode Toggle** - Switch between static data view and animated overlays
- **Animation Controls** (when animated mode is enabled):
  - Layer selector for different weather phenomena
  - Play/Pause button for timeline control
  - Stop button to reset animation
- **Unit Toggle** - Switch between Metric and Imperial units
  - Temperature: °C / °F
  - Wind Speed: km/h / mph
  - Precipitation: mm / inch
  - Pressure: hPa / inHg
- **Parameter Selector** - Dropdown menu to choose weather parameter
- **Date/Time Picker** - Select historical date and hour (UTC)
- **Clear Markers** - Remove all markers from the map
- **Synchronized Controls** - Weather parameter selection automatically syncs with animation layer when applicable

### 🎨 User Interface
- Clean, modern design using Bootstrap 5
- Responsive layout that works on desktop and mobile devices
- Loading indicators for better user experience
- Error handling with user-friendly messages
- Intuitive control panel with organized sections

## Technical Implementation

### Frontend
- **HTML5** - Semantic markup
- **CSS3** - Custom styling with responsive design
- **JavaScript (ES6+)** - Interactive functionality
- **Leaflet.js 1.9.4** - Interactive mapping library (static mode)
- **Windy API v3** - Animated weather overlays (animated mode)
- **Bootstrap 5.3.2** - UI framework

### Backend
- **PHP** - API proxy server
- Handles requests to Open-Meteo API
- Parameter validation and error handling
- Supports both current and historical data endpoints

### APIs
- **Open-Meteo Forecast API** - Current weather data
- **Open-Meteo Archive API** - Historical weather data (1940-present)
- **Windy API** - Animated weather visualizations and forecasts
  - API Key: Pre-configured (can be updated in map.js)
  - Provides real-time animated overlays for multiple weather parameters

## Setup Instructions

### Prerequisites
- **Web Server** with PHP support (Apache, Nginx, or built-in PHP server)
- **PHP 7.0+** with `file_get_contents` enabled for external requests
- Modern web browser with JavaScript enabled
- Internet connection to access Open-Meteo API and CDN resources

### Installation

1. **Clone or download** this repository to your web server's document root

2. **Navigate** to the `Weather maps/` directory:
   ```bash
   cd "Weather maps"
   ```

3. **Ensure PHP can make external HTTP requests**:
   - Check that `allow_url_fopen` is enabled in `php.ini`
   - Alternatively, you can modify `api.php` to use cURL if needed

4. **Set appropriate permissions** (if on Linux/Unix):
   ```bash
   chmod 644 *.php *.html *.css *.js
   ```

## Running the Application

### Option 1: Using PHP Built-in Server (Development)

The easiest way to run the application for testing:

```bash
cd "Weather maps"
php -S localhost:8000
```

Then open your browser and navigate to:
```
http://localhost:8000/index.html
```

**Demo Mode (for testing without API access):**
```
http://localhost:8000/index.html?demo=true
```
This will use simulated weather data instead of calling the external Open-Meteo API.

### Option 2: Using Apache/Nginx (Production)

1. Place the `Weather maps/` directory in your web server's document root (e.g., `/var/www/html/` for Apache)

2. Access the application:
   ```
   http://your-domain.com/Weather maps/index.html
   ```
   Or if running locally:
   ```
   http://localhost/Weather maps/index.html
   ```

3. Make sure your web server is configured to execute PHP files

## How to Use

### Basic Usage

1. **Open the application** in your web browser
2. The map will load centered on North America in static mode
3. **Toggle Animated Overlays**: Use the "Animated Overlays" switch to enable animated weather visualizations
4. **Select a weather parameter** from the dropdown menu (default is Temperature)
5. **Choose data mode**:
   - **Current**: Click "Current" button for real-time data
   - **Historical**: Click "Historical" button and select a date/time (Note: animated overlays show forecast data only)
6. **Click anywhere on the map** to fetch weather data for that location (works in both modes)
7. View the data in the **Data Panel** on the right
8. Add multiple markers by clicking different locations
9. **Clear markers** using the "Clear Markers" button

### Using Animated Overlays

1. **Enable Animated Mode**: Toggle the "Animated Overlays" switch in the control panel
2. **Select Animation Layer**: Choose from Wind, Temperature, Rain, Clouds, Pressure, or Waves
3. **Control Playback**:
   - Click **Play** to start the animation timeline
   - Click **Pause** to freeze at current time
   - Click **Stop** to reset to current time
4. **Interact with Map**: Click anywhere to fetch numerical data while viewing animations
5. **Switch Back**: Toggle off "Animated Overlays" to return to static mode with full historical data access

### Advanced Features

- **Change Units**: Toggle between Metric and Imperial units (works in both modes)
- **Explore Different Parameters**: Use the dropdown to switch between temperature, precipitation, wind, etc.
- **Synchronized Layers**: When selecting compatible weather parameters, the animation layer auto-updates
- **Historical Analysis**: Disable animated overlays and select past dates to analyze historical weather patterns
- **Pan and Zoom**: Use mouse or touch gestures to explore different regions
- **View Legend**: Check the color-coded legend (in static mode) to understand value ranges

## File Structure

```
Weather maps/
├── index.html          # Main HTML page
├── styles.css          # Custom CSS styling
├── map.js              # JavaScript for map interactions
├── api.php             # PHP API proxy for Open-Meteo
├── api-demo.php        # Demo data generator (for testing)
├── README.md           # This file
├── API_DOCS.md         # Detailed API documentation
└── USAGE_GUIDE.md      # Comprehensive usage guide
```

## Documentation

- **[README.md](README.md)** - Overview, setup, and quick start (this file)
- **[API_DOCS.md](API_DOCS.md)** - Complete API reference, parameters, and examples
- **[USAGE_GUIDE.md](USAGE_GUIDE.md)** - Detailed usage instructions and use cases

## API Usage Notes

### Open-Meteo API

This application uses the Open-Meteo API, which is:
- **Free and open-source**
- **No API key required**
- **Attribution required** (included in the navbar)

### Windy API

The animated overlays are powered by Windy API:
- **API Key**: Pre-configured in the application (`VUlmt9CjBWsehQomhqHyFscMbw3dGMCX`)
- **How to Update API Key**: Edit the `WINDY_API_KEY` constant in `map.js` (line ~16)
- **Attribution**: Automatically included via Windy's library
- **Features**: Provides animated forecast visualizations for multiple weather parameters
- **Rate Limits**: Generous limits for non-commercial use. See [Windy API Documentation](https://api.windy.com/) for details

### Rate Limits

- Open-Meteo has generous rate limits for non-commercial use
- Windy API provides ample quota for testing and personal projects
- For high-traffic applications, consider:
  - Implementing caching mechanisms
  - Using commercial plans from providers
  - Adding request throttling

### Data Coverage

- **Current Data**: Global coverage, updated hourly
- **Historical Data**: 
  - Available from 1940 to present
  - Hourly resolution
  - Some parameters may have limited historical coverage

## Browser Compatibility

Tested and working on:
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Troubleshooting

### Common Issues

**Problem**: Map doesn't load
- **Solution**: Check internet connection and ensure CDN resources are accessible
- **Alternative**: Use demo mode by adding `?demo=true` to the URL

**Problem**: Animated overlays don't appear
- **Solution**: Ensure Windy API can be accessed. Check browser console for errors
- **Fallback**: The application will automatically fall back to static mode if Windy fails to load
- **API Key**: Verify the Windy API key is valid in `map.js`

**Problem**: "Failed to fetch data" error
- **Solution**: Verify PHP can make external HTTP requests (`allow_url_fopen` enabled)
- **Alternative**: Use demo mode for testing: `http://localhost:8000/index.html?demo=true`

**Problem**: Historical data returns errors
- **Solution**: Ensure selected date is between 1940-01-01 and yesterday
- **Note**: Animated overlays only work with forecast data, not historical data

**Problem**: No data for selected location
- **Solution**: Some parameters may not be available for all locations/times

**Problem**: Animation controls not responding
- **Solution**: Ensure animated overlays mode is enabled via the toggle switch
- **Check**: Verify you're in "Current" data mode (animated overlays show forecast data)

### Debug Mode

To enable error display in PHP (for development only):
1. Open `api.php`
2. Change `ini_set('display_errors', 0);` to `ini_set('display_errors', 1);`

## Future Enhancements

Potential improvements:
- [x] Animated weather layer overlays
- [ ] Time series charts for selected locations
- [ ] Weather forecast data (future predictions)
- [ ] Export data to CSV/JSON
- [ ] Compare multiple parameters side-by-side
- [ ] Save favorite locations
- [ ] Weather alerts and notifications
- [ ] Integration with more weather APIs

## Credits

- **Weather Data**: [Open-Meteo](https://open-meteo.com/)
- **Animated Overlays**: [Windy](https://www.windy.com/)
- **Mapping Library**: [Leaflet.js](https://leafletjs.com/)
- **UI Framework**: [Bootstrap](https://getbootstrap.com/)
- **Base Maps**: [OpenStreetMap](https://www.openstreetmap.org/)

## License

This project is open source and available for educational and non-commercial use.

## Contributing

Contributions, issues, and feature requests are welcome!

## Support

For questions or issues:
1. Check the Troubleshooting section above
2. Review Open-Meteo API documentation: https://open-meteo.com/en/docs
3. Check browser console for JavaScript errors

---

**Note**: This application requires an internet connection to function as it fetches data from external APIs in real-time.
