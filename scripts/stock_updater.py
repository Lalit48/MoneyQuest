#!/usr/bin/env python3
"""
Stock Market Data Updater for MoneyQuest
Fetches real-time stock data from Alpha Vantage API and updates MySQL database
"""

import requests
import mysql.connector
import time
import os
from datetime import datetime
import logging

# Configuration
ALPHA_VANTAGE_API_KEY = "PKRXM1YIUV4LPGXS"  # Actual Alpha Vantage API key
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'moneyquest'
}

# Stock symbols to track
STOCK_SYMBOLS = ['AAPL', 'GOOGL', 'MSFT', 'AMZN', 'TSLA', 'META', 'NVDA', 'NFLX', 'ADBE', 'CRM']

# Setup logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('stock_updater.log'),
        logging.StreamHandler()
    ]
)

def get_db_connection():
    """Create and return database connection"""
    try:
        connection = mysql.connector.connect(**DB_CONFIG)
        return connection
    except mysql.connector.Error as err:
        logging.error(f"Database connection failed: {err}")
        return None

def fetch_stock_data(symbol, api_key):
    """Fetch stock data from Alpha Vantage API"""
    url = f"https://www.alphavantage.co/query"
    params = {
        'function': 'GLOBAL_QUOTE',
        'symbol': symbol,
        'apikey': api_key
    }
    
    try:
        response = requests.get(url, params=params, timeout=10)
        response.raise_for_status()
        data = response.json()
        
        if 'Global Quote' in data:
            quote = data['Global Quote']
            return {
                'symbol': symbol,
                'price': float(quote.get('05. price', 0)),
                'change': float(quote.get('09. change', 0)),
                'change_percent': quote.get('10. change percent', '0%'),
                'volume': int(quote.get('06. volume', 0)),
                'last_updated': datetime.now()
            }
        else:
            logging.warning(f"No data received for {symbol}: {data}")
            return None
            
    except requests.RequestException as e:
        logging.error(f"API request failed for {symbol}: {e}")
        return None
    except (KeyError, ValueError) as e:
        logging.error(f"Data parsing failed for {symbol}: {e}")
        return None

def update_stock_in_db(connection, stock_data):
    """Update stock data in MySQL database"""
    if not stock_data:
        return False
    
    try:
        cursor = connection.cursor()
        
        # Update or insert stock data
        query = """
        INSERT INTO stocks (symbol, name, current_price, last_updated) 
        VALUES (%s, %s, %s, %s)
        ON DUPLICATE KEY UPDATE 
        current_price = VALUES(current_price),
        last_updated = VALUES(last_updated)
        """
        
        # Get company name (you might want to maintain a separate mapping)
        company_names = {
            'AAPL': 'Apple Inc.',
            'GOOGL': 'Alphabet Inc.',
            'MSFT': 'Microsoft Corporation',
            'AMZN': 'Amazon.com Inc.',
            'TSLA': 'Tesla Inc.',
            'META': 'Meta Platforms Inc.',
            'NVDA': 'NVIDIA Corporation',
            'NFLX': 'Netflix Inc.',
            'ADBE': 'Adobe Inc.',
            'CRM': 'Salesforce Inc.'
        }
        
        company_name = company_names.get(stock_data['symbol'], stock_data['symbol'])
        
        cursor.execute(query, (
            stock_data['symbol'],
            company_name,
            stock_data['price'],
            stock_data['last_updated']
        ))
        
        connection.commit()
        cursor.close()
        
        logging.info(f"Updated {stock_data['symbol']}: ${stock_data['price']:.2f}")
        return True
        
    except mysql.connector.Error as err:
        logging.error(f"Database update failed for {stock_data['symbol']}: {err}")
        return False

def main():
    """Main function to update stock data"""
    logging.info("Starting stock data update...")
    
    # Get database connection
    connection = get_db_connection()
    if not connection:
        logging.error("Failed to connect to database")
        return
    
    try:
        updated_count = 0
        
        for symbol in STOCK_SYMBOLS:
            logging.info(f"Fetching data for {symbol}...")
            
            # Fetch stock data
            stock_data = fetch_stock_data(symbol, ALPHA_VANTAGE_API_KEY)
            
            if stock_data:
                # Update database
                if update_stock_in_db(connection, stock_data):
                    updated_count += 1
                
                # Rate limiting - Alpha Vantage has a limit of 5 requests per minute for free tier
                time.sleep(12)  # Wait 12 seconds between requests
            else:
                logging.warning(f"Failed to fetch data for {symbol}")
        
        logging.info(f"Stock update completed. Updated {updated_count}/{len(STOCK_SYMBOLS)} stocks")
        
    except Exception as e:
        logging.error(f"Unexpected error: {e}")
    
    finally:
        if connection:
            connection.close()
            logging.info("Database connection closed")

if __name__ == "__main__":
    main()