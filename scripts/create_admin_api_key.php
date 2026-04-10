<?php
require_once "../src/utils/connect.php";
try {
    $key = bin2hex(random_bytes(32));
} catch (\Random\RandomException $e) {
    echo "Error generating random bytes.\n";
    exit(1);
}

$description = $argv[1] ?? "Admin Key Created " . date("Y-m-d H:i:s");

$stmt = $conn->prepare("INSERT INTO admin_api_keys (api_key, description) VALUES (:key, :description)");
if ($stmt->execute([
    "key" => hash("sha256", $key),
    "description" => $description
])) {
    echo "Admin API key generated : " . $key . "\n";
    echo "You better not share this with anyone!\n";
} else {
    echo "Error saving API key to database.\n";
    exit(1);
}