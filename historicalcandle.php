<?php

$instrument_key = "NSE_EQ|INE669E01016"; // Example: replace with actual instrument key
$unit = "minutes"; // Possible values: minutes, hours, days, weeks, months
$interval = "5"; // 1, 2, 3,... for minutes; 1-5 for hours; 1 for days/weeks/months
$to_date = "2025-08-02"; // End date (inclusive)
$from_date = "2025-08-01"; // Optional start date

$access_token = "eyJ0eXAiOiJKV1QiLCJrZXlfaWQiOiJza192MS4wIiwiYWxnIjoiSFMyNTYifQ.eyJzdWIiOiI4NkE1V0wiLCJqdGkiOiI2ODk5YTlhNTEyMTBmMjM3NmM5MGZmOTQiLCJpc011bHRpQ2xpZW50IjpmYWxzZSwiaXNQbHVzUGxhbiI6ZmFsc2UsImlhdCI6MTc1NDkwMDkwMSwiaXNzIjoidWRhcGktZ2F0ZXdheS1zZXJ2aWNlIiwiZXhwIjoxNzU0OTQ5NjAwfQ.qYE5DVn6uopQHKwIq9B4w2e3OIZhZceiIS6xrvj1u9g"; // Replace with your valid token

$url = "https://api.upstox.com/v3/historical-candle/$instrument_key/$unit/$interval/$to_date/$from_date";

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => $url,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => array(
    "Accept: application/json",
    "Authorization: Bearer $access_token"
  ),
));

$response = curl_exec($curl);

if(curl_errno($curl)){
    echo "cURL Error: " . curl_error($curl);
}

curl_close($curl);

echo $response;
