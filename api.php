    <?php

    // Function to load environment variables from .env file
    function loadEnv($path) {
        if (!file_exists($path)) {
            throw new Exception('.env file not found');
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
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
        }
    }

    // Load environment variables
    loadEnv(__DIR__ . '/.env');

    $API_KEY = getenv('API_KEY'); // Load API key from environment variable

    if (isset($_POST['imeis'])) {
        $imeis = preg_split('/\s+/', trim($_POST['imeis']));
        foreach ($imeis as $imei) {
            $myCheck = [
                "service" => 4, // FMI ON / OFF CHECK $0.1 each check
                "imei" => $imei,
                "key" => $API_KEY // YOUR API KEY
            ];

            // check model
            check_model($myCheck);

            // check FMI status 
            check_imei($myCheck);
        }
    }
    // check balance of the API KEY
    check_balance();

    function check_imei($myCheck)
    {
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

    function check_balance()
    {
        global $API_KEY;
        $myCheck = [
            "key" => $API_KEY, // YOUR API KEY
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
            echo "Error: HTTP Code $httpcode";
        } elseif ($myResult->success !== true) {
            echo "Error: $myResult->error";
            return;
        } else {
            echo $myResult->response; // Human-readable
            return;
        }
    }

    function check_model($myCheck)
    {
        global $API_KEY;
        $myCheck2 = [
            "key" => $API_KEY, // YOUR API KEY
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
            return;
        } else {
            #echo $myResult->response;
            echo $myResult->object->model;
            echo "<BR>";
            return;
        }
    }
    ?>