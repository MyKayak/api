<?php
function createApiKey($description){
    require "connect.php";
    try {
        $key = bin2hex(random_bytes(32));
    } catch (\Random\RandomException $e) {
        header("HTTP/1.0 500 Internal Server Error");
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO api_keys (api_key, description) VALUES (:api_key, :description)");
    if (!$stmt->execute([
        "api_key" => hash("sha256", $key),
        "description" => $description
    ])) {
        return false;
    }

    return $key;
}
