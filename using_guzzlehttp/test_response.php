<?php
// Quick test of upload_handler response
echo "Testing upload_handler.php response:\n\n";

// Simulate a simple GET request
$response = file_get_contents("http://localhost:8081/upload_handler.php");
echo "Response: " . $response . "\n\n";

// Test if it's valid JSON
$json = json_decode($response, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "✅ Valid JSON response\n";
    print_r($json);
} else {
    echo "❌ Invalid JSON: " . json_last_error_msg() . "\n";
    echo "Raw response: " . $response . "\n";
}
?>
