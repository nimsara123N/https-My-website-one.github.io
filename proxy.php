<?php
// URL of the target website
$target_url = "";

// Initialize a cURL session
$ch = curl_init();

// Set cURL options to fetch the content of the target website
curl_setopt($ch, CURLOPT_URL, $target_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);

// Execute the cURL request and capture the response
$response = curl_exec($ch);

// Close the cURL session
curl_close($ch);

// Output the content of the target website
echo $response;
?>
