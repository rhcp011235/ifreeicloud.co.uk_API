<?php

$myCheck["service"] = 4; // FMI ON / OFF CHECK $0.1 each check
$myCheck["imei"] = ""; // BLANK IMEI
$myCheck["key"] = ""; // YOUR API KEY


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
}

