<?php
function create_api_key($description){
    require_once "connect.php";
    try {
        $key = bin2hex(random_bytes(64));
    } catch (\Random\RandomException $e) {
        header("HTTP/1.0 500 Internal Server Error");
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO api_keys (key, description) VALUES (:key, :description)")->execute([
        "key" => hash("sha256", $key),
        "description" => $description
    ]);

    return $key;
}
