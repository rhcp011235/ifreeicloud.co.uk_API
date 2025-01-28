<?php

// Function to load environment variables from .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception('.env file not found');
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $apiKeys = [];

    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }

        // Collect all API keys
        if (strpos($name, 'API_KEY') === 0) {
            $apiKeys[$name] = $value;
        }
    }

    return $apiKeys;
}

// Load environment variables and get all API keys
$apiKeys = loadEnv(__DIR__ . '/.env');

// Handle the request to get balances
if (isset($_GET['action']) && $_GET['action'] === 'get_balances') {
    $balances = [];

    foreach ($apiKeys as $keyName => $apiKey) {
        $balance = check_balance_for_key($apiKey);
        $balances[] = [
            'key' => $keyName,
            'balance' => $balance
        ];
    }

    echo json_encode([
        'success' => true,
        'balances' => $balances
    ]);
    exit;
}

// Function to check balance for a given API key
function check_balance_for_key($apiKey) {
    $myCheck = [
        "key" => $apiKey,
        "accountinfo" => "balance"
    ];

    $ch = curl_init("https://api.ifreeicloud.co.uk");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $myCheck);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $myResult = json_decode(curl_exec($ch));
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode != 200) {
        return "Error: HTTP Code $httpcode";
    } elseif ($myResult->success !== true) {
        return "Error: $myResult->error";
    } else {
        return $myResult->response; // Human-readable balance
    }
}

// Handle IMEI submission
if (isset($_POST['imeis']) && isset($_POST['api-key'])) {
    $selectedApiKey = $_POST['api-key'];
    $imeis = preg_split('/\s+/', trim($_POST['imeis']));

    // Start styled output
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>IMEI Check Results</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                background-color: #f4f4f4;
                padding: 20px;
            }
            .result-container {
                background: #fff;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                margin-bottom: 20px;
            }
            h2 {
                color: #333;
                margin-bottom: 10px;
            }
            p {
                color: #555;
                margin: 5px 0;
            }
            .error {
                color: #ff0000;
                font-weight: bold;
            }
        </style>
    </head>
    <body>";

    foreach ($imeis as $imei) {
        $myCheck = [
            "service" => 4, // FMI ON / OFF CHECK $0.1 each check
            "imei" => $imei,
            "key" => $apiKeys[$selectedApiKey] // Use the selected API key
        ];

        // Check model
        echo "<div class='result-container'>";
        echo "<h2>IMEI: $imei</h2>";
        echo "<h3>Model Information</h3>";
        echo "<p>";
        check_model($myCheck);
        echo "</p>";

        // Check FMI status
        echo "<h3>FMI Status</h3>";
        echo "<p>";
        check_imei($myCheck);
        echo "</p>";
        echo "</div>";
    }

    // End styled output
    echo "</body>
    </html>";
}

function check_imei($myCheck) {
    $ch = curl_init("https://api.ifreeicloud.co.uk");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $myCheck);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $myResult = json_decode(curl_exec($ch));
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode != 200) {
        echo "<span class='error'>Error: HTTP Code $httpcode</span>";
    } elseif ($myResult->success !== true) {
        echo "<span class='error'>Error: $myResult->error</span>";
    } else {
        // Format the response
        $response = $myResult->response;

        // Strip HTML tags for easier processing
        $responsePlain = strip_tags($response);

        // Normalize newlines
        $responsePlain = str_replace("\r", "", $responsePlain);

        // Add ✅ for "Find My: ON" and ❌ for "Find My: OFF"
        if (strpos($responsePlain, "Find My: ON") !== false) {
            $response = str_replace("Find My: <span style=\"color:red;\">ON</span>", "Find My: ON ❌", $response);
        } elseif (strpos($responsePlain, "Find My: OFF") !== false) {
            $response = str_replace("Find My: <span style=\"color:green;\">OFF</span>", "Find My: OFF ✅", $response);
        }

        // Output the formatted response
        echo nl2br($response); // Preserve line breaks in HTML
    }
}



// Function to check model
function check_model($myCheck) {
    global $apiKeys;
    $myCheck2 = [
        "key" => $myCheck['key'], // Use the selected API key
        "service"  => '0',
        "imei" => $myCheck['imei']
    ];

    $ch = curl_init("https://api.ifreeicloud.co.uk");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $myCheck2);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $myResult = json_decode(curl_exec($ch));
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode != 200) {
        echo "<span class='error'>Error: HTTP Code $httpcode</span>";
    } elseif ($myResult->success !== true) {
        echo "<span class='error'>Error: $myResult->error</span>";
    } else {
        echo $myResult->object->model;
    }
}
?>
