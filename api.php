<?php
if (isset($_POST['imeis'])) {
    $imeis = preg_split('/\s+/', trim($_POST['imeis']));
    foreach ($imeis as $imei) {
        $myCheck = [
            "service" => 4, // FMI ON / OFF CHECK $0.1 each check
            "imei" => $imei,
            "key" => "API_KEY" // YOUR API KEY
        ];
        //echo $imei . "<br>";
        check_imei($myCheck);
    }
}

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
        //echo "<hr><pre>" . print_r($myResult->object, true) . "</pre><hr>"; // TEST ONLY
        // Here you can access specific info!
        // echo $myResult->object->model;
    }
}
?>