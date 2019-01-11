<?php

/**
 * Plugin Name: Tax Calculation
 * Plugin URI: http://www.genefrice.com/my-first-plugin
 * Description: The very first plugin that I have ever created.
 * Version: 1.0
 * Author: Gene Rice
 * Author URI: http://www.genefrice.com
 */
$url = "https://sstws.taxware.net:443/Twe/api/rest/calcTax/doc";
$usrname = "restProd@ACBJ";
$pswrd = "aRest2015";
$hmacKey = "004ca1ef-516c-49b3-b67a-9ea5cbf3752e";
$isoDate = date(DateTime::ISO8601);
$hmacInput = "POSTapplication/json" . $isoDate . "/Twe/api/rest/calcTax/doc" . $usrname . $pswrd;
$hmacSig = hash_hmac("sha1", $hmacInput, $hmacKey);

// echo $hmacInput;
$response = wp_remote_post($url, array(
     "method" => "POST",
     "headers" => array(
          "Content-Type" => "application/json",
          "Date" => "$isoDate",
          // "Authorization" => "TAX " . $usrname . ":" . $hmacSig
          "Authorization" => "TAX $usrname : $hmacSig"
     ),
     "body" => array(
          "usrname" => "$usrname",
          "pswrd" => "$pswrd",
          "currn" => "USD",
          "txCalcTp" => "1",
          "trnTp" => "1",
          "grossAmt" => "100.00",
          "sFStNum" => "120",
          "sFSt" => "W Morehead St",
          "sFStNameNum" => "120 W MOREHEAD ST",
          "sFCity" => "CHARLOTTE",
          "sFStateProv" => "NC",
          "sFPstlCd" => "28202",
          "sFCountry" => "USA",
          "sTStNum" => "188",
          "sTSt" => "Eller Cove Rd",
          "sTStNameNum" => "188 Eller Cove Rd",
          "sTCity" => "Charlotte",
          "stStateProv" => "NC",
          "sTPstlCd" => "28202",
          "sTCountry" => "USA"
          
     )
));

if (is_wp_error($response)) {
     $error_message = $response->get_error_message();
     echo "Something went wrong: $error_message";
} else {
     print_r($response);
}

?>


<!-- 
"lines" => array(
               "orgCd" => "99692",
               
               
               "sTStNameNum" => "120 W. Morehead St",
               "sTCity" => "Charlotte",
               "stStateProv" => "NC",
               "sTPstlCd" => "28202",
               "sTCountry" => "USA"
          ) -->