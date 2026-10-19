<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$url = isset($_GET['url']) ? $_GET['url'] : '';
$method = isset($_GET['method']) ? strtoupper($_GET['method']) : 'GET';

if (empty($url)) {
    http_response_code(400);
    echo json_encode(["error" => "URL parameter is required."]);
    exit;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

// Set headers to accept json/html
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json, text/html, text/plain, */*'
]);

$body = file_get_contents('php://input');
if ($method === 'POST' && !empty($body)) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
}

curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(["error" => "cURL error (" . curl_errno($ch) . "): " . curl_error($ch)]);
} else {
    // Determine content type
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    if ($contentType) {
        header("Content-Type: " . $contentType);
    }
    http_response_code($httpcode);
    echo $response;
}
curl_close($ch);
?>
