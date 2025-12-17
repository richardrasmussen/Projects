# Influenza and Absolute Humidity Analysis

## Overview
This system provides integrated analysis of weekly influenza cases and absolute humidity data for St. Louis, Missouri, allowing users to explore potential correlations between environmental humidity and flu activity.

## Files

### `InfluenzaCases.php` (Enhanced)
Main dashboard showing influenza case data with optional absolute humidity overlay.

**Features:**
- Weekly influenza cases, hospitalizations, and deaths
- Historical data spanning 2022-2025
- **NEW: Toggle absolute humidity overlay** - Click "Show Absolute Humidity" button
- When enabled, displays average weekly absolute humidity alongside case data
- Color-coded humidity values (blue = dry, red = humid)
- Date range filtering
- Refresh controls with cache information

**Data Display:**
- Week starting and ending dates
- Confirmed cases (primary focus)
- Hospitalizations
- Deaths (when applicable)
- Absolute humidity (when overlay enabled) - in g/m³
- Visual trend bar for case volume

### `WeeklyAbsoluteHumidity.php` (New)
Standalone page for viewing absolute humidity data aggregated by week.

**Features:**
- Weekly average absolute humidity
- Data derived from hourly weather data (Open-Meteo archive)
- Visual representation of humidity trends
- Shows data point count for each week
- Color-coded: blue = low humidity (dry), red = high humidity (humid)
- Date range filtering

**Data:**
- Latitude: 38.2203 (Festus, MO)
- Longitude: -90.3954
- Source: Open-Meteo historical weather archive
- Temperature unit: Fahrenheit
- Calculated from temperature and relative humidity

## How to Use

### View Influenza Cases Only
```
InfluenzaCases.php
```
- Shows weekly case counts, hospitalizations, deaths
- Set date range to view specific periods
- Click "Refresh Data" to clear cache and fetch fresh data

### Add Absolute Humidity Overlay
```
InfluenzaCases.php?show_ah=1
```
- Click "Show Absolute Humidity" button on the page
- An additional column appears showing weekly average absolute humidity
- Humidity is color-coded for quick visual reference
- Use to explore potential environmental correlations with flu activity

### View Weekly Absolute Humidity Only
```
WeeklyAbsoluteHumidity.php
```
- Dedicated page for humidity analysis
- Shows only absolute humidity data by week
- Visual trend bars for humidity patterns
- Useful for independent weather analysis

### Apply Date Ranges
Both pages support date filtering:
```
?start_date=2024-10-01&end_date=2025-03-31
```

## Data Sources

### Influenza Cases
- **Primary:** Missouri DHSS Influenza Dashboard (via web scraping)
- **Fallback:** Sample historical data (when scraping unavailable)
- **Data Points:** Cases, hospitalizations, deaths
- **Granularity:** Weekly
- **History:** 2022-2025

### Absolute Humidity
- **Source:** Open-Meteo Historical Weather API
- **Data:** Temperature (°F) and relative humidity (%)
- **Calculation:** Magnus formula for saturation vapor pressure
- **Output:** g/m³ (grams per cubic meter)
- **Granularity:** Hourly data aggregated to weekly averages

## Analysis & Interpretation

### Absolute Humidity Basics
- **Definition:** Mass of water vapor per unit volume of air
- **Units:** g/m³ (grams per cubic meter)
- **Range (typical):** 2-20 g/m³
- **Factors:** Temperature and relative humidity

### Flu and Environmental Factors
Research suggests correlations between:
- **Low absolute humidity** → Higher flu transmission risk
- **Winter months** → Lower outdoor humidity, higher indoor heating reduces humidity
- **Peak flu season** → Typically January-February in Northern Hemisphere

### Using the Overlay
1. View influenza cases with absolute humidity
2. Look for patterns:
   - Do flu cases peak when humidity is low?
   - Is there a lag between humidity changes and case changes?
   - Are peaks aligned or offset?

## Technical Details

### Absolute Humidity Calculation
```
AH = (216.7 × VP) / (273.15 + T_C)
```

Where:
- VP = Vapor pressure (hPa)
- T_C = Temperature in Celsius
- VP = (RH/100) × SVP
- SVP = 6.112 × exp((17.67 × T_C) / (T_C + 243.5))

### Weekly Aggregation
- Start date: Monday of each week
- End date: Sunday of each week
- Hourly values: Averaged to get weekly mean
- Visualization: Scaled to min/max values in range

### Caching
- Influenza data: 1-hour cache (can be cleared manually)
- Absolute humidity: Fetched on-demand from Open-Meteo
- Cache location: `Weather/cache/mo_flu_data.json`

## Browser Requirements
- Modern JavaScript-capable browser
- Support for HTML date inputs
- Cookies enabled (for data retention)

## Future Enhancements

1. **Statistical Analysis**
   - Correlation coefficient between humidity and cases
   - Lag analysis (cases delayed from humidity changes)
   - Moving averages and trend lines

2. **Additional Environmental Data**
   - Temperature trends
   - Precipitation
   - UV index
   - Air quality (PM2.5, PM10)

3. **Visualization Improvements**
   - Dual-axis graph (cases vs humidity)
   - Heatmap showing relationships
   - Downloadable charts

4. **Data Export**
   - CSV export for both datasets
   - Excel with formatting
   - JSON for external analysis

5. **Regional Expansion**
   - Other Missouri cities
   - Regional comparison
   - Multi-state analysis

## Troubleshooting

### No absolute humidity data showing
- Ensure internet connectivity (fetches from Open-Meteo)
- Check that "Show Absolute Humidity" is toggled on
- Date range may not have available weather data

### Influenza data is stale
- Click "Refresh Data" button to clear cache
- Wait 1 hour for automatic cache expiration
- Check Missouri DHSS website for latest data

### Page loads slowly
- Absolute humidity calculation is data-intensive
- First load may take 10-30 seconds
- Subsequent views use cached data

## References

- **Open-Meteo API:** https://open-meteo.com/
- **Missouri DHSS:** https://health.mo.gov/living/healthcondiseases/communicable/influenza/
- **CDC FluView:** https://www.cdc.gov/flu/weekly/
- **Influenza & Humidity Research:** Recent studies show correlation between low absolute humidity and increased influenza transmission

## Support
For issues or feature requests, contact the development team or refer to the technical documentation in the code comments.
