<?php
// Config
$sheet1Csv = 'Sheet1-Table 1.csv';  // CSV with trades: Trade#,Type,DateTime
$unit = "minutes"; 
$interval = "1"; 
$investmentPerStock = 500000;
$brokeragePerShare = 0.0013;
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
    "ITC" => "NSE_EQ|INE154A01025",
    
    // First column stocks
    "ABCAPITAL" => "NSE_EQ|INE674K01013",
    "ACC" => "NSE_EQ|INE012A01025",
    "AARTIIND" => "NSE_EQ|INE769A01020",
    "ALKEM" => "NSE_EQ|INE540L01014",
    "APLAPOLLO" => "NSE_EQ|INE702C01027",
    "ASHOKLEY" => "NSE_EQ|INE208A01029",
    "ASTRAL" => "NSE_EQ|INE006I01046",
    "AUBANK" => "NSE_EQ|INE949L01017",
    "AUROPHARMA" => "NSE_EQ|INE406A01037",
    "BALKRISIND" => "NSE_EQ|INE787D01026",
    "BANDHANBNK" => "NSE_EQ|INE545U01014",
    "BANKINDIA" => "NSE_EQ|INE084A01016",
    "BHARATFORG" => "NSE_EQ|INE465A01025",
    "BHEL" => "NSE_EQ|INE257A01026",
    "COFORGE" => "NSE_EQ|INE591G01017",
    "COLPAL" => "NSE_EQ|INE259A01022",
    "CONCOR" => "NSE_EQ|INE111A01025",
    "CUMMINSIND" => "NSE_EQ|INE298A01020",
    "DALBHARAT" => "NSE_EQ|INE00R701025",
    "DIXON" => "NSE_EQ|INE935N01012",
    "DELHIVERY" => "NSE_EQ|INE148O01012",
    "FEDERALBNK" => "NSE_EQ|INE171A01029",
    "NAUKRI" => "NSE_EQ|INE663F01024",
    "GLENMARK" => "NSE_EQ|INE935A01035",
    "GMRINFRA" => "NSE_EQ|INE776C01039",
    "GODREJPROP" => "NSE_EQ|INE484D01022",
    "HINDPETRO" => "NSE_EQ|INE094A01015",
    "IDFCFIRSTB" => "NSE_EQ|INE092A01019",
    "INDIANB" => "NSE_EQ|INE562A01011",
    "IGL" => "NSE_EQ|INE203G01027",
    "IRCTC" => "NSE_EQ|INE335Y01020",
    "JSL" => "NSE_EQ|INE220G01021",
    "JUBLFOOD" => "NSE_EQ|INE797F01012",
    "KALYANKJIL" => "NSE_EQ|INE303R01014",
    "KPITTECH" => "NSE_EQ|INE04I401011",
    "LAURUSLABS" => "NSE_EQ|INE947Q01028",
    "LICHSGFIN" => "NSE_EQ|INE115A01026",
    "LUPIN" => "NSE_EQ|INE326A01037",
    "MAXHEALTH" => "NSE_EQ|INE027H01010",
    "MAZDOCK" => "NSE_EQ|INE249I01017",
    "MPHASIS" => "NSE_EQ|INE356A01018",
    "MUTHOOTFIN" => "NSE_EQ|INE414G01012",
    "NHPC" => "NSE_EQ|INE848E01016",
    "NMDC" => "NSE_EQ|INE584A01023",
    "OBEROIRLTY" => "NSE_EQ|INE093I01010",
    "OIL" => "NSE_EQ|INE274J01014",
    "PAYTM" => "NSE_EQ|INE982J01020",
    "OFSS" => "NSE_EQ|INE881D01027",
    "PATANJALI" => "NSE_EQ|INE619A01035",
    "PERSISTENT" => "NSE_EQ|INE262H01013",
    "PETRONET" => "NSE_EQ|INE347G01014",
    "PIIND" => "NSE_EQ|INE603J01030",
    "PRESTIGE" => "NSE_EQ|INE811K01011",
    "RAILVIKAS" => "NSE_EQ|INE415G01027",
    "SAIL" => "NSE_EQ|INE114A01011",
    "SJVN" => "NSE_EQ|INE002L01015",
    "SOLARINDS" => "NSE_EQ|INE343H01029",
    "SONACOMS" => "NSE_EQ|INE07V001025",
    "SUPREMEIND" => "NSE_EQ|INE195A01028",
    "TATACOMM" => "NSE_EQ|INE151A01013",
    "TATATECH" => "NSE_EQ|INE142M01019",
    "PHOENIXLTD" => "NSE_EQ|INE211B01039",
    "TORNTPHARM" => "NSE_EQ|INE685A01028",
    "TORNTPOWER" => "NSE_EQ|INE813H01021",
    "TUBEINDIAT" => "NSE_EQ|INE077A01020",
    "UNOMINDA" => "NSE_EQ|INE405E01023",
    "UPL" => "NSE_EQ|INE628A01036",
    "VOLTAS" => "NSE_EQ|INE226A01021",
    "YESBANK" => "NSE_EQ|INE528G01027",

    // Second column stocks that weren't already in first column
    "CGCL" => "NSE_EQ|INE067A01029", // CG Power
    "HINDCOPPER" => "NSE_EQ|INE531E01026", // Hindustan Copper
    "IPCALAB" => "NSE_EQ|INE571A01020", // Ipca Laboratories
    "MAHABANK" => "NSE_EQ|INE457A01014", // Bank of Maharashtra
    "NATCOPHARM" => "NSE_EQ|INE987B01018",
    "PFC" => "NSE_EQ|INE134E01011", // Power Finance Corporation
    "RECLTD" => "NSE_EQ|INE020B01018",
    "SUNDARMFIN" => "NSE_EQ|INE572C01032", // Sundaram Finance
    "TRENT" => "NSE_EQ|INE849A01020",
    "ZYDUSLIFE" => "NSE_EQ|INE010B01027" // Zydus Lifesciences
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
