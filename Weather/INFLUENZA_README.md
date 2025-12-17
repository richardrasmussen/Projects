# Missouri Influenza Data Web Scraper

This system fetches and displays weekly influenza case data for St. Louis, Missouri using web scraping from the Missouri Department of Health and Senior Services (DHSS).

## Files

### `InfluenzaScraper.php`
The core scraper class that handles fetching and parsing data from Missouri DHSS.

**Key Features:**
- `MissouriInfluenzaScraper` - Scrapes HTML from the DHSS dashboard
- `CDCFluViewScraper` - Alternative source for CDC regional flu data
- Automatic caching (1 hour default) to reduce server load
- Error handling and fallback to sample data
- Support for extracting data from tables, embedded JSON, and data attributes

**Public Methods:**
- `fetchFluData()` - Fetch flu data (uses cache if available)
- `getCacheAge()` - Get how old the cached data is
- `clearCache()` - Clear the cached data

### `InfluenzaCases.php`
The main display page that shows influenza statistics with interactive features.

**Features:**
- Weekly case counts, hospitalizations, and deaths
- Visual trend bars for case volume
- Date range filtering
- Refresh button to clear cache and fetch fresh data
- Cache age indicator
- Responsive design with professional styling

## Usage

### Basic View
Simply open `InfluenzaCases.php` in your browser:
```
http://yourserver.com/Weather/InfluenzaCases.php
```

### Refresh Data
Click the "Refresh Data" button to clear the cache and fetch latest data.

### Date Range Filtering
Use the start and end date inputs to filter the displayed data.

### API Endpoint
The scraper includes API endpoints for programmatic access:

**Fetch latest data:**
```
GET InfluenzaScraper.php?action=fetch
```

**Clear cache:**
```
GET InfluenzaScraper.php?action=clear_cache
```

## How It Works

1. **Scraping Process:**
   - Fetches HTML from Missouri DHSS dashboard
   - Parses HTML tables for weekly data
   - Extracts case counts, hospitalizations, deaths
   - Caches result for 1 hour

2. **Fallback Logic:**
   - If scraping fails, displays sample data
   - Shows error message indicating data source issue
   - User can refresh to retry

3. **Caching:**
   - Data cached in `cache/mo_flu_data.json`
   - Cache expires after 1 hour
   - Manual refresh bypasses cache

## Data Sources

### Primary
- **Missouri DHSS Influenza Dashboard**
  - URL: https://health.mo.gov/living/healthcondiseases/communicable/influenza/dashboard.php
  - Weekly surveillance reports
  - State-level data

### Secondary (CDC)
- **CDC FluView Weekly Reports**
  - URL: https://www.cdc.gov/flu/weekly/
  - National and regional data
  - HHS Region 7 covers St. Louis area

### Tertiary
- **St. Louis County Health Department**
  - URL: https://www.stlouiscounty.com/dph/
  - Local health department
  - County-level data (when available)

## Limitations

1. **Data Granularity:**
   - Data is reported **by week**, not by individual days
   - Weekly cycles typically end on Saturday

2. **Reporting Delays:**
   - 1-2 week delay in official reporting
   - Real-time data not available

3. **Web Scraping Constraints:**
   - Depends on DHSS website structure remaining stable
   - DHSS may update their dashboard design
   - Scraper would need adjustment if HTML structure changes

## Customization

### Change Cache Expiry
In `InfluenzaScraper.php`, modify:
```php
private $cacheExpiry = 3600; // Change 3600 to desired seconds
```

### Change Data Source URL
In `InfluenzaScraper.php`:
```php
private $dashboardUrl = 'YOUR_URL_HERE';
```

### Add Custom Data Extraction
Modify the `extractTableData()` or `extractEmbeddedData()` methods in `MissouriInfluenzaScraper` class to match your data structure.

## Technical Requirements

- PHP 7.2+
- cURL extension enabled
- DOM/XPath support
- Write permissions for `/cache` directory
- Outbound HTTPS connections allowed

## Troubleshooting

### "Could not fetch live data from Missouri DHSS"
- Check internet connectivity
- Verify DHSS website is accessible
- Check if website structure has changed
- Review `cache/mo_flu_data.json` for cached data

### Cache directory permission errors
```bash
mkdir -p /var/www/html/Weather/cache
chmod 755 /var/www/html/Weather/cache
```

### No data appearing
- Click "Refresh Data" to fetch fresh data
- Check cache is being created: `cache/mo_flu_data.json`
- Review error messages shown on page
- Check PHP error logs

## Future Enhancements

1. **Database Storage:**
   - Store historical data in database
   - Enable trend analysis over years

2. **Scheduled Updates:**
   - Cron job to auto-refresh data weekly
   - Email alerts for spikes in cases

3. **Advanced Analytics:**
   - 7-day moving averages
   - Year-over-year comparisons
   - Forecasting models

4. **Multi-Region:**
   - Display data for other Missouri regions
   - Regional comparison charts

5. **Integration:**
   - Combine with weather data (AbsoluteHumidity.php)
   - Correlate humidity with flu activity
   - Export data to CSV/Excel

## License

This script is provided as-is for tracking public health data from Missouri DHSS.
