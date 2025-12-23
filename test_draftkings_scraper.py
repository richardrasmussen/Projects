#!/usr/bin/env python3
"""
Test script for DraftKings scraper with mock data.
This demonstrates how the scraper works with sample data.
"""

import json
from draftkings_scraper import DraftKingsScraper


def test_with_mock_data():
    """Test the scraper with mock data."""
    
    # Mock response data similar to what DraftKings API returns
    mock_odds_data = {
        "eventGroup": {
            "events": [
                {
                    "eventId": 123456,
                    "name": "Kansas City Chiefs vs Buffalo Bills",
                    "startDate": "2025-12-24T18:00:00Z",
                    "markets": [
                        {
                            "name": "Moneyline",
                            "outcomes": [
                                {
                                    "label": "Kansas City Chiefs",
                                    "oddsAmerican": "-150",
                                    "oddsDecimal": "1.67",
                                    "line": None
                                },
                                {
                                    "label": "Buffalo Bills",
                                    "oddsAmerican": "+130",
                                    "oddsDecimal": "2.30",
                                    "line": None
                                }
                            ]
                        },
                        {
                            "name": "Point Spread",
                            "outcomes": [
                                {
                                    "label": "Kansas City Chiefs",
                                    "oddsAmerican": "-110",
                                    "oddsDecimal": "1.91",
                                    "line": "-3.5"
                                },
                                {
                                    "label": "Buffalo Bills",
                                    "oddsAmerican": "-110",
                                    "oddsDecimal": "1.91",
                                    "line": "+3.5"
                                }
                            ]
                        },
                        {
                            "name": "Total Points",
                            "outcomes": [
                                {
                                    "label": "Over",
                                    "oddsAmerican": "-110",
                                    "oddsDecimal": "1.91",
                                    "line": "47.5"
                                },
                                {
                                    "label": "Under",
                                    "oddsAmerican": "-110",
                                    "oddsDecimal": "1.91",
                                    "line": "47.5"
                                }
                            ]
                        }
                    ]
                },
                {
                    "eventId": 123457,
                    "name": "Los Angeles Lakers vs Boston Celtics",
                    "startDate": "2025-12-24T20:30:00Z",
                    "markets": [
                        {
                            "name": "Moneyline",
                            "outcomes": [
                                {
                                    "label": "Los Angeles Lakers",
                                    "oddsAmerican": "+120",
                                    "oddsDecimal": "2.20",
                                    "line": None
                                },
                                {
                                    "label": "Boston Celtics",
                                    "oddsAmerican": "-140",
                                    "oddsDecimal": "1.71",
                                    "line": None
                                }
                            ]
                        }
                    ]
                }
            ]
        }
    }
    
    print("Testing DraftKings Scraper with Mock Data")
    print("=" * 80)
    
    # Create scraper instance
    scraper = DraftKingsScraper()
    
    # Parse the mock odds data
    print("\n1. Testing parse_odds() method...")
    odds_list = scraper.parse_odds(mock_odds_data)
    print(f"   Successfully parsed {len(odds_list)} events")
    
    # Format as text
    print("\n2. Testing text formatting...")
    text_output = scraper.format_odds_output(odds_list, format_type='text')
    print(text_output)
    
    # Format as JSON
    print("\n3. Testing JSON formatting...")
    json_output = scraper.format_odds_output(odds_list, format_type='json')
    print("JSON Output (first 500 characters):")
    print(json_output[:500] + "...\n")
    
    # Verify data structure
    print("4. Verifying parsed data structure...")
    assert len(odds_list) == 2, "Should have 2 events"
    assert odds_list[0]['event_id'] == 123456, "First event ID should match"
    assert odds_list[0]['name'] == "Kansas City Chiefs vs Buffalo Bills", "Event name should match"
    assert len(odds_list[0]['markets']) == 3, "First event should have 3 markets"
    assert odds_list[0]['markets'][0]['name'] == "Moneyline", "First market should be Moneyline"
    print("   ✓ All data structure checks passed!")
    
    print("\n" + "=" * 80)
    print("TEST PASSED - Scraper logic works correctly!")
    print("=" * 80)


if __name__ == '__main__':
    test_with_mock_data()
