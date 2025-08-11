<?php

// ===== CONFIG =====
$stocks = ["RELIANCE", "TCS", "HDFCBANK"]; // Stock symbols (for example)
$investmentPerStock = 500000;
$brokeragePerShare = 0.013;
$unit = "minutes"; 
$interval = "1"; 
$access_token = "YOUR_ACCESS_TOKEN"; // Replace with valid token

// Trade plan with full date+time strings
$tradePlan = [
    ["buy_time" => "2025-08-01 09:30", "buy_type" => "Long",  "sell_time" => "2025-08-01 10:30", "sell_type" => "Long"],
    ["buy_time" => "2025-08-01 10:45", "buy_type" => "Short", "sell_time" => "2025-08-01 11:15", "sell_type" => "Short"],
    ["buy_time" => "2025-08-01 11:30", "buy_type" => "Long",  "sell_time" => "2025-08-01 12:00", "sell_type" => "Long"],
    ["buy_time" => "2025-08-01 12:15", "buy_type" => "Short", "sell_time" => "2025-08-01 12:45", "sell_type" => "Short"],
    ["buy_time" => "2025-08-01 13:00", "buy_type" => "Long",  "sell_time" => "2025-08-01 13:45", "sell_type" => "Long"],
    ["buy_time" => "2025-08-01 14:00", "buy_type" => "Short", "sell_time" => "2025-08-01 14:30", "sell_type" => "Short"],
    ["buy_time" => "2025-08-01 14:45", "buy_type" => "Long",  "sell_time" => "2025-08-01 15:15", "sell_type" => "Long"],
    ["buy_time" => "2025-08-01 15:30", "buy_type" => "Short", "sell_time" => "2025-08-01 15:55", "sell_type" => "Short"],
    ["buy_time" => "2025-08-01 09:45", "buy_type" => "Long",  "sell_time" => "2025-08-01 10:15", "sell_type" => "Long"],
    ["buy_time" => "2025-08-01 11:00", "buy_type" => "Short", "sell_time" => "2025-08-01 11:45", "sell_type" => "Short"]
];

// Extract the earliest buy date and latest sell date from trade plan
$allBuyDates = array_map(fn($t) => substr($t['buy_time'], 0, 10), $tradePlan);
$allSellDates = array_map(fn($t) => substr($t['sell_time'], 0, 10), $tradePlan);
$from_date = min($allBuyDates);
$to_date = max($allSellDates);

// Helper: Convert stock symbol to instrument key (You must update this mapping properly)
function getInstrumentKey($stockSymbol) {
    $map = [
        "RELIANCE" => "NSE_EQ|INE002A01018",
        "TCS" => "NSE_EQ|INE467B01029",
        "HDFCBANK" => "NSE_EQ|INE040A01034"
    ];
    return $map[$stockSymbol] ?? null;
}

// Helper: Find candle closest to given datetime
function findClosestCandleIndex($candles, $datetime) {
    $target = strtotime($datetime);
    $closestIndex = null;
    $closestDiff = PHP_INT_MAX;
    foreach ($candles as $i => $candle) {
        $candleTime = strtotime($candle[0]);
        $diff = abs($candleTime - $target);
        if ($diff < $closestDiff) {
            $closestDiff = $diff;
            $closestIndex = $i;
        }
    }
    return $closestIndex;
}

// ===== MAIN PROCESS =====

$allTrades = [];

foreach ($stocks as $stockName) {
    $instrument_key = getInstrumentKey($stockName);
    if (!$instrument_key) {
        echo "Instrument key not found for $stockName\n";
        continue;
    }

    // Build API URL
    $url = "https://api.upstox.com/v3/historical-candle/$instrument_key/$unit/$interval/$to_date/$from_date";

    // cURL request
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
        echo "cURL Error for $stockName: " . curl_error($curl) . "\n";
        curl_close($curl);
        continue;
    }
    curl_close($curl);

    $data = json_decode($response, true);
    if (!isset($data['data']['candles']) || empty($data['data']['candles'])) {
        echo "No candles data for $stockName\n";
        continue;
    }
    $candles = $data['data']['candles'];

    // Process each trade in plan
    foreach ($tradePlan as $trade) {
        $buyTime = $trade['buy_time'];
        $sellTime = $trade['sell_time'];
        $buyType = $trade['buy_type'];
        $sellType = $trade['sell_type'];

        // Find closest candle indices for buy and sell times
        $buyIndex = findClosestCandleIndex($candles, $buyTime);
        $sellIndex = findClosestCandleIndex($candles, $sellTime);

        if ($buyIndex === null || $sellIndex === null) {
            // Could not find suitable candle for buy or sell time
            continue;
        }

        $buyCandle = $candles[$buyIndex];
        $sellCandle = $candles[$sellIndex];

        $buyPrice = $buyCandle[1];  // Open price at buy candle
        $sellPrice = $sellCandle[4]; // Close price at sell candle

        $quantity = floor($investmentPerStock / $buyPrice);

        // Calculate gross P/L based on long/short
        if (strtolower($buyType) === "long") {
            $grossPL = ($sellPrice - $buyPrice) * $quantity;
        } else {
            // Short: profit if price falls
            $grossPL = ($buyPrice - $sellPrice) * $quantity;
        }

        // Brokerage applies on sell side, buy + sell brokerage per share * quantity
        $brokerage = $quantity * $brokeragePerShare * 2;

        $netPL = $grossPL - $brokerage;

        // Store trade details
        $allTrades[] = [
            "stock" => $stockName,
            "entry_time" => substr($buyTime, 11, 5),  // HH:MM
            "buy_datetime" => $buyTime,
            "exit_time" => substr($sellTime, 11, 5),  // HH:MM
            "sell_datetime" => $sellTime,
            "type" => ucfirst(strtolower($buyType)),
            "quantity" => $quantity,
            "buy_price" => $buyPrice,
            "sell_price" => $sellPrice,
            "brokerage" => $brokerage,
            "net_pl" => $netPL
        ];
    }
}

// ===== DISPLAY RESULTS =====

echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
echo "<tr>
<th>Stock</th>
<th>Entry Time</th>
<th>Buy DateTime</th>
<th>Exit Time</th>
<th>Sell DateTime</th>
<th>Type</th>
<th>Quantity</th>
<th>Buy Price</th>
<th>Sell Price</th>
<th>Brokerage (₹)</th>
<th>Net P/L (₹)</th>
</tr>";

foreach ($allTrades as $trade) {
    echo "<tr>
    <td>{$trade['stock']}</td>
    <td>{$trade['entry_time']}</td>
    <td>{$trade['buy_datetime']}</td>
    <td>{$trade['exit_time']}</td>
    <td>{$trade['sell_datetime']}</td>
    <td>{$trade['type']}</td>
    <td>{$trade['quantity']}</td>
    <td>" . number_format($trade['buy_price'], 2) . "</td>
    <td>" . number_format($trade['sell_price'], 2) . "</td>
    <td>" . number_format($trade['brokerage'], 2) . "</td>
    <td style='color:" . ($trade['net_pl'] >= 0 ? "green" : "red") . "'>" . number_format($trade['net_pl'], 2) . "</td>
    </tr>";
}

echo "</table>";
