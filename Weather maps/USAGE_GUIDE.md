# Usage Guide - Interactive Weather Maps

## Quick Start

### Starting the Application

1. Open a terminal and navigate to the Weather maps directory:
   ```bash
   cd "Weather maps"
   ```

2. Start the PHP development server:
   ```bash
   php -S localhost:8000
   ```

3. Open your browser and go to:
   ```
   http://localhost:8000/index.html
   ```

4. For testing without internet/API access, use demo mode:
   ```
   http://localhost:8000/index.html?demo=true
   ```

## Basic Usage

### Viewing Current Weather Data

1. **Select a weather parameter** from the dropdown menu
   - Default is "Temperature (2m)"
   
2. **Choose units** - Metric or Imperial
   - Metric: °C, km/h, mm, hPa
   - Imperial: °F, mph, inches, inHg

3. **Click anywhere on the map** to fetch weather data
   - A marker will appear at the clicked location
   - Weather data will display in the data panel

4. **View the legend** to understand the color coding for the selected parameter

### Viewing Historical Weather Data

1. **Switch to Historical mode** by clicking the "Historical" button

2. **Select a date** using the date picker
   - Available range: 1940-01-01 to yesterday
   
3. **Select an hour** (0-23, UTC time)
   - Default is 12:00 (noon)

4. **Click on the map** to fetch historical data for that location and time

5. The data panel will show:
   - Selected location coordinates
   - Weather parameter value
   - Exact timestamp
   - Selected date and hour

## Advanced Features

### Comparing Multiple Locations

1. Click on multiple locations on the map
2. Each click adds a new marker
3. The data panel updates to show the most recent location
4. All markers remain visible for comparison
5. Use "Clear Markers" button to remove all markers

### Exploring Different Weather Parameters

Try each parameter to understand different weather conditions:

**Temperature Parameters:**
- **Temperature (2m)** - Air temperature at human height
- **Apparent Temperature** - "Feels like" temperature
- **Dew Point** - Temperature at which air becomes saturated

**Precipitation Parameters:**
- **Precipitation** - Total precipitation (rain + snow)
- **Rain** - Liquid precipitation only
- **Snowfall** - Snow accumulation

**Wind Parameters:**
- **Wind Speed** - How fast the wind is blowing
- **Wind Direction** - Direction from which wind is coming (0-360°)

**Atmospheric Parameters:**
- **Pressure (Sea Level)** - Atmospheric pressure
- **Cloud Cover** - Percentage of sky covered by clouds
- **Relative Humidity** - Moisture content in air
- **Visibility** - How far you can see
- **Weather Code** - General weather condition

## Use Cases

### 1. Real-Time Weather Monitoring

**Scenario:** Check current weather conditions across North America

**Steps:**
1. Keep "Current" mode selected
2. Select "Temperature (2m)"
3. Click on cities of interest (New York, Los Angeles, Chicago, etc.)
4. Compare values across locations
5. Switch parameters to see wind, precipitation, etc.

### 2. Historical Weather Analysis

**Scenario:** Analyze weather patterns from a specific date

**Steps:**
1. Switch to "Historical" mode
2. Select a date (e.g., 2010-07-04 for Independence Day)
3. Choose time of day (e.g., 14:00 for afternoon)
4. Click on multiple locations
5. Compare historical conditions

### 3. Storm Tracking

**Scenario:** Monitor precipitation and wind during a weather event

**Steps:**
1. Select "Precipitation" parameter
2. Click on areas of interest
3. Check legend for precipitation levels
4. Switch to "Wind Speed" to see wind patterns
5. Switch to "Pressure (Sea Level)" to identify pressure systems

### 4. Temperature Gradient Analysis

**Scenario:** Understand temperature variations across regions

**Steps:**
1. Select "Temperature (2m)"
2. Click on locations from north to south
3. Observe temperature changes with latitude
4. Check legend for temperature ranges
5. Compare with "Apparent Temperature" for perceived warmth

### 5. Historical Climate Research

**Scenario:** Study long-term weather patterns

**Steps:**
1. Switch to "Historical" mode
2. Select same location for different years
3. Choose same date/time for comparison
4. Record values for analysis
5. Switch between parameters to analyze multiple factors

## Tips and Tricks

### Efficient Navigation

- **Zoom In/Out:** Use mouse wheel or pinch gestures
- **Pan:** Click and drag the map
- **Reset View:** Refresh the page to return to default North America view

### Data Interpretation

- **Temperature:** Higher values = warmer, lower values = colder
- **Precipitation:** 0 = dry, >10mm = significant rain
- **Wind Speed:** 0-10 km/h = calm, >50 km/h = strong winds
- **Cloud Cover:** 0% = clear sky, 100% = completely overcast
- **Pressure:** <1000 hPa = low pressure (storms), >1020 hPa = high pressure (fair weather)
- **Humidity:** <30% = dry, >80% = humid
- **Visibility:** <1 km = very poor, >10 km = good

### Weather Codes Quick Reference

- **0-3:** Clear to cloudy
- **45-48:** Foggy conditions
- **51-57:** Drizzle
- **61-67:** Rain (light to heavy)
- **71-77:** Snow
- **80-82:** Rain showers
- **95-99:** Thunderstorms

### Best Practices

1. **Start with Current Mode** to understand current conditions
2. **Use Demo Mode** for testing and learning the interface
3. **Check Multiple Parameters** for complete weather picture
4. **Compare Locations** by clicking multiple points
5. **Use Historical Mode** to verify past weather events
6. **Clear Markers Regularly** to avoid map clutter

## Keyboard Shortcuts

While the application doesn't have dedicated keyboard shortcuts, you can:
- **Tab** through form controls
- **Enter** to submit forms (if applicable)
- **Arrow keys** to navigate select dropdowns

## Mobile Usage

The application is responsive and works on mobile devices:

### Touch Gestures
- **Tap** on map to select location
- **Pinch** to zoom in/out
- **Drag** to pan the map
- **Tap** controls to change settings

### Mobile Tips
- Rotate device to landscape for better map view
- Use the control panel in portrait mode
- Data panel appears at bottom on mobile
- Legend is scaled for smaller screens

## Example Scenarios

### Scenario 1: Planning a Trip

**Goal:** Check weather for vacation destination

1. Switch to Historical mode
2. Select date of travel
3. Click on destination city
4. Check temperature, precipitation, wind
5. Compare with current weather
6. Make informed decisions

### Scenario 2: Agricultural Planning

**Goal:** Analyze precipitation patterns

1. Select "Precipitation" parameter
2. Use Historical mode
3. Check multiple dates across growing season
4. Click on farm locations
5. Record precipitation data
6. Use for crop planning

### Scenario 3: Educational Use

**Goal:** Teach students about weather patterns

1. Use Demo Mode for consistent results
2. Show temperature variations by latitude
3. Demonstrate wind patterns
4. Explain pressure systems
5. Analyze historical events (hurricanes, storms)
6. Compare seasonal differences

### Scenario 4: Weather Photography

**Goal:** Find locations with interesting weather

1. Check "Cloud Cover" for dramatic skies
2. Look at "Visibility" for clear shots
3. Check "Wind Speed" for calm conditions
4. Use Historical data to find patterns
5. Plan shoots around weather conditions

## Troubleshooting Common Issues

### Map Doesn't Load
- Check internet connection
- Verify CDN resources are accessible
- Try demo mode if external resources blocked
- Clear browser cache
- Try a different browser

### Data Not Fetching
- Verify PHP server is running
- Check PHP error logs
- Ensure `allow_url_fopen` is enabled
- Try demo mode to isolate issue
- Check firewall settings

### Markers Not Appearing
- Ensure you clicked on valid map area
- Check browser console for errors
- Verify JavaScript is enabled
- Try clearing existing markers first

### Historical Data Errors
- Verify date is between 1940 and yesterday
- Check date format (YYYY-MM-DD)
- Ensure hour is between 0-23
- Some parameters may not have historical data

### Performance Issues
- Clear markers regularly
- Close other browser tabs
- Reduce zoom level for better performance
- Use demo mode if API is slow

## Getting Help

If you encounter issues not covered in this guide:

1. Check the main README.md for setup instructions
2. Review API_DOCS.md for API details
3. Check browser console for error messages
4. Verify PHP server logs for backend errors
5. Try demo mode to isolate frontend vs backend issues

## Further Learning

To get the most out of this application:

1. **Learn about weather patterns** - Understanding meteorology helps interpret data
2. **Explore Open-Meteo documentation** - Learn about data sources and accuracy
3. **Experiment with parameters** - Try all 13 weather parameters
4. **Compare locations** - Click on different geographic features (mountains, coasts, plains)
5. **Analyze historical events** - Research major weather events and view their data

## Acknowledgments

- Weather data provided by [Open-Meteo](https://open-meteo.com/)
- Maps powered by [Leaflet.js](https://leafletjs.com/)
- Base map tiles from [OpenStreetMap](https://www.openstreetmap.org/)
