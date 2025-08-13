import csv
import requests
from datetime import datetime

# --- Config ---
SHEET1_CSV = 'Sheet1-Table 1.csv'  # CSV with trades: Trade#,Type,DateTime
UNIT = "minutes"
INTERVAL = "1"
INVESTMENT_PER_STOCK = 500000
BROKERAGE_PER_SHARE = 0.0013
ACCESS_TOKEN = "YOUR_ACCESS_TOKEN"  # Replace with your actual token. Get it from Upstox Developer Console.

# --- Hardcoded stock list with known instrument keys ---
INSTRUMENT_KEY_MAP = {
    "RELIANCE": "NSE_EQ|INE002A01018",
    "TCS": "NSE_EQ|INE467B01029",
    "HDFCBANK": "NSE_EQ|INE040A01026",
    "INFY": "NSE_EQ|INE009A01021",
    "ICICIBANK": "NSE_EQ|INE090A01021",
    "KOTAKBANK": "NSE_EQ|INE237A01028",
    "SBIN": "NSE_EQ|INE062A01020",
    "BHARTIARTL": "NSE_EQ|INE397D01024",
    "HINDUNILVR": "NSE_EQ|INE030A01027",
    "ITC": "NSE_EQ|INE154A01025",

    # First column stocks
    "ABCAPITAL": "NSE_EQ|INE674K01013",
    "ACC": "NSE_EQ|INE012A01025",
    "AARTIIND": "NSE_EQ|INE769A01020",
    "ALKEM": "NSE_EQ|INE540L01014",
    "APLAPOLLO": "NSE_EQ|INE702C01027",
    "ASHOKLEY": "NSE_EQ|INE208A01029",
    "ASTRAL": "NSE_EQ|INE006I01046",
    "AUBANK": "NSE_EQ|INE949L01017",
    "AUROPHARMA": "NSE_EQ|INE406A01037",
    "BALKRISIND": "NSE_EQ|INE787D01026",
    "BANDHANBNK": "NSE_EQ|INE545U01014",
    "BANKINDIA": "NSE_EQ|INE084A01016",
    "BHARATFORG": "NSE_EQ|INE465A01025",
    "BHEL": "NSE_EQ|INE257A01026",
    "COFORGE": "NSE_EQ|INE591G01017",
    "COLPAL": "NSE_EQ|INE259A01022",
    "CONCOR": "NSE_EQ|INE111A01025",
    "CUMMINSIND": "NSE_EQ|INE298A01020",
    "DALBHARAT": "NSE_EQ|INE00R701025",
    "DIXON": "NSE_EQ|INE935N01012",
    "DELHIVERY": "NSE_EQ|INE148O01012",
    "FEDERALBNK": "NSE_EQ|INE171A01029",
    "NAUKRI": "NSE_EQ|INE663F01024",
    "GLENMARK": "NSE_EQ|INE935A01035",
    "GMRINFRA": "NSE_EQ|INE776C01039",
    "GODREJPROP": "NSE_EQ|INE484D01022",
    "HINDPETRO": "NSE_EQ|INE094A01015",
    "IDFCFIRSTB": "NSE_EQ|INE092A01019",
    "INDIANB": "NSE_EQ|INE562A01011",
    "IGL": "NSE_EQ|INE203G01027",
    "IRCTC": "NSE_EQ|INE335Y01020",
    "JSL": "NSE_EQ|INE220G01021",
    "JUBLFOOD": "NSE_EQ|INE797F01012",
    "KALYANKJIL": "NSE_EQ|INE303R01014",
    "KPITTECH": "NSE_EQ|INE04I401011",
    "LAURUSLABS": "NSE_EQ|INE947Q01028",
    "LICHSGFIN": "NSE_EQ|INE115A01026",
    "LUPIN": "NSE_EQ|INE326A01037",
    "MAXHEALTH": "NSE_EQ|INE027H01010",
    "MAZDOCK": "NSE_EQ|INE249I01017",
    "MPHASIS": "NSE_EQ|INE356A01018",
    "MUTHOOTFIN": "NSE_EQ|INE414G01012",
    "NHPC": "NSE_EQ|INE848E01016",
    "NMDC": "NSE_EQ|INE584A01023",
    "OBEROIRLTY": "NSE_EQ|INE093I01010",
    "OIL": "NSE_EQ|INE274J01014",
    "PAYTM": "NSE_EQ|INE982J01020",
    "OFSS": "NSE_EQ|INE881D01027",
    "PATANJALI": "NSE_EQ|INE619A01035",
    "PERSISTENT": "NSE_EQ|INE262H01013",
    "PETRONET": "NSE_EQ|INE347G01014",
    "PIIND": "NSE_EQ|INE603J01030",
    "PRESTIGE": "NSE_EQ|INE811K01011",
    "RAILVIKAS": "NSE_EQ|INE415G01027",
    "SAIL": "NSE_EQ|INE114A01011",
    "SJVN": "NSE_EQ|INE002L01015",
    "SOLARINDS": "NSE_EQ|INE343H01029",
    "SONACOMS": "NSE_EQ|INE07V001025",
    "SUPREMEIND": "NSE_EQ|INE195A01028",
    "TATACOMM": "NSE_EQ|INE151A01013",
    "TATATECH": "NSE_EQ|INE142M01019",
    "PHOENIXLTD": "NSE_EQ|INE211B01039",
    "TORNTPHARM": "NSE_EQ|INE685A01028",
    "TORNTPOWER": "NSE_EQ|INE813H01021",
    "TUBEINDIAT": "NSE_EQ|INE077A01020",
    "UNOMINDA": "NSE_EQ|INE405E01023",
    "UPL": "NSE_EQ|INE628A01036",
    "VOLTAS": "NSE_EQ|INE226A01021",
    "YESBANK": "NSE_EQ|INE528G01027",

    # Second column stocks that weren't already in first column
    "CGCL": "NSE_EQ|INE067A01029",  # CG Power
    "HINDCOPPER": "NSE_EQ|INE531E01026",  # Hindustan Copper
    "IPCALAB": "NSE_EQ|INE571A01020",  # Ipca Laboratories
    "MAHABANK": "NSE_EQ|INE457A01014",  # Bank of Maharashtra
    "NATCOPHARM": "NSE_EQ|INE987B01018",
    "PFC": "NSE_EQ|INE134E01011",  # Power Finance Corporation
    "RECLTD": "NSE_EQ|INE020B01018",
    "SUNDARMFIN": "NSE_EQ|INE572C01032",  # Sundaram Finance
    "TRENT": "NSE_EQ|INE849A01020",
    "ZYDUSLIFE": "NSE_EQ|INE010B01027"  # Zydus Lifesciences
}

# === Step 1: Read Sheet1 CSV to build trade pairs ===
def read_trade_plan_from_csv(csv_file):
    """
    Reads trade entries from a CSV file and organizes them into trade pairs (buy/sell).

    Args:
        csv_file (str): Path to the CSV file.

    Returns:
        list: A list of dictionaries, each representing a trade plan with
              'buy_time', 'buy_type', 'sell_time', 'sell_type'.
    """
    trade_entries = {}
    try:
        with open(csv_file, mode='r', newline='') as file:
            reader = csv.reader(file)
            header = next(reader)  # Skip header
            for row in reader:
                if len(row) < 3:
                    continue  # Skip incomplete rows
                trade_num = row[0].strip()
                trade_type = row[1].strip()
                date_time = row[2].strip()

                if trade_num not in trade_entries:
                    trade_entries[trade_num] = []
                trade_entries[trade_num].append({
                    'type': trade_type,
                    'datetime': date_time
                })
    except FileNotFoundError:
        print(f"Error: CSV file '{csv_file}' not found.")
        return []

    trade_plan = []
    for trade_num, events in trade_entries.items():
        buy = None
        sell = None

        for e in events:
            t = e['type'].lower()
            if 'entry' in t:
                buy = e
            elif 'exit' in t:
                sell = e

        if buy and sell:
            buy_type = 'Long' if 'long' in buy['type'].lower() else 'Short'
            sell_type = 'Long' if 'long' in sell['type'].lower() else 'Short'

            trade_plan.append({
                'buy_time': buy['datetime'],
                'buy_type': buy_type,
                'sell_time': sell['datetime'],
                'sell_type': sell_type,
            })
    return trade_plan

# === Helper function to fetch candles ===
def fetch_candles(instrument_key, unit, interval, from_date, to_date, access_token):
    """
    Fetches historical candle data from the Upstox API.

    Args:
        instrument_key (str): The unique key for the instrument (e.g., "NSE_EQ|INE002A01018").
        unit (str): Time unit (e.g., "minutes", "day").
        interval (str): Interval value (e.g., "1", "30").
        from_date (str): Start date in YYYY-MM-DD format.
        to_date (str): End date in YYYY-MM-DD format.
        access_token (str): Your Upstox API access token.

    Returns:
        list or None: A list of candle data, where each candle is a list:
                      [timestamp, open, high, low, close, volume], or None on error.
    """
    url = f"https://api.upstox.com/v3/historical-candle/{instrument_key}/{unit}/{interval}/{to_date}/{from_date}"
    headers = {
        "Accept": "application/json",
        "Authorization": f"Bearer {access_token}"
    }

    try:
        response = requests.get(url, headers=headers)
        response.raise_for_status()  # Raise an HTTPError for bad responses (4xx or 5xx)
        data = response.json()

        if 'data' in data and 'candles' in data['data'] and data['data']['candles']:
            return data['data']['candles']
        else:
            print(f"No candle data found for {instrument_key} from {from_date} to {to_date}. Response: {data}")
            return None
    except requests.exceptions.RequestException as e:
        print(f"Error fetching candles for {instrument_key}: {e}")
        return None

# === Step 3: Calculate trades and display ===
def main():
    """
    Main function to run the trade analysis.
    """
    trade_plan = read_trade_plan_from_csv(SHEET1_CSV)
    if not trade_plan:
        print("No trade plan generated. Exiting.")
        return

    # Prepare table headers and column widths for formatted output
    headers = [
        "Stock", "Buy Time", "Buy Type", "Buy Price", "Sell Time", "Sell Type",
        "Sell Price", "Quantity", "Brokerage (Rs)", "Net P/L (Rs)"
    ]
    # Adjust column widths based on expected content length
    col_widths = {
        "Stock": 12, "Buy Time": 18, "Buy Type": 10, "Buy Price": 12,
        "Sell Time": 18, "Sell Type": 10, "Sell Price": 12, "Quantity": 10,
        "Brokerage (Rs)": 15, "Net P/L (Rs)": 15
    }

    # Print table header
    header_line = ""
    for h in headers:
        header_line += h.ljust(col_widths[h])
    print(header_line)
    print("-" * len(header_line))

    for stock, instrument_key in INSTRUMENT_KEY_MAP.items():
        for trade in trade_plan:
            # Extract date parts for API call
            from_date = trade['buy_time'][:10]
            to_date = trade['sell_time'][:10]

            candles = fetch_candles(instrument_key, UNIT, INTERVAL, from_date, to_date, ACCESS_TOKEN)

            if candles is None:
                print(f"Error: No candle data for {stock} from {from_date} to {to_date}")
                continue

            # Find buy price (open) at buy_time
            buy_price = None
            # The API returns timestamp in ISO 8601 format (e.g., '2023-10-26T09:15:00+05:30')
            # We need to match up to minutes, similar to PHP's substr(..., 0, 16)
            buy_datetime_check = trade['buy_time'][:16].replace(' ', 'T')
            for c in candles:
                candle_time_api = c[0][:16] # Extract YYYY-MM-DDTHH:MM
                if candle_time_api == buy_datetime_check:
                    buy_price = c[1]  # Open price
                    break
            if buy_price is None:
                print(f"Error: Buy price missing for {stock} at {trade['buy_time']}")
                continue

            # Find sell price (close) at sell_time
            sell_price = None
            sell_datetime_check = trade['sell_time'][:16].replace(' ', 'T')
            for c in candles:
                candle_time_api = c[0][:16] # Extract YYYY-MM-DDTHH:MM
                if candle_time_api == sell_datetime_check:
                    sell_price = c[4]  # Close price
                    break
            if sell_price is None:
                print(f"Error: Sell price missing for {stock} at {trade['sell_time']}")
                continue

            # Ensure buy_price is not zero to avoid division by zero
            if buy_price == 0:
                print(f"Error: Buy price for {stock} is zero at {trade['buy_time']}. Skipping calculation.")
                continue

            quantity = int(INVESTMENT_PER_STOCK / buy_price)

            # Calculate P/L based on Long/Short
            buy_type_lower = trade['buy_type'].lower()
            sell_type_lower = trade['sell_type'].lower()

            gross_pl = 0
            if 'long' in buy_type_lower and 'long' in sell_type_lower:
                gross_pl = (sell_price - buy_price) * quantity
            elif 'short' in buy_type_lower and 'short' in sell_type_lower:
                gross_pl = (buy_price - sell_price) * quantity

            brokerage = quantity * BROKERAGE_PER_SHARE * 2  # Buy + Sell brokerage
            net_pl = gross_pl - brokerage

            # Print row data
            row_data = [
                stock,
                trade['buy_time'],
                trade['buy_type'],
                f"{buy_price:.2f}",
                trade['sell_time'],
                trade['sell_type'],
                f"{sell_price:.2f}",
                str(quantity),
                f"{brokerage:.2f}",
                f"{net_pl:.2f}"
            ]

            row_output = ""
            for i, item in enumerate(row_data):
                row_output += str(item).ljust(list(col_widths.values())[i])
            print(row_output)

if __name__ == "__main__":
    main()
