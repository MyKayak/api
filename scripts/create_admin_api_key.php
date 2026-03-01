<?php
require_once "../src/api/utils/connect.php";
try {
    $key = bin2hex(random_bytes(8)); // 1 in 18446744073709551616 chance everything breaks
} catch (\Random\RandomException $e) {
    echo "HTTP/1.0 500 Internal Server Error";
    exit;
}

$stmt = $conn->prepare("INSERT INTO admin_api_keys (api_key, description) VALUES (:key, :description)")->execute([
    "key" => hash("sha256", $key),
    "description" => $argv[1] // example: `$ php create_admin_key.php "Admin 1"`
]);

echo "Admin API key generated : " . $key;
echo "\nYou better not share this with anyone!\n";