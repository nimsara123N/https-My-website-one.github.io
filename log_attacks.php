<?php
// Set the path to the log file
$logFile = __DIR__ . 'attacks_log.txt';

// Create the logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

// Get the client's details
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown User Agent';
$requestUri = $_SERVER['REQUEST_URI'] ?? 'Unknown Request URI';
$method = $_SERVER['REQUEST_METHOD'] ?? 'Unknown Method';
$referrer = $_SERVER['HTTP_REFERER'] ?? 'No Referrer';
$time = date('Y-m-d H:i:s');

// Log entry format
$logEntry = "$time | IP: $ipAddress | Method: $method | URL: $requestUri | Referrer: $referrer | User Agent: $userAgent" . PHP_EOL;

// Write the log entry to the log file
file_put_contents($logFile, $logEntry, FILE_APPEND);

// Return a 403 Forbidden response
http_response_code(403);
echo "Access Denied.";
?>