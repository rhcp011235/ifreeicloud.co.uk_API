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

    foreach ($imeis as $imei) {
        $myCheck = [
            "service" => 4, // FMI ON / OFF CHECK $0.1 each check
            "imei" => $imei,
            "key" => $apiKeys[$selectedApiKey] // Use the selected API key
        ];

        // Check model
        echo "<div style='font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif;'>";
        echo "<h2>Model Information</h2>";
        echo "<p>";
        check_model($myCheck);
        echo "</p>";
        echo "</div>";

        // Check FMI status
        echo "<div style='font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif;'>";
        echo "<h2>FMI Status</h2>";
        echo "<p>";
        check_imei($myCheck);
        echo "</p>";
        echo "</div>";
    }
}

// Function to check IMEI
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
        echo "Error: HTTP Code $httpcode";
    } elseif ($myResult->success !== true) {
        echo "Error: $myResult->error";
    } else {
        echo $myResult->response;
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
        echo "Error: HTTP Code $httpcode";
    } elseif ($myResult->success !== true) {
        echo "Error: $myResult->error";
    } else {
        echo $myResult->object->model;
        echo "<BR>";
    }
}
?>
