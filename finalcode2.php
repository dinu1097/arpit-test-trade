<?php
// Config
$sheet1Csv = 'Sheet1-Table 1.csv';  // CSV with trades: Trade#,Type,DateTime
$unit = "minutes"; 
$interval = "5"; 
$investmentPerStock = 500000;
$brokeragePerShare = 0.013;
$access_token = "YOUR_ACCESS_TOKEN"; // Replace with your actual token

// === Step 1: Read Sheet1 CSV to build trade pairs ===
$rows1 = array_map('str_getcsv', file($sheet1Csv));
$header1 = array_shift($rows1); // Remove header

$tradeEntries = [];
foreach ($rows1 as $row) {
    if (count($row) < 3) continue; // Skip incomplete rows
    $tradeNum = trim($row[0]);
    $type = trim($row[1]);
    $dateTime = trim($row[2]);

    if (!isset($tradeEntries[$tradeNum])) {
        $tradeEntries[$tradeNum] = [];
    }
    $tradeEntries[$tradeNum][] = [
        'type' => $type,
        'datetime' => $dateTime
    ];
}

// Build trade plan array with buy/sell times and types
$tradePlan = [];
foreach ($tradeEntries as $tradeNum => $events) {
    $buy = null;
    $sell = null;

    foreach ($events as $e) {
        $t = strtolower($e['type']);
        if (strpos($t, 'entry') !== false) {
            $buy = $e;
        } elseif (strpos($t, 'exit') !== false) {
            $sell = $e;
        }
    }

    if ($buy && $sell) {
        $buy_type = stripos($buy['type'], 'long') !== false ? 'Long' : 'Short';
        $sell_type = stripos($sell['type'], 'long') !== false ? 'Long' : 'Short';

        $tradePlan[] = [
            'buy_time' => $buy['datetime'],
            'buy_type' => $buy_type,
            'sell_time' => $sell['datetime'],
            'sell_type' => $sell_type,
        ];
    }
}

// === Hardcoded stock list with known instrument keys ===
$instrumentKeyMap = [
    "RELIANCE" => "NSE_EQ|INE002A01018",
    "TCS" => "NSE_EQ|INE467B01029",
    "HDFCBANK" => "NSE_EQ|INE040A01026",
    "INFY" => "NSE_EQ|INE009A01021",
    "ICICIBANK" => "NSE_EQ|INE090A01021",
    "KOTAKBANK" => "NSE_EQ|INE237A01028",
    "SBIN" => "NSE_EQ|INE062A01020",
    "BHARTIARTL" => "NSE_EQ|INE397D01024",
    "HINDUNILVR" => "NSE_EQ|INE030A01027",
    "ITC" => "NSE_EQ|INE154A01025"
];

// === Helper function to fetch candles ===
function fetchCandles($instrument_key, $unit, $interval, $from_date, $to_date, $access_token) {
    $url = "https://api.upstox.com/v3/historical-candle/$instrument_key/$unit/$interval/$to_date/$from_date";

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Accept: application/json",
            "Authorization: Bearer $access_token"
        ],
    ]);

    $response = curl_exec($curl);
    if (curl_errno($curl)) {
        curl_close($curl);
        return null;
    }
    curl_close($curl);

    $data = json_decode($response, true);
    if (!isset($data['data']['candles']) || empty($data['data']['candles'])) {
        return null;
    }

    return $data['data']['candles'];
}

// === Step 3: Calculate trades and display ===
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr>
    <th>Stock</th>
    <th>Buy Time</th>
    <th>Buy Type</th>
    <th>Buy Price</th>
    <th>Sell Time</th>
    <th>Sell Type</th>
    <th>Sell Price</th>
    <th>Quantity</th>
    <th>Brokerage (Rs)</th>
    <th>Net P/L (Rs)</th>
</tr>";

foreach ($instrumentKeyMap as $stock => $instrument_key) {
    foreach ($tradePlan as $trade) {
        $from_date = substr($trade['buy_time'], 0, 10);
        $to_date = substr($trade['sell_time'], 0, 10);

        $candles = fetchCandles($instrument_key, $unit, $interval, $from_date, $to_date, $access_token);
        if ($candles === null) {
            echo "<tr><td colspan='10' style='color:red;'>No candle data for $stock from $from_date to $to_date</td></tr>";
            continue;
        }

        // Find buy price (open) at buy_time
        $buyPrice = null;
        $buyDateTimeCheck = str_replace(' ', 'T', substr($trade['buy_time'], 0, 16));
        foreach ($candles as $c) {
            $candleTime = substr($c[0], 0, 16);
            if ($candleTime === $buyDateTimeCheck) {
                $buyPrice = $c[1];
                break;
            }
        }
        if ($buyPrice === null) {
            echo "<tr><td colspan='10' style='color:red;'>Buy price missing for $stock at {$trade['buy_time']}</td></tr>";
            continue;
        }

        // Find sell price (close) at sell_time
        $sellPrice = null;
        $sellDateTimeCheck = str_replace(' ', 'T', substr($trade['sell_time'], 0, 16));
        foreach ($candles as $c) {
            $candleTime = substr($c[0], 0, 16);
            if ($candleTime === $sellDateTimeCheck) {
                $sellPrice = $c[4];
                break;
            }
        }
        if ($sellPrice === null) {
            echo "<tr><td colspan='10' style='color:red;'>Sell price missing for $stock at {$trade['sell_time']}</td></tr>";
            continue;
        }

        $quantity = floor($investmentPerStock / $buyPrice);

        // Calculate P/L based on Long/Short
        $buyType = strtolower($trade['buy_type']);
        $sellType = strtolower($trade['sell_type']);
        if ($buyType === 'long' && $sellType === 'long') {
            $grossPL = ($sellPrice - $buyPrice) * $quantity;
        } elseif ($buyType === 'short' && $sellType === 'short') {
            $grossPL = ($buyPrice - $sellPrice) * $quantity;
        } else {
            $grossPL = 0;
        }

        $brokerage = $quantity * $brokeragePerShare * 2; // Buy+Sell brokerage
        $netPL = $grossPL - $brokerage;

        echo "<tr>
            <td>$stock</td>
            <td>{$trade['buy_time']}</td>
            <td>{$trade['buy_type']}</td>
            <td>{$buyPrice}</td>
            <td>{$trade['sell_time']}</td>
            <td>{$trade['sell_type']}</td>
            <td>{$sellPrice}</td>
            <td>$quantity</td>
            <td>" . number_format($brokerage, 2) . "</td>
            <td style='color:" . ($netPL >= 0 ? "green" : "red") . "'>" . number_format($netPL, 2) . "</td>
        </tr>";
    }
}

echo "</table>";
