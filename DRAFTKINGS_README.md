# DraftKings Betting Odds Scraper

A Python script to scrape betting odds from DraftKings sportsbook using their public API.

## Features

- Scrapes betting odds for multiple sports
- Supports both text and JSON output formats
- Command-line interface for easy usage
- Error handling and logging
- No authentication required (uses public API)

## Supported Sports

- **NFL** (American Football - NFL)
- **NBA** (Basketball - NBA)
- **MLB** (Baseball - MLB)
- **NHL** (Hockey - NHL)
- **NCAAF** (College Football)
- **NCAAB** (College Basketball)
- **Soccer**
- **MMA** (Mixed Martial Arts)
- **Boxing**
- **Golf**
- **Tennis**

## Installation

1. Clone this repository or download the script
2. Install required dependencies:

```bash
pip install -r requirements.txt
```

Or install manually:

```bash
pip install requests
```

## Usage

### Basic Usage

Get NFL betting odds:
```bash
python draftkings_scraper.py --sport nfl
```

### Advanced Usage

Get NBA odds in JSON format:
```bash
python draftkings_scraper.py --sport nba --format json
```

Save MLB odds to a file:
```bash
python draftkings_scraper.py --sport mlb --output mlb_odds.txt
```

Limit results to 5 events:
```bash
python draftkings_scraper.py --sport nfl --limit 5
```

### Command-Line Arguments

- `--sport` (required): Sport to scrape odds for (e.g., nfl, nba, mlb)
- `--format`: Output format - 'text' or 'json' (default: text)
- `--output`: Output file path (default: print to console)
- `--limit`: Maximum number of events to return (default: 10)

### Python API Usage

You can also use the scraper as a Python module:

```python
from draftkings_scraper import DraftKingsScraper

# Create scraper instance
scraper = DraftKingsScraper()

# Get NFL odds
odds = scraper.scrape_sport_odds('nfl', limit=5)

# Format as JSON
json_output = scraper.format_odds_output(odds, format_type='json')
print(json_output)

# Or format as text
text_output = scraper.format_odds_output(odds, format_type='text')
print(text_output)
```

## Output Format

### Text Format Example

```
================================================================================
DRAFTKINGS BETTING ODDS
================================================================================

Event: Team A vs Team B
Start Time: 2025-12-23T18:00:00Z
Event ID: 12345

  Market: Moneyline
    Team A: -150 (Decimal: 1.67)
    Team B: +130 (Decimal: 2.30)

  Market: Point Spread
    Team A (-3.5): -110 (Decimal: 1.91)
    Team B (+3.5): -110 (Decimal: 1.91)

  Market: Total Points
    Over (45.5): -110 (Decimal: 1.91)
    Under (45.5): -110 (Decimal: 1.91)
--------------------------------------------------------------------------------
```

### JSON Format Example

```json
[
  {
    "event_id": 12345,
    "name": "Team A vs Team B",
    "start_time": "2025-12-23T18:00:00Z",
    "markets": [
      {
        "name": "Moneyline",
        "outcomes": [
          {
            "label": "Team A",
            "odds": "-150",
            "odds_decimal": "1.67",
            "line": null
          },
          {
            "label": "Team B",
            "odds": "+130",
            "odds_decimal": "2.30",
            "line": null
          }
        ]
      }
    ]
  }
]
```

## How It Works

The script uses DraftKings' public API endpoints to fetch betting odds data:

1. **Event Discovery**: Fetches available events for the specified sport
2. **Odds Extraction**: Retrieves detailed odds information for each event
3. **Data Parsing**: Extracts relevant information (teams, odds, lines, etc.)
4. **Output Formatting**: Presents data in human-readable or JSON format

## API Endpoints Used

- Event Groups: `https://sportsbook.draftkings.com/api/eventgroups/v1/sports/{sport_id}`
- Event Details: `https://sportsbook.draftkings.com/api/eventgroups/v1/events/{event_id}`

## Important Notes

- This script uses DraftKings' public API and does not require authentication
- The API structure may change over time, which could break the scraper
- Rate limiting may apply - use responsibly
- This tool is for informational purposes only
- Always verify odds on the official DraftKings website before placing bets
- Gambling should be done responsibly and only where legal

## Troubleshooting

### "Sport not supported" Error
Make sure you're using one of the supported sport identifiers listed above.

### Connection Errors
Check your internet connection and ensure DraftKings API is accessible.

### No odds found
The sport may not have any upcoming events, or the API structure may have changed.

## Legal Disclaimer

This script is provided for educational and informational purposes only. Users are responsible for ensuring their use of this script complies with:
- DraftKings Terms of Service
- Local gambling laws and regulations
- Web scraping policies

The authors are not responsible for any misuse of this tool.

## License

This project is open source and available for personal use.

## Contributing

Feel free to submit issues or pull requests to improve the scraper!
