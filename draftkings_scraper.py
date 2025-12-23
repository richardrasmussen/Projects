#!/usr/bin/env python3
"""
DraftKings Betting Odds Scraper

This script scrapes betting odds from DraftKings sportsbook.
It uses the DraftKings public API to fetch odds data for various sports.
"""

import requests
import json
from typing import Dict, List, Optional
from datetime import datetime
import argparse


class DraftKingsScraper:
    """Scraper for DraftKings betting odds."""
    
    BASE_URL = "https://sportsbook.draftkings.com/api/sportscontent/v1/leagues"
    EVENTGROUPS_URL = "https://sportsbook.draftkings.com/api/eventgroups/v1"
    
    # Sport IDs from DraftKings
    SPORTS = {
        'nfl': 'american-football-nfl',
        'nba': 'basketball-nba',
        'mlb': 'baseball-mlb',
        'nhl': 'hockey-nhl',
        'soccer': 'soccer',
        'mma': 'mixed-martial-arts',
        'boxing': 'boxing',
        'golf': 'golf',
        'tennis': 'tennis',
        'ncaaf': 'american-football-ncaaf',
        'ncaab': 'basketball-ncaab'
    }
    
    def __init__(self):
        """Initialize the scraper."""
        self.session = requests.Session()
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Accept': 'application/json',
        })
    
    def get_sport_events(self, sport: str) -> Optional[Dict]:
        """
        Get events for a specific sport.
        
        Args:
            sport: Sport identifier (e.g., 'nfl', 'nba', 'mlb')
            
        Returns:
            Dictionary containing event data or None if request fails
        """
        if sport.lower() not in self.SPORTS:
            print(f"Error: Sport '{sport}' not supported.")
            print(f"Available sports: {', '.join(self.SPORTS.keys())}")
            return None
        
        sport_id = self.SPORTS[sport.lower()]
        
        try:
            # Try to get events from the eventgroups endpoint
            url = f"{self.EVENTGROUPS_URL}/sports/{sport_id}"
            response = self.session.get(url, timeout=10)
            response.raise_for_status()
            return response.json()
        except requests.exceptions.RequestException as e:
            print(f"Error fetching events: {e}")
            return None
    
    def get_event_odds(self, event_id: int) -> Optional[Dict]:
        """
        Get odds for a specific event.
        
        Args:
            event_id: The event ID from DraftKings
            
        Returns:
            Dictionary containing odds data or None if request fails
        """
        try:
            url = f"{self.EVENTGROUPS_URL}/events/{event_id}"
            response = self.session.get(url, timeout=10)
            response.raise_for_status()
            return response.json()
        except requests.exceptions.RequestException as e:
            print(f"Error fetching odds for event {event_id}: {e}")
            return None
    
    def parse_event(self, event: Dict) -> Dict:
        """
        Parse an event to extract key information.
        
        Args:
            event: Event dictionary from API
            
        Returns:
            Parsed event information
        """
        parsed = {
            'event_id': event.get('eventId'),
            'name': event.get('name'),
            'start_time': event.get('startDate'),
            'teams': [],
            'markets': []
        }
        
        # Extract team names
        if 'teamName1' in event and 'teamName2' in event:
            parsed['teams'] = [event['teamName1'], event['teamName2']]
        
        return parsed
    
    def parse_odds(self, odds_data: Dict) -> List[Dict]:
        """
        Parse odds data to extract betting lines.
        
        Args:
            odds_data: Odds dictionary from API
            
        Returns:
            List of parsed betting lines
        """
        lines = []
        
        if not odds_data or 'eventGroup' not in odds_data:
            return lines
        
        event_group = odds_data['eventGroup']
        events = event_group.get('events', [])
        
        for event in events:
            event_info = {
                'event_id': event.get('eventId'),
                'name': event.get('name'),
                'start_time': event.get('startDate'),
                'markets': []
            }
            
            # Extract markets (betting options)
            markets = event.get('markets', [])
            for market in markets:
                market_info = {
                    'name': market.get('name'),
                    'outcomes': []
                }
                
                # Extract outcomes (betting lines)
                outcomes = market.get('outcomes', [])
                for outcome in outcomes:
                    outcome_info = {
                        'label': outcome.get('label'),
                        'odds': outcome.get('oddsAmerican'),
                        'odds_decimal': outcome.get('oddsDecimal'),
                        'line': outcome.get('line')
                    }
                    market_info['outcomes'].append(outcome_info)
                
                event_info['markets'].append(market_info)
            
            lines.append(event_info)
        
        return lines
    
    def scrape_sport_odds(self, sport: str, limit: int = 10) -> List[Dict]:
        """
        Scrape betting odds for a specific sport.
        
        Args:
            sport: Sport identifier (e.g., 'nfl', 'nba', 'mlb')
            limit: Maximum number of events to return
            
        Returns:
            List of events with odds
        """
        print(f"Fetching odds for {sport.upper()}...")
        
        events_data = self.get_sport_events(sport)
        if not events_data:
            return []
        
        odds_list = self.parse_odds(events_data)
        
        # Limit results
        if limit and len(odds_list) > limit:
            odds_list = odds_list[:limit]
        
        print(f"Found {len(odds_list)} events")
        return odds_list
    
    def format_odds_output(self, odds_list: List[Dict], format_type: str = 'text') -> str:
        """
        Format odds data for output.
        
        Args:
            odds_list: List of odds data
            format_type: Output format ('text' or 'json')
            
        Returns:
            Formatted string
        """
        if format_type == 'json':
            return json.dumps(odds_list, indent=2)
        
        # Text format
        output = []
        output.append("=" * 80)
        output.append("DRAFTKINGS BETTING ODDS")
        output.append("=" * 80)
        
        for event in odds_list:
            output.append(f"\nEvent: {event['name']}")
            output.append(f"Start Time: {event['start_time']}")
            output.append(f"Event ID: {event['event_id']}")
            
            for market in event['markets']:
                output.append(f"\n  Market: {market['name']}")
                for outcome in market['outcomes']:
                    line_str = f" ({outcome['line']})" if outcome.get('line') else ""
                    output.append(
                        f"    {outcome['label']}{line_str}: "
                        f"{outcome['odds']} (Decimal: {outcome['odds_decimal']})"
                    )
            
            output.append("-" * 80)
        
        return "\n".join(output)


def main():
    """Main function to run the scraper from command line."""
    parser = argparse.ArgumentParser(
        description='Scrape betting odds from DraftKings',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog='''
Examples:
  # Get NFL odds
  python draftkings_scraper.py --sport nfl
  
  # Get NBA odds in JSON format
  python draftkings_scraper.py --sport nba --format json
  
  # Get MLB odds and save to file
  python draftkings_scraper.py --sport mlb --output odds.txt
  
  # Limit to 5 events
  python draftkings_scraper.py --sport nfl --limit 5

Available sports:
  nfl, nba, mlb, nhl, soccer, mma, boxing, golf, tennis, ncaaf, ncaab
        '''
    )
    
    parser.add_argument(
        '--sport',
        type=str,
        required=True,
        help='Sport to scrape odds for (e.g., nfl, nba, mlb)'
    )
    
    parser.add_argument(
        '--format',
        type=str,
        choices=['text', 'json'],
        default='text',
        help='Output format (default: text)'
    )
    
    parser.add_argument(
        '--output',
        type=str,
        help='Output file path (default: print to console)'
    )
    
    parser.add_argument(
        '--limit',
        type=int,
        default=10,
        help='Maximum number of events to return (default: 10)'
    )
    
    args = parser.parse_args()
    
    # Create scraper and fetch odds
    scraper = DraftKingsScraper()
    odds_list = scraper.scrape_sport_odds(args.sport, args.limit)
    
    if not odds_list:
        print("No odds found or error occurred.")
        return
    
    # Format output
    output = scraper.format_odds_output(odds_list, args.format)
    
    # Write to file or print
    if args.output:
        with open(args.output, 'w') as f:
            f.write(output)
        print(f"\nOdds saved to {args.output}")
    else:
        print(output)


if __name__ == '__main__':
    main()
